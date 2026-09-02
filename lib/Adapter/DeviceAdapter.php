<?php

declare(strict_types=1);

namespace OCA\AdminOffboard\Adapter;

use OCA\AdminOffboard\Db\Entity\Device;
use OCA\AdminOffboard\Db\Repository\DeviceRepository;
use OCP\Notification\IManager as INotificationManager;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class DeviceAdapter
{
    private const WIPE_SUPPORTED_DEVICES = [
        'mirall',
        'nextcloud',
        'nextcloud-desktop',
        'nextcloud-mobile-ios',
        'nextcloud-mobile-android',
        'nextcloud-mobile',
        'windows-desktop',
        'macos-desktop',
        'linux-desktop'
    ];

    public function __construct(
        private DeviceRepository $deviceRepository,
        private TokenAdapter $tokenAdapter,
        private INotificationManager $notificationManager,
        private IUserManager $userManager,
        private LoggerInterface $logger
    ) {
    }

    public function remoteWipeUserDevices(string $userId): int
    {
        $devices = $this->syncUserDevices($userId);
        $count = 0;

        foreach ($devices as $device) {
            if ($device->isWipeSupported()) {
                $this->remoteWipeDevice($device->getId());
                $count++;
            }
        }

        return $count;
    }

    public function getUserDevices(string $userId): array
    {
        $devices = $this->deviceRepository->findByUser($userId);

        if (empty($devices)) {
            return $this->syncUserDevices($userId);
        }

        return $devices;
    }

    public function getDevice(int $deviceId): ?Device
    {
        return $this->deviceRepository->find($deviceId);
    }

    public function syncUserDevices(string $userId): array
    {
        $this->deviceRepository->deleteByUser($userId);

        $tokens = $this->tokenAdapter->getUserTokens($userId);
        $devices = [];

        foreach ($tokens as $token) {
            $device = $this->createDevice($userId, $token);
            if ($device) {
                $devices[] = $device;
            }
        }

        $this->logger->info('Synced devices for user', [
            'user_id' => $userId,
            'count' => count($devices)
        ]);

        return $devices;
    }

    private function createDevice(string $userId, array $tokenData): ?Device
    {
        $device = new Device();
        $device->setUserId($userId);
        $device->setTokenId((int)$tokenData['id']);
        $device->setDeviceName($tokenData['name'] ?? 'Unknown Device');
        $device->setDeviceType($this->detectDeviceType($tokenData));
        $device->setLastActivity($tokenData['last_activity'] ?? time());
        $device->setWipeSupported($this->isWipeSupported($tokenData));
        $device->setCreatedAt(time());
        $device->setUpdatedAt(time());

        return $this->deviceRepository->create($device);
    }

    private function detectDeviceType(array $tokenData): string
    {
        $name = strtolower($tokenData['name'] ?? '');

        if (strpos($name, 'desktop') !== false || strpos($name, 'mirall') !== false) {
            return 'desktop';
        }

        if (strpos($name, 'mobile') !== false || strpos($name, 'phone') !== false) {
            return 'mobile';
        }

        if (strpos($name, 'browser') !== false || strpos($name, 'web') !== false) {
            return 'web';
        }

        return 'unknown';
    }

    private function isWipeSupported(array $tokenData): bool
    {
        $name = strtolower($tokenData['name'] ?? '');

        foreach (self::WIPE_SUPPORTED_DEVICES as $supported) {
            if (strpos($name, $supported) !== false) {
                return true;
            }
        }

        return false;
    }

    public function remoteWipeUser(string $userId, ?string $deviceId = null): bool
    {
        if ($deviceId) {
            $device = $this->deviceRepository->findByUserAndDeviceId($userId, $deviceId);
            if ($device) {
                return $this->remoteWipeDevice($device->getId());
            }
            return false;
        }

        $devices = $this->getUserDevices($userId);
        $success = true;

        foreach ($devices as $device) {
            if ($device->isWipeSupported()) {
                $result = $this->remoteWipeDevice($device->getId());
                if (!$result) {
                    $success = false;
                }
            }
        }

        return $success;
    }

    public function remoteWipeDevice(int $deviceId): bool
    {
        $device = $this->deviceRepository->find($deviceId);
        if (!$device || !$device->isWipeSupported()) {
            $this->logger->warning('Device not found or wipe not supported', [
                'device_id' => $deviceId
            ]);
            return false;
        }

        $userId = $device->getUserId();
        $tokenId = $device->getTokenId();

        try {
            $device->setWipeStatus(Device::WIPE_STATUS_PENDING);
            $device->setWipeRequestedAt(time());
            $this->deviceRepository->update($device);

            $this->logger->info('Remote wipe initiated', [
                'device_id' => $deviceId,
                'user_id' => $userId,
                'token_id' => $tokenId,
                'device_name' => $device->getDeviceName()
            ]);

            $tokenDeleted = $this->tokenAdapter->deleteToken($tokenId);

            if (!$tokenDeleted) {
                $device->setWipeStatus(Device::WIPE_STATUS_FAILED);
                $this->deviceRepository->update($device);
                return false;
            }

            $this->sendWipePushNotification($userId, $device);

            $device->setWipeStatus(Device::WIPE_STATUS_COMPLETED);
            $device->setWipeCompletedAt(time());
            $this->deviceRepository->update($device);

            $this->logger->info('Remote wipe completed successfully', [
                'device_id' => $deviceId,
                'user_id' => $userId,
            ]);

            return true;

        } catch (\Exception $e) {
            $device->setWipeStatus(Device::WIPE_STATUS_FAILED);
            $this->deviceRepository->update($device);

            $this->logger->error('Remote wipe failed: ' . $e->getMessage());
            return false;
        }
    }

    private function sendWipePushNotification(string $userId, Device $device): void
    {
        try {
            $notification = $this->notificationManager->createNotification();
            $notification->setApp('adminoffboard')
                ->setUser($userId)
                ->setDateTime(new \DateTime())
                ->setObject('device', (string)$device->getId())
                ->setSubject('remote_wipe', [
                    'deviceName' => $device->getDeviceName() ?? 'Unknown device',
                    'timestamp' => time()
                ]);

            $this->notificationManager->notify($notification);
        } catch (\Exception $e) {
            $this->logger->warning('Failed to send wipe push: ' . $e->getMessage());
        }
    }

    public function isRemoteWipeSupported(string $userId, int $tokenId): bool
    {
        $device = $this->deviceRepository->findByUserAndToken($userId, $tokenId);
        return $device ? $device->isWipeSupported() : false;
    }
}

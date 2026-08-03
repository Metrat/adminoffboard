<?php

declare(strict_types=1);

namespace OCA\AdminOffboard\Driver;

use OCP\Notification\IManager as INotificationManager;
use OCP\IUserManager;

class DesktopDriver extends BaseDriver
{
    private const SUPPORTED_PLATFORMS = [
        'windows',
        'macos',
        'linux',
        'desktop'
    ];

    private const WIPE_CAPABLE_CLIENTS = [
        'nextcloud-desktop',
        'windows-desktop',
        'macos-desktop',
        'linux-desktop'
    ];

    public function __construct(
        $tokenAdapter,
        $logger,
        private INotificationManager $notificationManager,
        private IUserManager $userManager
    ) {
        parent::__construct($tokenAdapter, $logger);
    }

    public function getName(): string
    {
        return 'Desktop';
    }

    public function getPriority(): int
    {
        return 200;
    }

    public function supports(array $deviceData): bool
    {
        $name = strtolower($deviceData['name'] ?? '');

        foreach (self::SUPPORTED_PLATFORMS as $platform) {
            if (strpos($name, $platform) !== false) {
                return true;
            }
        }

        $deviceType = strtolower($deviceData['device_type'] ?? '');
        return in_array($deviceType, ['desktop', 'pc', 'workstation', 'laptop']);
    }

    public function supportsRemoteWipe(): bool
    {
        return true;
    }

    public function remoteWipe(int $tokenId, array $deviceData): bool
    {
        $this->logOperation('remote_wipe', [
            'token_id' => $tokenId,
            'device_name' => $deviceData['name'] ?? 'unknown'
        ]);

        try {
            $name = strtolower($deviceData['name'] ?? '');
            $supported = false;

            foreach (self::WIPE_CAPABLE_CLIENTS as $client) {
                if (strpos($name, $client) !== false) {
                    $supported = true;
                    break;
                }
            }

            if (!$supported) {
                $this->logger->warning('Desktop client does not support remote wipe', [
                    'device_name' => $name
                ]);
                return false;
            }

            // Удаляем токен
            $deleted = $this->tokenAdapter->deleteToken($tokenId);

            if ($deleted) {
                $this->logOperation('remote_wipe_success', [
                    'token_id' => $tokenId
                ]);

                // Для десктопа тоже отправляем уведомление
                $this->sendDesktopWipeNotification($deviceData);
            }

            return $deleted;
        } catch (\Exception $e) {
            $this->logError('remote_wipe', $e, [
                'token_id' => $tokenId
            ]);
            return false;
        }
    }

    /**
     * Отправка уведомления на десктоп
     */
    private function sendDesktopWipeNotification(array $deviceData): void
    {
        try {
            $userId = $deviceData['user_id'] ?? null;
            if (!$userId) {
                return;
            }

            $user = $this->userManager->get($userId);
            if (!$user) {
                return;
            }

            $notification = $this->notificationManager->createNotification();
            $notification->setApp('adminoffboard')
                ->setUser($userId)
                ->setDateTime(new \DateTime())
                ->setObject('device', (string)($deviceData['id'] ?? 'unknown'))
                ->setSubject('remote_wipe_desktop', [
                    'deviceName' => $deviceData['name'] ?? 'Unknown device',
                    'timestamp' => time()
                ])
                ->setMessage('remote_wipe_desktop_message', [
                    'deviceName' => $deviceData['name'] ?? 'Unknown device'
                ]);

            $this->notificationManager->notify($notification);

            $this->logger->info('Desktop wipe notification sent', [
                'user_id' => $userId,
                'device_name' => $deviceData['name'] ?? 'unknown'
            ]);

        } catch (\Exception $e) {
            $this->logger->warning('Failed to send desktop wipe notification', [
                'error' => $e->getMessage()
            ]);
        }
    }

    public function getDeviceInfo(array $deviceData): array
    {
        $info = [
            'type' => 'desktop',
            'platform' => 'unknown',
            'version' => 'unknown',
            'name' => $deviceData['name'] ?? 'Unknown Desktop',
            'last_activity' => $this->getLastActivity($deviceData),
            'is_active' => $this->isActive($deviceData),
        ];

        $name = strtolower($deviceData['name'] ?? '');
        foreach (self::SUPPORTED_PLATFORMS as $platform) {
            if (strpos($name, $platform) !== false) {
                $info['platform'] = $platform;
                break;
            }
        }

        if (preg_match('/v?(\d+\.\d+\.\d+)/i', $name, $matches)) {
            $info['version'] = $matches[1];
        }

        return $info;
    }

    public function validateDeviceData(array $deviceData): bool
    {
        return parent::validateDeviceData($deviceData) &&
            isset($deviceData['device_type']) &&
            $deviceData['device_type'] === 'desktop';
    }
}

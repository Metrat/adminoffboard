<?php

declare(strict_types=1);

namespace OCA\AdminOffboard\Driver;

use OCP\Notification\IManager as INotificationManager;
use OCP\IUserManager;

class MobileDriver extends BaseDriver
{
    private const MOBILE_PLATFORMS = [
        'android',
        'ios',
        'iphone',
        'ipad',
        'mobile'
    ];

    private const WIPE_CAPABLE_CLIENTS = [
        'nextcloud-mobile',
        'nextcloud-android',
        'nextcloud-ios',
        'mobile-app'
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
        return 'Mobile';
    }

    public function getPriority(): int
    {
        return 300;
    }

    public function supports(array $deviceData): bool
    {
        $name = strtolower($deviceData['name'] ?? '');

        foreach (self::MOBILE_PLATFORMS as $platform) {
            if (strpos($name, $platform) !== false) {
                return true;
            }
        }

        $deviceType = strtolower($deviceData['device_type'] ?? '');
        return in_array($deviceType, ['mobile', 'phone', 'tablet']);
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
                $this->logger->warning('Mobile client does not support remote wipe', [
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

                // Отправляем push для немедленного вайпа
                $this->sendMobileWipePush($deviceData);
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
     * Отправка push-уведомления на мобильное устройство
     */
    private function sendMobileWipePush(array $deviceData): void
    {
        try {
            $userId = $deviceData['user_id'] ?? null;
            if (!$userId) {
                $this->logger->warning('No user_id in device data for push');
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
                ->setSubject('remote_wipe_mobile', [
                    'deviceName' => $deviceData['name'] ?? 'Unknown device',
                    'timestamp' => time()
                ])
                ->setMessage('remote_wipe_mobile_message', [
                    'deviceName' => $deviceData['name'] ?? 'Unknown device'
                ]);

            $this->notificationManager->notify($notification);

            $this->logger->info('Mobile wipe push notification sent', [
                'user_id' => $userId,
                'device_name' => $deviceData['name'] ?? 'unknown'
            ]);

        } catch (\Exception $e) {
            // Push не критичен
            $this->logger->warning('Failed to send mobile wipe push', [
                'error' => $e->getMessage(),
                'device_name' => $deviceData['name'] ?? 'unknown'
            ]);
        }
    }

    public function getDeviceInfo(array $deviceData): array
    {
        $info = [
            'type' => 'mobile',
            'platform' => 'unknown',
            'model' => 'unknown',
            'name' => $deviceData['name'] ?? 'Unknown Mobile',
            'last_activity' => $this->getLastActivity($deviceData),
            'is_active' => $this->isActive($deviceData),
        ];

        $name = strtolower($deviceData['name'] ?? '');
        foreach (self::MOBILE_PLATFORMS as $platform) {
            if (strpos($name, $platform) !== false) {
                $info['platform'] = $platform;
                break;
            }
        }

        if (preg_match('/(iphone|ipad|galaxy|pixel|oneplus)/i', $name, $matches)) {
            $info['model'] = $matches[1];
        }

        return $info;
    }

    public function validateDeviceData(array $deviceData): bool
    {
        return parent::validateDeviceData($deviceData) &&
            isset($deviceData['device_type']) &&
            in_array($deviceData['device_type'], ['mobile', 'phone', 'tablet']);
    }
}

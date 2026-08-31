<?php

declare(strict_types=1);

namespace OCA\AdminOffboard\Notification;

use OCP\Notification\INotification;
use OCP\Notification\INotifier;
use OCP\Notification\UnknownNotificationException;
use OCP\IL10N;

class Notifier implements INotifier
{
    public function __construct(
        private IL10N $l
    ) {
    }

    public function getID(): string
    {
        return 'adminoffboard';
    }

    public function getName(): string
    {
        return $this->l->t('Admin Offboard');
    }

    public function prepare(INotification $notification, string $languageCode): INotification
    {
        if ($notification->getApp() !== 'adminoffboard') {
            throw new UnknownNotificationException('Notification is not from adminoffboard');
        }

        switch ($notification->getSubject()) {
            case 'remote_wipe':
            case 'remote_wipe_mobile':
            case 'remote_wipe_desktop':
                $parameters = $notification->getSubjectParameters();
                $deviceName = $parameters['deviceName'] ?? $this->l->t('Unknown device');

                $notification->setParsedSubject(
                    $this->l->t('Remote wipe initiated for %s', [$deviceName])
                );
                $notification->setParsedMessage(
                    $this->l->t('Your device "%s" has been remotely wiped by an administrator. All local data will be removed on next sync.', [$deviceName])
                );
                break;

            default:
                throw new UnknownNotificationException('Unknown subject: ' . $notification->getSubject());
        }

        return $notification;
    }
}

<?php

declare(strict_types=1);

namespace OCA\AdminOffboard\Driver;

use OCA\AdminOffboard\Adapter\TokenAdapter;
use OCA\AdminOffboard\Driver\Exception\DriverNotFoundException;
use OCA\AdminOffboard\Logger\AppLogger;
use OCP\Notification\IManager as INotificationManager;
use OCP\IUserManager;

class DriverFactory
{
    private DriverRegistry $registry;

    public function __construct(
        private TokenAdapter $tokenAdapter,
        private AppLogger $logger,
        private INotificationManager $notificationManager,
        private IUserManager $userManager
    ) {
        $this->initializeRegistry();
    }

    private function initializeRegistry(): void
    {
        $this->registry = new DriverRegistry($this->logger);

        $this->registry->registerDrivers([
            new DesktopDriver(
                $this->tokenAdapter,
                $this->logger,
                $this->notificationManager,
                $this->userManager
            ),
            new MobileDriver(
                $this->tokenAdapter,
                $this->logger,
                $this->notificationManager,
                $this->userManager
            ),
            new WebDriver($this->tokenAdapter, $this->logger),
            new UnknownDriver($this->tokenAdapter, $this->logger),
        ]);

        $this->logger->info('Driver registry initialized', [
            'drivers' => $this->registry->getDriverNames()
        ]);
    }

    public function getRegistry(): DriverRegistry
    {
        return $this->registry;
    }

    public function getDriver(array $deviceData): DriverInterface
    {
        $driver = $this->registry->findDriver($deviceData);

        if ($driver === null) {
            $driver = $this->registry->getDriverByName('Unknown');
        }

        return $driver;
    }

    public function getDriverByName(string $name): DriverInterface
    {
        $driver = $this->registry->getDriverByName($name);

        if ($driver === null) {
            throw new DriverNotFoundException("Driver not found: $name");
        }

        return $driver;
    }

    public function supportsRemoteWipe(array $deviceData): bool
    {
        $driver = $this->getDriver($deviceData);
        return $driver->supportsRemoteWipe();
    }

    public function remoteWipe(array $deviceData): bool
    {
        $driver = $this->getDriver($deviceData);
        $tokenId = (int)($deviceData['id'] ?? 0);

        if (!$driver->supportsRemoteWipe()) {
            $this->logger->warning('Remote wipe not supported by driver', [
                'driver' => $driver->getName(),
                'device_name' => $deviceData['name'] ?? 'unknown'
            ]);
            return false;
        }

        return $driver->remoteWipe($tokenId, $deviceData);
    }

    public function getDeviceInfo(array $deviceData): array
    {
        $driver = $this->getDriver($deviceData);
        return $driver->getDeviceInfo($deviceData);
    }

    public function getCapabilities(): array
    {
        return $this->registry->getCapabilitiesSummary();
    }

    public function getRemoteWipeDrivers(): array
    {
        return $this->registry->getRemoteWipeDrivers();
    }
}

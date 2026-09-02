<?php

declare(strict_types=1);

namespace OCA\AdminOffboard\Tests;

use OCA\AdminOffboard\Adapter\DeviceAdapter;
use OCA\AdminOffboard\Db\Repository\DeviceRepository;
use OCA\AdminOffboard\Db\Mapper\DeviceMapper;
use OCA\AdminOffboard\Adapter\TokenAdapter;
use PHPUnit\Framework\TestCase;

class DeviceAdapterTest extends TestCase
{
    private DeviceAdapter $deviceAdapter;

    protected function setUp(): void
    {
        $db = \OCP\Server::get(\OCP\IDBConnection::class);

        $deviceMapper = new DeviceMapper($db);
        $deviceRepository = new DeviceRepository($deviceMapper);

        $tokenAdapter = new TokenAdapter(
            \OCP\Server::get(\OCP\Security\ISecureRandom::class),
            $db
        );

        $this->deviceAdapter = new DeviceAdapter(
            $deviceRepository,
            $tokenAdapter,
            \OCP\Server::get(\OCP\Notification\IManager::class),
            \OCP\Server::get(\OCP\IUserManager::class),
            new \Psr\Log\NullLogger()
        );
    }

    public function testGetUserDevices(): void
    {
        $devices = $this->deviceAdapter->getUserDevices('nonexistent_user_123');
        $this->assertIsArray($devices);
    }

    public function testSyncUserDevices(): void
    {
        $devices = $this->deviceAdapter->syncUserDevices('nonexistent_user_123');
        $this->assertIsArray($devices);
        $this->assertEmpty($devices);
    }
}

<?php

declare(strict_types=1);

namespace OCA\AdminOffboard\Tests;

use OCA\AdminOffboard\Configuration\AppConfig;
use PHPUnit\Framework\TestCase;

class AppConfigTest extends TestCase
{
    private AppConfig $config;

    protected function setUp(): void
    {
        $this->config = new AppConfig(
            \OCP\Server::get(\OCP\IConfig::class),
            'adminoffboard'
        );
    }

    public function testAppId(): void
    {
        $this->assertEquals('adminoffboard', $this->config->getAppId());
    }

    public function testDefaultConfig(): void
    {
        $this->assertNotNull($this->config->getAppVersion());
        $this->assertIsString($this->config->getAppVersion());
    }
}

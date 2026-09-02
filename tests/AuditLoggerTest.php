<?php

declare(strict_types=1);

namespace OCA\AdminOffboard\Tests;

use OCA\AdminOffboard\Audit\AuditLogger;
use OCA\AdminOffboard\Db\Repository\AuditLogRepository;
use OCA\AdminOffboard\Db\Mapper\AuditLogMapper;
use OCA\AdminOffboard\Logger\AppLogger;
use PHPUnit\Framework\TestCase;

class AuditLoggerTest extends TestCase
{
    private AuditLogger $auditLogger;

    protected function setUp(): void
    {
        $db = \OCP\Server::get(\OCP\IDBConnection::class);
        $mapper = new AuditLogMapper($db);
        $repository = new AuditLogRepository($mapper);
        $logger = new AppLogger('adminoffboard_test');

        $this->auditLogger = new AuditLogger($repository, $logger);
    }

    public function testLog(): void
    {
        $log = $this->auditLogger->log(
            AuditLogger::ACTION_OFFBOARD,
            'test_user',
            'test_actor',
            ['test' => true],
            AuditLogger::STATUS_SUCCESS
        );

        $this->assertNotNull($log->getId());
        $this->assertEquals('test_user', $log->getUserId());
        $this->assertEquals(AuditLogger::ACTION_OFFBOARD, $log->getAction());
    }
}

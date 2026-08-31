<?php

declare(strict_types=1);

namespace OCA\AdminOffboard\Tests;

use OCA\AdminOffboard\Queue\JobQueue;
use OCA\AdminOffboard\Db\Repository\JobRepository;
use OCA\AdminOffboard\Db\Mapper\JobMapper;
use OCA\AdminOffboard\Logger\AppLogger;
use PHPUnit\Framework\TestCase;

class JobQueueTest extends TestCase
{
    private JobQueue $jobQueue;

    protected function setUp(): void
    {
        $db = \OCP\Server::get(\OCP\IDBConnection::class);
        $jobMapper = new JobMapper($db);
        $jobRepository = new JobRepository($jobMapper);
        $logger = new AppLogger('adminoffboard_test');

        $this->jobQueue = new JobQueue($jobRepository, $logger);
    }

    public function testGetStats(): void
    {
        $stats = $this->jobQueue->getStats();
        $this->assertIsArray($stats);
    }

    public function testGetNextJob(): void
    {
        $job = $this->jobQueue->getNextJob();
        $this->assertNull($job);
    }
}

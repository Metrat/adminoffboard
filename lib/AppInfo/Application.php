<?php

declare(strict_types=1);

namespace OCA\AdminOffboard\AppInfo;

use OCA\AdminOffboard\Adapter\DeviceAdapter;
use OCA\AdminOffboard\Adapter\NextcloudAdapter;
use OCA\AdminOffboard\Adapter\TokenAdapter;
use OCA\AdminOffboard\Adapter\UserAdapter;
use OCA\AdminOffboard\Audit\AuditLogger;
use OCA\AdminOffboard\Configuration\AppConfig;
use OCA\AdminOffboard\Db\Mapper\AuditLogMapper;
use OCA\AdminOffboard\Db\Mapper\DeviceMapper;
use OCA\AdminOffboard\Db\Mapper\JobMapper;
use OCA\AdminOffboard\Db\Repository\AuditLogRepository;
use OCA\AdminOffboard\Db\Repository\DeviceRepository;
use OCA\AdminOffboard\Db\Repository\JobRepository;
use OCA\AdminOffboard\Driver\DriverFactory;
use OCA\AdminOffboard\Logger\LoggerFactory;
use OCA\AdminOffboard\Notification\Notifier;
use OCA\AdminOffboard\Queue\JobProcessor;
use OCA\AdminOffboard\Queue\JobQueue;
use OCA\AdminOffboard\Queue\QueueManager;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Security\ISecureRandom;
use OCP\Notification\IManager as INotificationManager;
use Psr\Log\LoggerInterface;

class Application extends App implements IBootstrap
{
    public const APP_ID = 'adminoffboard';

    public function __construct()
    {
        parent::__construct(self::APP_ID);
    }

    public function register(IRegistrationContext $context): void
    {
        // Register Notifier
        $context->registerNotifierService(Notifier::class);

        // Configuration
        $context->registerService(AppConfig::class, function($c) {
            return new AppConfig(
                $c->get(IConfig::class),
                self::APP_ID
            );
        });

        // Database mappers
        $context->registerService(JobMapper::class, function($c) {
            return new JobMapper(
                $c->get(IDBConnection::class)
            );
        });

        $context->registerService(AuditLogMapper::class, function($c) {
            return new AuditLogMapper(
                $c->get(IDBConnection::class)
            );
        });

        $context->registerService(DeviceMapper::class, function($c) {
            return new DeviceMapper(
                $c->get(IDBConnection::class)
            );
        });

        // Repositories
        $context->registerService(JobRepository::class, function($c) {
            return new JobRepository(
                $c->get(JobMapper::class)
            );
        });

        $context->registerService(AuditLogRepository::class, function($c) {
            return new AuditLogRepository(
                $c->get(AuditLogMapper::class)
            );
        });

        $context->registerService(DeviceRepository::class, function($c) {
            return new DeviceRepository(
                $c->get(DeviceMapper::class)
            );
        });

        // Adapters
        $context->registerService(UserAdapter::class, function($c) {
            return new UserAdapter(
                $c->get(IUserManager::class),
                $c->get(IUserSession::class)
            );
        });

        $context->registerService(TokenAdapter::class, function($c) {
            return new TokenAdapter(
                $c->get(ISecureRandom::class),
                $c->get(IDBConnection::class)
            );
        });

        $context->registerService(DeviceAdapter::class, function($c) {
            return new DeviceAdapter(
                $c->get(DeviceRepository::class),
                $c->get(TokenAdapter::class),
                $c->get(INotificationManager::class),
                $c->get(IUserManager::class),
                $c->get(LoggerFactory::class)->getLogger()
            );
        });

        $context->registerService(NextcloudAdapter::class, function($c) {
            return new NextcloudAdapter(
                $c->get(UserAdapter::class),
                $c->get(TokenAdapter::class),
                $c->get(DeviceAdapter::class)
            );
        });

        // Logger
        $context->registerService(LoggerFactory::class, function($c) {
            return new LoggerFactory(self::APP_ID);
        });

        // Audit
        $context->registerService(AuditLogger::class, function($c) {
            return new AuditLogger(
                $c->get(AuditLogRepository::class),
                $c->get(LoggerFactory::class)->getLogger()
            );
        });

        // Driver
        $context->registerService(DriverFactory::class, function($c) {
            return new DriverFactory(
                $c->get(TokenAdapter::class),
                $c->get(LoggerFactory::class)->getLogger(),
                $c->get(INotificationManager::class),
                $c->get(IUserManager::class)
            );
        });

        // Queue
        $context->registerService(JobQueue::class, function($c) {
            return new JobQueue(
                $c->get(JobRepository::class),
                $c->get(LoggerFactory::class)->getLogger()
            );
        });

        $context->registerService(QueueManager::class, function($c) {
            return new QueueManager(
                $c->get(JobQueue::class),
                $c->get(JobProcessor::class),
                $c->get(AppConfig::class),
                $c->get(LoggerFactory::class)->getLogger()
            );
        });

        $context->registerService(JobProcessor::class, function($c) {
            return new JobProcessor(
                $c->get(JobRepository::class),
                $c->get(AuditLogger::class),
                $c->get(LoggerFactory::class)->getLogger(),
                $c->get(NextcloudAdapter::class)
            );
        });
    }

    public function boot(IBootContext $context): void
    {
        $context->getAppContainer()->query(AppConfig::class)->init();
    }
}

<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Metrat <disparam@gmail.com>
 *
 * @author Metrat <disparam@gmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

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
use OCA\AdminOffboard\Logger\LoggerFactory;
use OCA\AdminOffboard\Queue\JobProcessor;
use OCA\AdminOffboard\Queue\JobQueue;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\DB\Connection;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Security\ISecureRandom;

/**
 * Main application class
 */
class Application extends App implements IBootstrap
{
    public const APP_ID = 'adminoffboard';

    public function __construct()
    {
        parent::__construct(self::APP_ID);
    }

    /**
     * Register all services
     */
    public function register(IRegistrationContext $context): void
    {
        // Register configuration
        $context->registerService(AppConfig::class, function($c) {
            return new AppConfig(
                $c->get(IConfig::class),
                self::APP_ID
            );
        });

        // Register database mappings
        $context->registerService(JobMapper::class, function($c) {
            return new JobMapper(
                $c->get(IDBConnection::class),
                $c->get(Connection::class)
            );
        });

        $context->registerService(AuditLogMapper::class, function($c) {
            return new AuditLogMapper(
                $c->get(IDBConnection::class),
                $c->get(Connection::class)
            );
        });

        $context->registerService(DeviceMapper::class, function($c) {
            return new DeviceMapper(
                $c->get(IDBConnection::class),
                $c->get(Connection::class)
            );
        });

        // Register repositories
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

        // Register adapters
        $context->registerService(UserAdapter::class, function($c) {
            return new UserAdapter(
                $c->get(IUserManager::class),
                $c->get(IUserSession::class)
            );
        });

        $context->registerService(TokenAdapter::class, function($c) {
            return new TokenAdapter(
                $c->get(ISecureRandom::class)
            );
        });

        $context->registerService(DeviceAdapter::class, function($c) {
            return new DeviceAdapter(
                $c->get(DeviceRepository::class),
                $c->get(TokenAdapter::class)
            );
        });

        $context->registerService(NextcloudAdapter::class, function($c) {
            return new NextcloudAdapter(
                $c->get(UserAdapter::class),
                $c->get(TokenAdapter::class),
                $c->get(DeviceAdapter::class)
            );
        });

        // Register logger
        $context->registerService(LoggerFactory::class, function($c) {
            return new LoggerFactory(self::APP_ID);
        });

        $context->registerService(AppLogger::class, function($c) {
            return $c->get(LoggerFactory::class)->getLogger();
        });

        // Register audit logger
        $context->registerService(AuditLogger::class, function($c) {
            return new AuditLogger(
                $c->get(AuditLogRepository::class),
                $c->get(AppLogger::class)
            );
        });

        // Register queue services
        $context->registerService(JobQueue::class, function($c) {
            return new JobQueue(
                $c->get(JobRepository::class),
                $c->get(AppLogger::class)
            );
        });

        $context->registerService(JobProcessor::class, function($c) {
            return new JobProcessor(
                $c->get(JobRepository::class),
                $c->get(AuditLogger::class),
                $c->get(AppLogger::class),
                $c->get(NextcloudAdapter::class)
            );
        });

        // Register commands
        $context->registerCommand(Command\OffboardUser::class);
        $context->registerCommand(Command\DisableUsers::class);
        $context->registerCommand(Command\DeleteTokens::class);
        $context->registerCommand(Command\RemoteWipe::class);
        $context->registerCommand(Command\ProcessQueue::class);
        $context->registerCommand(Command\AuditLog::class);
        $context->registerCommand(Command\CleanupAuditLogs::class);
        $context->registerCommand(Command\ListDevices::class);

        // Register background jobs
        $context->registerBackgroundJob(\OCA\AdminOffboard\BackgroundJob\ProcessQueueJob::class);
        $context->registerBackgroundJob(\OCA\AdminOffboard\BackgroundJob\CleanupAuditLogJob::class);

        // Register settings
        $context->registerAdminSetting(\OCA\AdminOffboard\Settings\AdminSettings::class);
        $context->registerAdminSection(\OCA\AdminOffboard\Settings\AdminSection::class);

        // Register activity providers
        $context->registerActivityFilter(\OCA\AdminOffboard\Activity\Filter::class);
        $context->registerActivitySetting(\OCA\AdminOffboard\Activity\Setting::class);
        $context->registerActivityProvider(\OCA\AdminOffboard\Activity\Provider::class);
    }

    /**
     * Boot the application
     */
    public function boot(IBootContext $context): void
    {
        // Initialize application
        $context->getAppContainer()->query(AppConfig::class)->init();
    }
}
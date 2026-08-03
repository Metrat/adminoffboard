<?php

declare(strict_types=1);

namespace OCA\AdminOffboard\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version0001Date20260803 extends SimpleMigrationStep
{
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper
    {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        // Таблица audit
        if (!$schema->hasTable('adminoffboard_audit')) {
            $table = $schema->createTable('adminoffboard_audit');
            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
            ]);
            $table->addColumn('action', 'string', [
                'notnull' => true,
                'length' => 64,
            ]);
            $table->addColumn('user_id', 'string', [
                'notnull' => true,
                'length' => 64,
            ]);
            $table->addColumn('actor', 'string', [
                'notnull' => true,
                'length' => 64,
            ]);
            $table->addColumn('target', 'string', [
                'notnull' => false,
                'length' => 255,
            ]);
            $table->addColumn('details', 'text', [
                'notnull' => false,
            ]);
            $table->addColumn('status', 'string', [
                'notnull' => true,
                'length' => 32,
                'default' => 'success',
            ]);
            $table->addColumn('ip_address', 'string', [
                'notnull' => false,
                'length' => 45,
            ]);
            $table->addColumn('user_agent', 'string', [
                'notnull' => false,
                'length' => 255,
            ]);
            $table->addColumn('timestamp', 'integer', [
                'notnull' => true,
            ]);
            $table->setPrimaryKey(['id']);
            $table->addIndex(['user_id'], 'adminoffboard_audit_user_idx');
            $table->addIndex(['action'], 'adminoffboard_audit_action_idx');
        }

        // Таблица devices
        if (!$schema->hasTable('adminoffboard_devices')) {
            $table = $schema->createTable('adminoffboard_devices');
            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
            ]);
            $table->addColumn('user_id', 'string', [
                'notnull' => true,
                'length' => 64,
            ]);
            $table->addColumn('token_id', 'integer', [
                'notnull' => true,
            ]);
            $table->addColumn('device_type', 'string', [
                'notnull' => false,
                'length' => 32,
            ]);
            $table->addColumn('device_name', 'string', [
                'notnull' => false,
                'length' => 255,
            ]);
            $table->addColumn('last_activity', 'integer', [
                'notnull' => false,
            ]);
            $table->addColumn('wipe_supported', 'boolean', [
                'notnull' => true,
                'default' => false,
            ]);
            $table->addColumn('created_at', 'integer', [
                'notnull' => true,
            ]);
            $table->addColumn('updated_at', 'integer', [
                'notnull' => true,
            ]);
            $table->setPrimaryKey(['id']);
            $table->addIndex(['user_id'], 'adminoffboard_devices_user_idx');
            $table->addIndex(['token_id'], 'adminoffboard_devices_token_idx');
        }

        // Таблица jobs
        if (!$schema->hasTable('adminoffboard_jobs')) {
            $table = $schema->createTable('adminoffboard_jobs');
            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
            ]);
            $table->addColumn('job_type', 'string', [
                'notnull' => true,
                'length' => 32,
            ]);
            $table->addColumn('status', 'string', [
                'notnull' => true,
                'length' => 32,
                'default' => 'pending',
            ]);
            $table->addColumn('payload', 'text', [
                'notnull' => false,
            ]);
            $table->addColumn('user_id', 'string', [
                'notnull' => false,
                'length' => 64,
            ]);
            $table->addColumn('created_by', 'string', [
                'notnull' => false,
                'length' => 64,
            ]);
            $table->addColumn('created_at', 'integer', [
                'notnull' => true,
            ]);
            $table->addColumn('started_at', 'integer', [
                'notnull' => false,
            ]);
            $table->addColumn('completed_at', 'integer', [
                'notnull' => false,
            ]);
            $table->addColumn('attempts', 'integer', [
                'notnull' => true,
                'default' => 0,
            ]);
            $table->addColumn('max_attempts', 'integer', [
                'notnull' => true,
                'default' => 3,
            ]);
            $table->addColumn('error_message', 'string', [
                'notnull' => false,
                'length' => 255,
            ]);
            $table->addColumn('priority', 'integer', [
                'notnull' => true,
                'default' => 0,
            ]);
            $table->setPrimaryKey(['id']);
            $table->addIndex(['status'], 'adminoffboard_jobs_status_idx');
            $table->addIndex(['user_id'], 'adminoffboard_jobs_user_idx');
        }

        return $schema;
    }
}

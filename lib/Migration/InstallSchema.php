<?php

declare(strict_types=1);

namespace OCA\AdminOffboard\Migration;

use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCP\IDBConnection;

class InstallSchema implements IRepairStep
{
    public function __construct(
        private IDBConnection $db
    ) {
    }

    public function getName(): string
    {
        return 'Install AdminOffboard database tables';
    }

    public function run(IOutput $output): void
    {
        $schema = $this->db->createSchema();
        $schemaChanged = false;

        // Таблица audit
        if (!$schema->hasTable('adminoffboard_audit')) {
            $table = $schema->createTable('adminoffboard_audit');
            $table->addColumn('id', 'integer', ['autoincrement' => true, 'notnull' => true]);
            $table->addColumn('action', 'string', ['notnull' => true, 'length' => 64]);
            $table->addColumn('user_id', 'string', ['notnull' => true, 'length' => 64]);
            $table->addColumn('actor', 'string', ['notnull' => true, 'length' => 64]);
            $table->addColumn('target', 'string', ['notnull' => false, 'length' => 255]);
            $table->addColumn('details', 'text', ['notnull' => false]);
            $table->addColumn('status', 'string', ['notnull' => true, 'length' => 32, 'default' => 'success']);
            $table->addColumn('ip_address', 'string', ['notnull' => false, 'length' => 45]);
            $table->addColumn('user_agent', 'string', ['notnull' => false, 'length' => 255]);
            $table->addColumn('timestamp', 'integer', ['notnull' => true]);
            $table->setPrimaryKey(['id']);
            $table->addIndex(['user_id']);
            $table->addIndex(['action']);
            $schemaChanged = true;
            $output->info('Created table: adminoffboard_audit');
        }

        // Таблица devices
        if (!$schema->hasTable('adminoffboard_devices')) {
            $table = $schema->createTable('adminoffboard_devices');
            $table->addColumn('id', 'integer', ['autoincrement' => true, 'notnull' => true]);
            $table->addColumn('user_id', 'string', ['notnull' => true, 'length' => 64]);
            $table->addColumn('token_id', 'integer', ['notnull' => true]);
            $table->addColumn('device_type', 'string', ['notnull' => false, 'length' => 32]);
            $table->addColumn('device_name', 'string', ['notnull' => false, 'length' => 255]);
            $table->addColumn('last_activity', 'integer', ['notnull' => false]);
            $table->addColumn('wipe_supported', 'boolean', ['notnull' => true, 'default' => false]);
            $table->addColumn('created_at', 'integer', ['notnull' => true]);
            $table->addColumn('updated_at', 'integer', ['notnull' => true]);
            $table->setPrimaryKey(['id']);
            $table->addIndex(['user_id']);
            $table->addIndex(['token_id']);
            $schemaChanged = true;
            $output->info('Created table: adminoffboard_devices');
        }

        // Таблица jobs
        if (!$schema->hasTable('adminoffboard_jobs')) {
            $table = $schema->createTable('adminoffboard_jobs');
            $table->addColumn('id', 'integer', ['autoincrement' => true, 'notnull' => true]);
            $table->addColumn('job_type', 'string', ['notnull' => true, 'length' => 32]);
            $table->addColumn('status', 'string', ['notnull' => true, 'length' => 32, 'default' => 'pending']);
            $table->addColumn('payload', 'text', ['notnull' => false]);
            $table->addColumn('user_id', 'string', ['notnull' => false, 'length' => 64]);
            $table->addColumn('created_by', 'string', ['notnull' => false, 'length' => 64]);
            $table->addColumn('created_at', 'integer', ['notnull' => true]);
            $table->addColumn('started_at', 'integer', ['notnull' => false]);
            $table->addColumn('completed_at', 'integer', ['notnull' => false]);
            $table->addColumn('attempts', 'integer', ['notnull' => true, 'default' => 0]);
            $table->addColumn('max_attempts', 'integer', ['notnull' => true, 'default' => 3]);
            $table->addColumn('error_message', 'string', ['notnull' => false, 'length' => 255]);
            $table->addColumn('priority', 'integer', ['notnull' => true, 'default' => 0]);
            $table->setPrimaryKey(['id']);
            $table->addIndex(['status']);
            $table->addIndex(['user_id']);
            $schemaChanged = true;
            $output->info('Created table: adminoffboard_jobs');
        }

        if ($schemaChanged) {
            $this->db->migrateToSchema($schema);
            $output->info('Database schema updated successfully');
        } else {
            $output->info('All tables already exist');
        }
    }
}

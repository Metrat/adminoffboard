<?php

declare(strict_types=1);

namespace OCA\AdminOffboard\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version0002Date20260803 extends SimpleMigrationStep
{
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper
    {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        $table = $schema->getTable('adminoffboard_devices');

        if (!$table->hasColumn('wipe_status')) {
            $table->addColumn('wipe_status', 'string', [
                'notnull' => false,
                'length' => 32,
                'default' => null,
            ]);
        }

        if (!$table->hasColumn('wipe_requested_at')) {
            $table->addColumn('wipe_requested_at', 'integer', [
                'notnull' => false,
                'length' => 4,
                'default' => null,
            ]);
        }

        if (!$table->hasColumn('wipe_completed_at')) {
            $table->addColumn('wipe_completed_at', 'integer', [
                'notnull' => false,
                'length' => 4,
                'default' => null,
            ]);
        }

        return $schema;
    }
}

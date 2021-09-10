<?php

declare(strict_types=1);

namespace OCA\Carnet\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version002401Date20210711195249 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('carnet_metadata')) {
			$table = $schema->createTable('carnet_metadata');
			$table->addColumn('path', 'string', [
				'notnull' => true,
				'length' => 300,
			]);
			$table->addColumn('metadata', 'string', [
				'notnull' => false,
				'length' => 10000000,
			]);
			$table->addColumn('last_modification_file', 'integer', [
				'notnull' => false,
			]);
			$table->addColumn('low_case_text', 'string', [
				'notnull' => false,
				'length' => 10000000,
			]);
			$table->addUniqueIndex(['path'], 'indexpath');
		}
		return $schema;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}
}

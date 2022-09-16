<?php

declare(strict_types=1);

namespace StepUpDream\DreamAbility\Database\Console\Migrations\Basic;

use Illuminate\Database\ConnectionResolverInterface as Resolver;
use StepUpDream\DreamAbility\Database\Migrations\Basic\MigrationRepository;
use StepUpDream\DreamAbilitySupport\Console\BaseCommand;

class InstallCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dream-ability-migrate:install-basic {name : The name of the migration}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the migration repository';

    /**
     * Create a new console command instance.
     */
    public function __construct(
        protected Resolver $resolver
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @see \StepUpDream\DreamAbility\Database\Console\Migrations\Basic\MigrateCommand::prepareDatabase
     */
    public function handle(): int
    {
        $versionControlTableName = config('stepupdream.migration.basic.version_control_table_name');
        $this->createMigrationRepository($versionControlTableName);

        $this->components->info('Migration table created successfully.');

        return 0;
    }

    /**
     * Prepare tables for migration management.
     *
     * @param  string  $tableName
     * @return void
     */
    private function createMigrationRepository(string $tableName): void
    {
        $connectionName = $this->input->getArgument('name');
        $migrationRepository = new MigrationRepository($connectionName, $this->resolver, $tableName);
        $migrationRepository->createRepository();
    }
}

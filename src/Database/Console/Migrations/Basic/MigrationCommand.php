<?php

declare(strict_types=1);

namespace StepUpDream\DreamAbility\Database\Console\Migrations\Basic;

use Illuminate\Support\Facades\File;
use LogicException;
use StepUpDream\DreamAbility\Database\Console\Migrations\BaseMigrationCommand;

class MigrationCommand extends BaseMigrationCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dream-ability:migration-basic {--production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'run migration';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->confirmToProceed();

        $definitionDatabaseDirectoryPath = config('stepupdream.migration.definition_database_directory_path');
        $migrationDirectoryParentPath = config('stepupdream.migration.migration_directory_parent_path');

        if ($this->fileOperation->isSameFileNameExist($definitionDatabaseDirectoryPath)) {
            throw new LogicException('Avoid naming tables the same. : '.$definitionDatabaseDirectoryPath);
        }

        $migrationDirectories = File::directories($migrationDirectoryParentPath);
        $migrationDirectories = collect($migrationDirectories)->sort()->all();
        foreach ($migrationDirectories as $migrationDirectoryPath) {
            $this->migrator->setOutput($this->output)->migration($migrationDirectoryPath);
        }

        $this->commandDetailLog('command run detail');
    }
}

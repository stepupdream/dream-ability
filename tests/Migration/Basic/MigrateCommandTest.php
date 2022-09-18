<?php

declare(strict_types=1);

namespace StepUpDream\DreamAbility\Test\Migration\Basic;

use Carbon\Carbon;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\Config;
use Mockery;
use StepUpDream\DreamAbility\Database\Migrations\Basic\MigrationRepositoryInterface;
use StepUpDream\DreamAbility\Database\Migrations\Basic\Migrator;
use StepUpDream\DreamAbility\Test\Migration\Basic\MockRepository\MigrationRepositoryMock;
use StepUpDream\DreamAbility\Test\TestCase;
use StepUpDream\DreamAbilitySupport\Supports\File\FileOperation;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class MigrateCommandTest extends TestCase
{
    /**
     * @var string
     */
    protected string $time = '2017-01-15 12:30:15';

    /**
     * @test
     * @dataProvider dataProviderMigration
     */
    public function migrate(array $startData, array $endData): void
    {
        // bind
        $migrationRepositoryMock = new MigrationRepositoryMock($startData);
        $this->app->bind(MigrationRepositoryInterface::class, function () use ($migrationRepositoryMock) {
            return $migrationRepositoryMock;
        });

        // run
        $this->commandRun();

        // assert
        static::assertEquals($migrationRepositoryMock->get(), $endData);
    }

    /**
     * dataProvider.
     *
     * @return string[]
     */
    public function dataProviderMigration(): array
    {
        return [
            1 => [
                'start' => [],
                'end'   => [
                    [
                        'table_name' => 'users',
                        'version'    => '1_0_0_0',
                        'created_at' => $this->time,
                    ],
                    [
                        'table_name' => 'password_resets',
                        'version'    => '1_0_0_0',
                        'created_at' => $this->time,
                    ],
                    [
                        'table_name' => 'password_resets',
                        'version'    => '1_0_1_0',
                        'created_at' => $this->time,
                    ],
                ],
            ],
            2 => [
                'start' => [
                    [
                        'table_name' => 'users',
                        'version'    => '1_0_0_0',
                        'created_at' => $this->time,
                    ],
                    [
                        'table_name' => 'password_resets',
                        'version'    => '1_0_0_0',
                        'created_at' => $this->time,
                    ],
                ],
                'end'   => [
                    [
                        'table_name' => 'users',
                        'version'    => '1_0_0_0',
                        'created_at' => $this->time,
                    ],
                    [
                        'table_name' => 'password_resets',
                        'version'    => '1_0_0_0',
                        'created_at' => $this->time,
                    ],
                    [
                        'table_name' => 'password_resets',
                        'version'    => '1_0_1_0',
                        'created_at' => $this->time,
                    ],
                ],
            ],
        ];
    }

    /**
     * Run command.
     *
     * @return void
     */
    protected function commandRun(): void
    {
        Carbon::setTestNow(Carbon::parse($this->time));
        Config::set('stepupdream.migration.basic.version_control_table_name', 'migrations');
        Config::set('stepupdream.migration.basic.migration_directory_parent_path', __DIR__.'/MigrationFiles');
        Config::set('database.connections', ['user_db' => ['drive' => 'mysql']]);
        Config::set('stepupdream.migration.basic.exclude_connections', []);

        $fileOperation = new FileOperation();
        $databaseManager = $this->app['db'];

        $migratorMock = Mockery::mock(Migrator::class, [$fileOperation, $databaseManager]);
        $bufferedOutput = new BufferedOutput();
        $migratorMock->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->allows('useTransactions')
            ->andReturns(false);
        $style = new OutputStyle(new ArrayInput([]), $bufferedOutput);
        $migratorMock->setOutput($style);

        // run
        // To avoid errors in areas other than the original test area, test in inherited classes.
        $migrateCommand = new  MigrateCommand($fileOperation, $migratorMock, $style);
        $migrateCommand->handle();
    }
}

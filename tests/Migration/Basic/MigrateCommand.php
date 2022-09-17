<?php

declare(strict_types=1);

namespace StepUpDream\DreamAbility\Test\Migration\Basic;

use Closure;
use Illuminate\Console\View\Components\Factory;
use StepUpDream\DreamAbility\Database\Console\Migrations\Basic\MigrateCommand as Command;
use StepUpDream\DreamAbility\Database\Migrations\Basic\Migrator;
use StepUpDream\DreamAbilitySupport\Supports\File\FileOperation;

class MigrateCommand extends Command
{
    /**
     * Create a new console command instance.
     *
     * @param  \StepUpDream\DreamAbilitySupport\Supports\File\FileOperation  $fileOperation
     * @param  \StepUpDream\DreamAbility\Database\Migrations\Basic\Migrator  $migrator
     * @param $style
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function __construct(
        protected FileOperation $fileOperation,
        protected Migrator $migrator,
        protected $style
    ) {
        $this->components = app()->make(Factory::class, ['output' => $style]);
        $this->output = $style;

        parent::__construct($fileOperation, $migrator);
    }

    /**
     * Get the default confirmation callback.
     *
     * @return \Closure
     */
    protected function getDefaultConfirmCallback(): Closure
    {
        return function () {
            return false;
        };
    }

    /**
     * Run "dream-ability-migrate:install-basic".
     *
     * @param  string  $connectionName
     * @return void
     */
    protected function migrate(string $connectionName): void
    {
    }

    /**
     * Whether a file with the same filename exists or not.
     */
    protected function verifySameFileNameExist(): void
    {
        //
    }

    /**
     * Write a blank line.
     *
     * @param  int  $count
     * @return $this
     */
    public function newLine($count = 1): static
    {
        return $this;
    }

    /**
     * Command execution log.
     *
     * @param  string  $description
     * @return void
     */
    protected function commandDetailLog(string $description = 'Command run detail'): void
    {
        //
    }
}

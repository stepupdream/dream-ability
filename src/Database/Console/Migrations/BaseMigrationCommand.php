<?php

declare(strict_types=1);

namespace StepUpDream\DreamAbility\Database\Console\Migrations;

use Illuminate\Console\ConfirmableTrait;
use StepUpDream\DreamAbility\Database\Migrations\Basic\Migrator;
use StepUpDream\DreamAbilitySupport\Console\BaseCommand;
use StepUpDream\DreamAbilitySupport\Supports\File\FileOperation;

class BaseMigrationCommand extends BaseCommand
{
    use ConfirmableTrait;

    /**
     * Create a new console command instance.
     *
     * @param  \StepUpDream\DreamAbilitySupport\Supports\File\FileOperation  $fileOperation
     * @param  \StepUpDream\DreamAbility\Database\Migrations\Basic\Migrator  $migrator
     */
    public function __construct(
        protected FileOperation $fileOperation,
        protected Migrator $migrator
    ) {
        parent::__construct();
    }
}

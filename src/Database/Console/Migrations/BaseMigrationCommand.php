<?php

declare(strict_types=1);

namespace StepUpDream\DreamAbility\Database\Console\Migrations;

use Illuminate\Console\ConfirmableTrait;
use StepUpDream\DreamAbilitySupport\Console\BaseCommand;

class BaseMigrationCommand extends BaseCommand
{
    use ConfirmableTrait;

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        //
    }
}

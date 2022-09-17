<?php

declare(strict_types=1);

use StepUpDream\DreamAbility\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * @var string
     */
    protected string $connection = 'user_db';

    /**
     * @var string
     */
    protected string $tableName = 'password_resets';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
    }
};

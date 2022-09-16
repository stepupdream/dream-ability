<?php

declare(strict_types=1);

return [
    'basic' => [
        'definition_database_directory_path' => base_path('definition_document/Database'),
        'migration_directory_parent_path'    => database_path('migrations/step_up_dream'),
        'version_control_table_name'         => 'migrations',
        'exclude_connections'                => ['master_data', 'sqlite', 'mysql', 'pgsql', 'sqlsrv'],
    ],
];

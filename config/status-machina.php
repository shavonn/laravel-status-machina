<?php

declare(strict_types=1);

use SysMatter\StatusMachina\Authorization\AuthorizationMethod;

return [
    /*
    |--------------------------------------------------------------------------
    | Default Authorization Method
    |--------------------------------------------------------------------------
    |
    | This option controls the default authorization method used by the
    | state machine. Supported: "null", "gate", "policy", "permission"
    |
    */
    'default_authorization' => AuthorizationMethod::from(env('STATUS_MACHINA_AUTH', 'null')),

    /*
    |--------------------------------------------------------------------------
    | Database History Tracking
    |--------------------------------------------------------------------------
    |
    | Enable or disable database history tracking for state transitions.
    |
    */
    'db_history_tracking' => [
        'enabled' => false,
        'history_table_name' => 'state_transitions',
    ],

    /*
    |--------------------------------------------------------------------------
    | Activity Log History Tracking
    |--------------------------------------------------------------------------
    |
    | Enable or disable Spatie Activity Log tracking for state transitions.
    |
    */
    'activitylog_history_tracking' => [
        'enabled' => false,
        'log_name' => 'state_transitions',
    ],

    /*
    |--------------------------------------------------------------------------
    | Maximum History Retention
    |--------------------------------------------------------------------------
    |
    | The maximum number of days to retain state transition history.
    | Set to null to keep history indefinitely.
    |
    */
    'max_history_retention' => null,
];

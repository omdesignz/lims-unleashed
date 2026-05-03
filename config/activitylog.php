<?php

return [

    /*
     * If set to false, no activities will be saved to the database.
     */
    'enabled' => env('ACTIVITY_LOGGER_ENABLED', true),

    /*
     * When the clean-command is executed, all recording activities older than
     * the number of days specified here will be deleted.
     */
    'delete_records_older_than_days' => 365,

    /*
     * If no log name is passed to the activity() helper
     * we use this default log name.
     */
    'default_log_name' => 'default',

    /*
     * You can specify an auth driver here that gets user models.
     * If this is null we'll use the current Laravel auth driver.
     */
    'default_auth_driver' => null,

    /*
     * If set to true, the subject returns soft deleted models.
     */
    'subject_returns_soft_deleted_models' => true,

    /*
     * This model will be used to log activity.
     * It should implement the Spatie\Activitylog\Contracts\Activity interface
     * and extend Illuminate\Database\Eloquent\Model.
     */
    // 'activity_model' => \Spatie\Activitylog\Models\Activity::class,

    'activity_model' => \App\Models\ISOActivityLog::class,

    /*
     * This is the name of the table that will be created by the migration and
     * used by the Activity model shipped with this package.
     */
    'table_name' => env('ACTIVITY_LOGGER_TABLE_NAME', 'activity_log'),

    /*
     * This is the database connection that will be used by the migration and
     * the Activity model shipped with this package. In case it's not set
     * Laravel's database.default will be used instead.
     */
    'database_connection' => env('ACTIVITY_LOGGER_DB_CONNECTION'),

    /*
     * This is the custom configuration for ISO 17025 related activity logging.
     * It includes retention period, required fields, and snapshot relations.
     * The database connection will use the same setting as above.
     */

    'iso_17025' => [
        'retention_period' => env('AUDIT_RETENTION_YEARS', 10) * 365, // days
        'required_fields' => [
            'user_id',
            'change_reason',
            'approval_status',
            'related_entities',
        ],
        'snapshot_relations' => [
            'quality_certificates' => ['collection', 'results', 'customer', 'product', 'warehouse'],
            'collection_product' => ['results', 'product', 'customer', 'warehouse'],
            'results' => ['parameter', 'unit', 'standard'],
        ],
    ],
];

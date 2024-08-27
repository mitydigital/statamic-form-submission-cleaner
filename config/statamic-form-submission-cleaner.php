<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Defaults
    |--------------------------------------------------------------------------
    |
    | You can set the defaults for the cleanup operations that will apply to
    | all configured forms for the cleanup process.
    |
    | Each value can also be overridden per form.
    |
    */

    'defaults' => [

        /*
         * Submissions older than this will be cleaned
         */
        'days' => 30,

        /*
         * Determines if assets should be deleted
         */
        'delete_assets' => true,

        /*
         * Determines if submissions should be deleted
         */
        'delete_submissions' => true,

        /*
         * Query scopes to add to the submission lookup query
         *
         * Can be null, a single scope, or an array of scopes
         */
        'query_scopes' => null,

    ],

    /*
    |--------------------------------------------------------------------------
    | Form Configuration
    |--------------------------------------------------------------------------
    |
    | To process all forms, simply set the 'forms' attribute to 'all':
    |
    |    'forms' => 'all',
    |
    | You can also specify an array of form handles to process, and all
    | defaults will automatically be applied
    |
    |    'forms' => [
    |        'form_handle',
    |        'another_form_handle',
    |    ],
    |
    | You can also override defaults per form. For any property *not* included
    | in the array, the defaults will be applied.
    |
    |    'forms' => [
    |        'form_handle' => [
    |            'days' => 7,
    |        ]
    |    ],
    |
    | But wait, there's more. Some forms can use all defaults, and others
    | can have selected properties overridden:
    |
    |    'forms' => [
    |        'form_handle' => [
    |            'days' => 7,
    |        ],
    |        'another_form_handle',
    |    ],
    |
    */

    'forms' => 'all',

];

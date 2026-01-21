<?php

return [
    'locale' => 'en_US',
    'chunk_size' => 1000,

    /*
    |--------------------------------------------------------------------------
    | Restricted Environments
    |--------------------------------------------------------------------------
    |
    | Define multiple environments that require confirmation before anonymization.
    | By default, the specified restricted environments are strictly enforced for added security.
    |
    */
    'restricted_env' => ['production', 'staging'],

    /*
    |--------------------------------------------------------------------------
    | Allowed Database Connections
    |--------------------------------------------------------------------------
    |
    | Database connections listed here are exempt from restricted environment
    | checks and will not require confirmation before anonymization.
    | Useful for local testing databases or isolated environments.
    |
    */
    'allowed_db_connections' => [],

    /*
    |--------------------------------------------------------------------------
    | Model Ordering
    |--------------------------------------------------------------------------
    |
    | Optionally specify the order of anonymization, these Models will be anonymized first.
    |
    */
    'priority_models' => [],
];
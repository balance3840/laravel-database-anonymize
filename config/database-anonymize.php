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
    | Model Ordering
    |--------------------------------------------------------------------------
    |
    | Optionally specify the order of anonymization, these Models will be anonymized first.
    |
    */
    'priority_models' => []
];

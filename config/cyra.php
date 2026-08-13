<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | CyraAgroLink Platform Configuration
    |--------------------------------------------------------------------------
    |
    | Core identity and defaults for the CyraAgroLink agricultural ecosystem
    | platform developed by CYRA-TECH LTD.
    |
    */

    'name' => env('CYRA_APP_NAME', 'CyraAgroLink'),

    'brand' => env('CYRA_BRAND', 'CyraAgroLink'),

    'company' => env('CYRA_COMPANY', 'CYRA-TECH LTD'),

    'tagline' => env(
        'CYRA_TAGLINE',
        'Connecting Agriculture, Markets, Investment and Opportunities Across Africa.'
    ),

    'stats' => [
        ['value' => '125K+', 'label' => 'Farmers'],
        ['value' => '8,400+', 'label' => 'Investors'],
        ['value' => '12.5K+', 'label' => 'Buyers'],
        ['value' => '850K+', 'label' => 'Transactions'],
        ['value' => '25+', 'label' => 'Countries'],
    ],

    'api_version' => env('CYRA_API_VERSION', 'v1'),

    'api_prefix' => env('CYRA_API_PREFIX', 'api/v1'),

    /*
    |--------------------------------------------------------------------------
    | Supported UI locales
    |--------------------------------------------------------------------------
    */

    'locales' => [
        'en' => 'English',
        'fr' => 'Français',
        'ha' => 'Hausa',
        'yo' => 'Yorùbá',
        'ig' => 'Igbo',
    ],

    'locale_labels' => [
        'en' => 'EN',
        'fr' => 'FR',
        'ha' => 'HA',
        'yo' => 'YO',
        'ig' => 'IG',
    ],

    'pagination' => [
        'default_per_page' => (int) env('CYRA_DEFAULT_PER_PAGE', 15),
        'max_per_page' => (int) env('CYRA_MAX_PER_PAGE', 100),
    ],

    'roles' => [
        'default' => env('CYRA_DEFAULT_ROLE', 'farmer'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Demo / catalog auto-seeding on HTTP requests
    |--------------------------------------------------------------------------
    |
    | When null/empty, seeding is allowed outside production only.
    | Set CYRA_ALLOW_DEMO_SEEDING=false to disable even in local/staging.
    | Set CYRA_ALLOW_DEMO_SEEDING=true only for controlled demo environments.
    |
    */
    'allow_demo_seeding' => env('CYRA_ALLOW_DEMO_SEEDING'),

    /*
    |--------------------------------------------------------------------------
    | Farm registration reference data
    |--------------------------------------------------------------------------
    */

    'nigeria_states' => [
        'Abia', 'Adamawa', 'Akwa Ibom', 'Anambra', 'Bauchi', 'Bayelsa', 'Benue',
        'Borno', 'Cross River', 'Delta', 'Ebonyi', 'Edo', 'Ekiti', 'Enugu',
        'FCT', 'Gombe', 'Imo', 'Jigawa', 'Kaduna', 'Kano', 'Katsina', 'Kebbi',
        'Kogi', 'Kwara', 'Lagos', 'Nasarawa', 'Niger', 'Ogun', 'Ondo', 'Osun',
        'Oyo', 'Plateau', 'Rivers', 'Sokoto', 'Taraba', 'Yobe', 'Zamfara',
    ],

    /*
    | Farm enterprises include crops, poultry, aquaculture, livestock, and related.
    | `crop_options` is kept as an alias for older callers.
    */
    'enterprise_options' => [
        // Crops
        'Cassava', 'Maize', 'Rice', 'Yam', 'Cocoa', 'Oil Palm', 'Tomato',
        'Pepper', 'Groundnut', 'Sorghum', 'Millet', 'Plantain', 'Soybean',
        // Poultry
        'Broilers', 'Layers', 'Poultry Hatchery', 'Poultry Feed',
        // Aquaculture
        'Catfish Farming', 'Tilapia Farming', 'Fish Hatchery',
        // Other livestock & related
        'Pig Farming', 'Goat Rearing', 'Cattle', 'Snail Farming', 'Rabbit Farming',
    ],

    'crop_options' => [
        'Cassava', 'Maize', 'Rice', 'Yam', 'Cocoa', 'Oil Palm', 'Tomato',
        'Pepper', 'Groundnut', 'Sorghum', 'Millet', 'Plantain', 'Soybean',
        'Broilers', 'Layers', 'Poultry Hatchery', 'Poultry Feed',
        'Catfish Farming', 'Tilapia Farming', 'Fish Hatchery',
        'Pig Farming', 'Goat Rearing', 'Cattle', 'Snail Farming', 'Rabbit Farming',
    ],
];

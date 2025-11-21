<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Utility Settings
    |--------------------------------------------------------------------------
    |
    | Here you may configure the settings for the Laravel Utils package.
    | These settings will be used throughout the package to customize
    | its behavior according to your application's needs.
    |
    */

    'enums' => [
        /*
        |--------------------------------------------------------------------------
        | Output Path for TypeScript Enums
        |--------------------------------------------------------------------------
        |
        | This option defines the output path where the generated TypeScript
        | enums will be saved inside the resources directory. You can customize this path to fit your
        | project's structure.
        |
        */
        'output_path' => 'js/enums',
    ],

    'lang_keys' => [
        /*|--------------------------------------------------------------------------
        | Main Locale
        |--------------------------------------------------------------------------
        | This option defines the main locale used for extracting
        | translation keys from Laravel language files. You can change this
        | to match the primary language of your application.
        |*/
        'main_locale' => 'en',
        /*
        |--------------------------------------------------------------------------
        | Output Path for TypeScript Language Keys
        |--------------------------------------------------------------------------
        |
        | This option defines the output path where the generated TypeScript
        | language keys will be saved inside the resources directory. You can customize this path to fit your
        | project's structure.
        |
        */
        'output_path' => 'js/types/lang-keys.d.ts',

        /*|--------------------------------------------------------------------------
        | Type Name for Translation Keys
        |--------------------------------------------------------------------------
        |
        | This option defines the name of the TypeScript type that will hold
        | all the translation keys extracted from Laravel language files.
        | You can change this to suit your naming conventions.
        |*/
        'type_name' => 'TranslationKeys',

        /*|--------------------------------------------------------------------------
        | Excluded Language Files
        |--------------------------------------------------------------------------
        | This option allows you to specify language files that should be
        | excluded from the TypeScript translation keys generation process.
        | Provide an array of file names (without extensions) to exclude them.
        |*/
        'exclude_files' => [
            'auth',
            'pagination',
            'passwords',
            'validation',
        ],
    ],

];

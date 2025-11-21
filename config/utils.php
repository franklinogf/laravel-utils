<?php

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
    ],

];

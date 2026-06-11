<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    |
    | Set some default values. It is possible to add all defines that can be set
    | in dompdf_config.inc.php. You can also override the entire config file.
    |
    */
    'show_warnings' => false,   // Throw an Exception on warnings from dompdf

    'public_path' => null,  // Override the public path if needed

    /*
     * Dejavu Sans font is missing glyphs for converted entities, turn it off if you need to show € and £.
     */
    'convert_entities' => true,

    // config/dompdf.php
    'options' => [
        'font_dir'   => storage_path('fonts/'),
        'font_cache' => storage_path('fonts/'),
        'is_unicode' => true,
        'is_html5_parser_enabled' => true,
        'is_rtl'     => true,
        'defaultFont' => 'DejaVu Sans',
    ],
    

];

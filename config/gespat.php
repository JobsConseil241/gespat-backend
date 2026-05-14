<?php

return [
    'org_code' => env('GESPAT_ORG_CODE', 'XYZ'),

    'codification' => [
        'format_defaut' => env('GESPAT_CODIFICATION_FORMAT', '{ORG}-{CAT}-{SITE}-{ANNEE}-{SEQ:05d}'),
        'separateur' => env('GESPAT_CODIFICATION_SEPARATOR', '-'),
    ],

    'qr' => [
        'base_url' => env('GESPAT_QR_BASE_URL', env('APP_URL').'/b/'),
        'hmac_secret' => env('GESPAT_HMAC_SECRET', 'change-me-in-prod'),
    ],

    'etiquettes' => [
        'formats' => [
            '62x29' => ['width_mm' => 62, 'height_mm' => 29, 'layout' => 'roll'],
            '38x90' => ['width_mm' => 38, 'height_mm' => 90, 'layout' => 'roll'],
            'A4_avery_24' => ['width_mm' => 70, 'height_mm' => 37, 'cols' => 3, 'rows' => 8, 'page' => 'A4'],
            'A4_avery_30' => ['width_mm' => 70, 'height_mm' => 29.7, 'cols' => 3, 'rows' => 10, 'page' => 'A4'],
        ],
    ],
];

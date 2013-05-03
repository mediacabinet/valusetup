<?php
return [
    'service_manager' => [
        'factories' => [
            'valu_so.setup_utils' => 'ValuSetup\\Setup\\SetupUtilsFactory',
        ],
    ],
    'setup_utils' => [
        'module_dirs' => [
            'valu' => 'vendor/valu',
        ],
        'repositories' => [
            'default' => [
                'url' => 'http://zf2b.valu.fi/rest/module-repository/download',
                'priority' => '1000',
            ],
        ],
        'definition_file' => 'composer.json',
        'extract_phars' => true,
    ],
];

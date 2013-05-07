<?php
return [
    'service_manager' => [
        'factories' => [
            'valu_setup.setup_utils' => 'ValuSetup\\Setup\\SetupUtilsFactory',
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
    'valu_so' => [
        'services' => [
            'ValuSetupAuth' => [
                'name' => 'Auth',
                'class' => 'ValuSetup\\Service\\AuthService',
                'options' => [
                    'identity_file' => '.setupauth',
                    'identity' => [
                        'superuser' => true,
                        'roles' => ['/' => 'superuser']
                    ],
                    'realm' => 'Setup Authentication',
                    'nonce_timeout' => '3600',
                ],
                'priority' => 100000
            ],
        ]
    ]
];

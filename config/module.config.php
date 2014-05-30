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
        'definition_file' => 'composer.json'
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

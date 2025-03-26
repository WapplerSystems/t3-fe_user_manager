<?php

return [
    'fe_user_manager' => [
        'parent' => 'web',
        'access' => 'admin',
        'iconIdentifier' => 'fe-user-module',
        'labels' => 'LLL:EXT:fe_user_manager/Resources/Private/Language/locallang_mod.xlf',
        'routes' => [
            '_default' => [
                'target' => \WapplerSystems\FeUserManager\Controller\FeUserController::class . '::indexAction',
            ],
            'activate' => [
                'path' => '/fe-user/activate/{user}',
                'target' => \WapplerSystems\FeUserManager\Controller\FeUserController::class . '::activateUserAction',
            ],
        ],
    ],
];

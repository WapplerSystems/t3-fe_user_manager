<?php

use WapplerSystems\FeUserManager\Controller\FeUserController;

return [
    'fe_user_manager' => [
        'parent' => 'web',
        'access' => 'user',
        'iconIdentifier' => 'module-beuser',
        'inheritNavigationComponentFromMainModule' => false,
        'labels' => 'LLL:EXT:fe_user_manager/Resources/Private/Language/locallang_mod.xlf',
        'path' => '/module/fe-user-manager',
        'extensionName' => 'FeUserManager',
        'controllerActions' => [
            FeUserController::class => [
                'index', 'activateUser',
            ]
        ]
    ],
];

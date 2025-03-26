<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'FE User Manager',
    'description' => 'Listet fe_user und erlaubt Aktivierung + E-Mail-Versand',
    'category' => 'module',
    'state' => 'beta',
    'author' => 'Sven Wappler',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '13.0.0-13.9.99',
        ],
    ],
];

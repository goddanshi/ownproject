<?php
$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-backend-console',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'backend\commands',
    'bootstrap' => ['log', 'gii'], // включаем gii
    'modules' => [
        'gii' => [
            'class' => \yii\gii\Module::class,
            'allowedIPs' => ['*'],
        ],
    ],
    'components' => [
        'db' => require __DIR__ . '/db.php',
        'log' => [
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
    ],
    'params' => $params,
];

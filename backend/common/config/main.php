<?php
return [
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'cache' => [
            'class' => \yii\caching\FileCache::class,
        ],
        'jwt' => [
            'class' => \sizeg\jwt\Jwt::class,
            'key' => 'your_secret_key_here', // замени на уникальный секрет
            'jwtValidationData' => \app\components\JwtValidationData::class, // необязательно
        ],
    ],
];

<?php
$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-backend',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'backend\controllers',
    'bootstrap' => ['log'],
    'modules' => [],
    'components' => [
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => true,
            'identityCookie' => ['name' => '_identity-backend', 'httpOnly' => true],
        ],
        'session' => [
            // this is the name of the session cookie used for login on the backend
            'name' => 'advanced-backend',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                // --------------------------------
                'POST api/register' => 'api/register',
                'POST api/login' => 'api/login',
                'GET api/check' => 'api/check',
                'POST api/logout' => 'api/logout',
                'POST api/refresh' => 'api/refresh',

                // --------------------------------
                // User routes
                'GET user/profile' => 'user/profile',
                'GET user/profiles' => 'user/profiles',
                'POST user/update-profile' => 'user/update-profile',
                'POST user/change-password' => 'user/change-password',

                // --------------------------------
                // Settings routes
                'GET settings/permissions' => 'settings/get-permissions',
                'GET settings/role-permissions' => 'settings/get-role-permissions',
                'POST settings/update-role-permissions' => 'settings/update-role-permissions'
            ],
        ],
        'request' => [
            'cookieValidationKey' => 'your-secret-key-here',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ]
        ],
    ],
    'params' => $params,
];

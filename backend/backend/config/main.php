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
                'POST user/update-avatar' => 'user/update-avatar',

                // --------------------------------
                // Settings routes
                'OPTIONS settings/role-permissions' => 'settings/options',  
                'OPTIONS settings/permissions' => 'settings/options',        
                'OPTIONS settings/update-role-permissions' => 'settings/options', 
                'GET settings/permissions' => 'settings/get-permissions',
                'GET settings/role-permissions' => 'settings/get-role-permissions',
                'POST settings/update-role-permissions' => 'settings/update-role-permissions',

                // --------------------------------
                // Teams routes
                'OPTIONS teams/<action>' => 'teams/options',
                'GET teams' => 'teams/index',
                'GET teams/<id:\d+>' => 'teams/view',
                'POST teams/create' => 'teams/create',
                'POST teams/update' => 'teams/update',
                'DELETE teams/<id:\d+>' => 'teams/delete',
                'POST teams/add-member' => 'teams/add-member',
                'POST teams/remove-member' => 'teams/remove-member',
                'GET teams/teamleads' => 'teams/get-teamleads',
                'GET teams/employees' => 'teams/get-employees',

                // --------------------------------
                // Projects routes
                'OPTIONS projects/<action>' => 'projects/options',
                'GET projects' => 'projects/index',
                'GET projects/<id:\d+>' => 'projects/view',
                'POST projects/create' => 'projects/create',
                'POST projects/update' => 'projects/update',
                'DELETE projects/<id:\d+>' => 'projects/delete',

                // --------------------------------
                // Folders routes
                'OPTIONS folders' => 'folders/options',
                'OPTIONS folders/<action>' => 'folders/options',
                'OPTIONS folders/<id:\d+>' => 'folders/options',
                'GET folders' => 'folders/index',
                'GET folders/tree' => 'folders/tree',
                'GET folders/<id:\d+>' => 'folders/view',
                'POST folders/create' => 'folders/create',
                'POST folders/update' => 'folders/update',
                'DELETE folders/<id:\d+>' => 'folders/delete',

                // --------------------------------
                // Tasks routes
                'OPTIONS tasks/<action>' => 'tasks/options',
                'GET tasks' => 'tasks/index',
                'GET tasks/active' => 'tasks/get-active-tasks',
                'GET tasks/<id:\d+>' => 'tasks/view',
                'POST tasks/create' => 'tasks/create',
                'POST tasks/update' => 'tasks/update',
                'DELETE tasks/<id:\d+>' => 'tasks/delete',
                'POST tasks/assign-user' => 'tasks/assign-user',
                'POST tasks/unassign-user' => 'tasks/unassign-user',
                'POST tasks/start-tracking' => 'tasks/start-tracking',
                'POST tasks/stop-tracking' => 'tasks/stop-tracking',
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

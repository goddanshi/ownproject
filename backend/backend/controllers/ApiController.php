<?php
namespace backend\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use common\models\LoginForm;
use common\models\User;
use common\components\JwtHelper;

class ApiController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        unset($behaviors['authenticator']);

        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
            'cors' => [
                'Origin' => [
                    'http://185.213.240.236',
                    'http://185.104.113.132',
                    'http://185.104.113.132:8080',
                    'http://localhost:5173',
                    'http://127.0.0.1:5173'
                ],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => true,
                'Access-Control-Max-Age' => 3600,
            ],
        ];

        return $behaviors;
    }

    // POST /api/register
    public function actionRegister()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $data = Yii::$app->request->post();

        $user = new User();
        $user->username = $data['username'] ?? '';
        $user->email = $data['email'] ?? '';
        $user->setPassword($data['password'] ?? '');
        $user->generateAuthKey();
        $user->status = User::STATUS_ACTIVE;
        $user->created_at = time();
        $user->updated_at = time();

        if ($user->save()) {
            // Генерируем JWT токен
            $token = JwtHelper::generateToken($user->id, $user->username, $user->email, $user->role);

            return [
                'success' => true,
                'message' => 'Registration successful',
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role' => $user->role,
                    'avatar' => $user->avatar,
                ]
            ];
        }

        return [
            'success' => false,
            'message' => 'Registration failed',
            'errors' => $user->errors
        ];
    }

    // POST /api/login
    public function actionLogin()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $data = Yii::$app->request->post();

        $user = User::findOne(['username' => $data['username'] ?? '']);

        if ($user && $user->validatePassword($data['password'] ?? '')) {
            // Генерируем JWT токен
            $token = JwtHelper::generateToken($user->id, $user->username, $user->email, $user->role);

            return [
                'success' => true,
                'message' => 'Login successful',
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role' => $user->role,
                    'avatar' => $user->avatar,
                ]
            ];
        }

        return [
            'success' => false,
            'message' => 'Invalid username or password'
        ];
    }

    // GET /api/check
    public function actionCheck()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $authHeader = Yii::$app->request->headers->get('Authorization');

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return ['success' => false, 'message' => 'Token not provided'];
        }

        $token = $matches[1];
        $payload = JwtHelper::validateToken($token);

        if (!$payload) {
            return ['success' => false, 'message' => 'Invalid or expired token'];
        }

        // Получаем свежие данные пользователя из БД
        $user = User::findOne($payload['userId']);

        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }

        return [
            'success' => true,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
                'avatar' => $user->avatar,
            ]
        ];
    }

    // POST /api/logout
    public function actionLogout()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        // С JWT logout происходит на фронте (удаляем токен)
        return ['success' => true, 'message' => 'Logged out'];
    }

    // POST /api/refresh
    public function actionRefresh()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $authHeader = Yii::$app->request->headers->get('Authorization');

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return ['success' => false, 'message' => 'Token not provided'];
        }

        $token = $matches[1];
        $payload = JwtHelper::validateToken($token);

        if (!$payload) {
            return ['success' => false, 'message' => 'Invalid or expired token'];
        }

        // Генерируем новый токен
        $newToken = JwtHelper::generateToken(
            $payload['userId'],
            $payload['username'],
            $payload['email']
        );

        return [
            'success' => true,
            'token' => $newToken
        ];
    }
}
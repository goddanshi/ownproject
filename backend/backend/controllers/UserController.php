<?php

namespace backend\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use common\models\User;
use common\components\JwtHelper;

class UserController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        unset($behaviors['authenticator']);

        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
            'cors' => [
                'Origin' => [
                    'http://81.19.136.133',
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

    // POST /user/update-profile
    public function actionUpdateProfile()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        // Проверяем JWT токен
        $authHeader = Yii::$app->request->headers->get('Authorization');

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $token = $matches[1];
        $payload = JwtHelper::validateToken($token);

        if (!$payload) {
            return ['success' => false, 'message' => 'Invalid token'];
        }

        // Находим пользователя
        $user = User::findOne($payload['userId']);

        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }

        // Получаем данные из запроса
        $data = Yii::$app->request->post();

        // Обновляем поля
        if (isset($data['name'])) {
            $user->name = $data['name'];
        }

        if (isset($data['surname'])) {
            $user->surname = $data['surname'];
        }

        if (isset($data['email'])) {
            // Проверяем уникальность email
            $existingUser = User::find()
                ->where(['email' => $data['email']])
                ->andWhere(['!=', 'id', $user->id])
                ->one();

            if ($existingUser) {
                return [
                    'success' => false,
                    'message' => 'Email already taken',
                    'errors' => ['email' => ['Email уже используется']]
                ];
            }

            $user->email = $data['email'];
        }

        if ($user->save()) {
            return [
                'success' => true,
                'message' => 'Profile updated successfully',
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'name' => $user->name,
                    'surname' => $user->surname,
                    'email' => $user->email,
                ]
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to update profile',
            'errors' => $user->errors
        ];
    }

    // POST /user/change-password
    public function actionChangePassword()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        // Проверяем JWT токен
        $authHeader = Yii::$app->request->headers->get('Authorization');

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $token = $matches[1];
        $payload = JwtHelper::validateToken($token);

        if (!$payload) {
            return ['success' => false, 'message' => 'Invalid token'];
        }

        // Находим пользователя
        $user = User::findOne($payload['userId']);

        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }

        $data = Yii::$app->request->post();

        // Проверяем старый пароль
        if (!isset($data['oldPassword']) || !$user->validatePassword($data['oldPassword'])) {
            return [
                'success' => false,
                'message' => 'Incorrect old password',
                'errors' => ['oldPassword' => ['Неверный текущий пароль']]
            ];
        }

        // Проверяем новый пароль
        if (!isset($data['newPassword']) || strlen($data['newPassword']) < 6) {
            return [
                'success' => false,
                'message' => 'New password must be at least 6 characters',
                'errors' => ['newPassword' => ['Пароль должен быть минимум 6 символов']]
            ];
        }

        // Устанавливаем новый пароль
        $user->setPassword($data['newPassword']);
        $user->generateAuthKey();

        if ($user->save()) {
            // Генерируем новый токен
            $newToken = JwtHelper::generateToken($user->id, $user->username, $user->email);

            return [
                'success' => true,
                'message' => 'Password changed successfully',
                'token' => $newToken
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to change password',
            'errors' => $user->errors
        ];
    }

    // GET /user/profile
    public function actionProfiles()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        // Проверяем JWT токен
        $authHeader = Yii::$app->request->headers->get('Authorization');

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $token = $matches[1];
        $payload = JwtHelper::validateToken($token);

        if (!$payload) {
            return ['success' => false, 'message' => 'Invalid token'];
        }

        // Получаем всех активных пользователей
        $users = User::find()
            ->where(['status' => User::STATUS_ACTIVE])
            ->all();

        if (empty($users)) {
            return [
                'success' => true,
                'users' => [],
                'total' => 0
            ];
        }

        // Формируем массив пользователей
        $usersData = [];
        foreach ($users as $user) {
            $usersData[] = [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'surname' => $user->surname,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
                'created_at' => $user->created_at,
            ];
        }

        return [
            'success' => true,
            'users' => $usersData,
            'total' => count($usersData)
        ];
    }
}
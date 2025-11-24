<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\Cors;
use yii\web\Response;
use common\components\JwtHelper;
use common\models\User;

class WorkersController extends Controller
{
    public function behaviors()
    {
        return [
            'corsFilter' => [
                'class' => Cors::class,
                'cors' => [
                    'Origin' => ['http://localhost:5173', 'http://185.213.240.236:5173', 'http://185.104.113.132', 'http://185.104.113.132:8080', 'http://185.213.240.236'],
                    'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
                    'Access-Control-Request-Headers' => ['*'],
                    'Access-Control-Allow-Credentials' => true,
                    'Access-Control-Max-Age' => 86400,
                ],
            ],
        ];
    }

    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }

    public function actionOptions()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return ['success' => true];
    }

    // Получить список работников
    public function actionIndex()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        // Проверка прав
        // Можно добавить логику: админ видит всех, тимлид - только свою команду
        $workers = User::find()->orderBy(['created_at' => SORT_DESC])->all();

        $result = [];
        foreach ($workers as $worker) {
            $result[] = [
                'id' => $worker->id,
                'username' => $worker->username,
                'email' => $worker->email,
                'name' => $worker->name,
                'surname' => $worker->surname,
                'role' => $worker->role,
                'avatar' => $worker->avatar,
                'created_at' => $worker->created_at,
            ];
        }

        return [
            'success' => true,
            'workers' => $result
        ];
    }

    // Получить детали работника
    public function actionView()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $id = Yii::$app->request->get('id');
        $worker = User::findOne($id);

        if (!$worker) {
            return ['success' => false, 'message' => 'Работник не найден'];
        }

        return [
            'success' => true,
            'worker' => [
                'id' => $worker->id,
                'username' => $worker->username,
                'email' => $worker->email,
                'name' => $worker->name,
                'surname' => $worker->surname,
                'role' => $worker->role,
                'avatar' => $worker->avatar,
                'created_at' => $worker->created_at,
            ]
        ];
    }

    // Создать работника
    public function actionCreate()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        // Только админ может создавать работников
        if ($user->role != User::ROLE_ADMIN) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        $data = json_decode(Yii::$app->request->rawBody, true);

        $worker = new User();
        $worker->username = $data['username'] ?? '';
        $worker->email = $data['email'] ?? '';
        $worker->role = $data['role'] ?? User::ROLE_EMPLOYER;
        $worker->name = $data['name'] ?? '';
        $worker->surname = $data['surname'] ?? '';
        $worker->status = User::STATUS_ACTIVE;
        $worker->created_at = time();
        $worker->updated_at = time();

        // Устанавливаем пароль
        if (!empty($data['password'])) {
            $worker->setPassword($data['password']);
            $worker->generateAuthKey();
        } else {
            return ['success' => false, 'message' => 'Пароль обязателен'];
        }

        if (!$worker->save()) {
            return [
                'success' => false,
                'message' => 'Ошибка создания работника',
                'errors' => $worker->errors
            ];
        }

        return [
            'success' => true,
            'message' => 'Работник успешно создан',
            'worker' => [
                'id' => $worker->id,
                'username' => $worker->username,
            ]
        ];
    }

    // Обновить работника
    public function actionUpdate()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        // Только админ может обновлять работников
        if ($user->role != User::ROLE_ADMIN) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        $data = json_decode(Yii::$app->request->rawBody, true);
        $id = $data['id'] ?? null;

        $worker = User::findOne($id);
        if (!$worker) {
            return ['success' => false, 'message' => 'Работник не найден'];
        }

        $worker->username = $data['username'] ?? $worker->username;
        $worker->email = $data['email'] ?? $worker->email;
        $worker->role = $data['role'] ?? $worker->role;
        $worker->name = $data['name'] ?? $worker->name;
        $worker->surname = $data['surname'] ?? $worker->surname;
        $worker->updated_at = time();

        if (!$worker->save()) {
            return [
                'success' => false,
                'message' => 'Ошибка обновления работника',
                'errors' => $worker->errors
            ];
        }

        return [
            'success' => true,
            'message' => 'Работник успешно обновлен'
        ];
    }

    // Удалить работника
    public function actionDelete()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        // Только админ может удалять работников
        if ($user->role != User::ROLE_ADMIN) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        $id = Yii::$app->request->get('id');
        $worker = User::findOne($id);

        if (!$worker) {
            return ['success' => false, 'message' => 'Работник не найден'];
        }

        // Нельзя удалить самого себя
        if ($worker->id == $user->id) {
            return ['success' => false, 'message' => 'Нельзя удалить самого себя'];
        }

        if ($worker->delete()) {
            return [
                'success' => true,
                'message' => 'Работник успешно удален'
            ];
        }

        return [
            'success' => false,
            'message' => 'Ошибка удаления работника'
        ];
    }

    // Изменить пароль работника
    public function actionChangePassword()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        // Только админ может менять пароли
        if ($user->role != User::ROLE_ADMIN) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        $data = json_decode(Yii::$app->request->rawBody, true);
        $id = $data['id'] ?? null;
        $newPassword = $data['new_password'] ?? null;

        if (!$newPassword || strlen($newPassword) < 6) {
            return ['success' => false, 'message' => 'Пароль должен содержать минимум 6 символов'];
        }

        $worker = User::findOne($id);
        if (!$worker) {
            return ['success' => false, 'message' => 'Работник не найден'];
        }

        $worker->setPassword($newPassword);
        $worker->generateAuthKey();
        $worker->updated_at = time();

        if ($worker->save()) {
            return [
                'success' => true,
                'message' => 'Пароль успешно изменен'
            ];
        }

        return [
            'success' => false,
            'message' => 'Ошибка изменения пароля'
        ];
    }

    // Изменить дату регистрации работника
    public function actionUpdateCreatedAt()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        // Только админ может менять дату регистрации
        if ($user->role != User::ROLE_ADMIN) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        $data = json_decode(Yii::$app->request->rawBody, true);
        $id = $data['id'] ?? null;
        $createdAt = $data['created_at'] ?? null;

        if (!$createdAt) {
            return ['success' => false, 'message' => 'Дата регистрации обязательна'];
        }

        $worker = User::findOne($id);
        if (!$worker) {
            return ['success' => false, 'message' => 'Работник не найден'];
        }

        // Преобразуем строку даты в timestamp
        $timestamp = strtotime($createdAt);
        if ($timestamp === false) {
            return ['success' => false, 'message' => 'Неверный формат даты'];
        }

        $worker->created_at = $timestamp;
        $worker->updated_at = time();

        if ($worker->save(false)) {
            return [
                'success' => true,
                'message' => 'Дата регистрации успешно обновлена',
                'created_at' => $worker->created_at
            ];
        }

        return [
            'success' => false,
            'message' => 'Ошибка обновления даты регистрации'
        ];
    }

    // === Вспомогательные методы ===

    private function getAuthenticatedUser()
    {
        $authHeader = Yii::$app->request->headers->get('Authorization');
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return null;
        }

        $token = $matches[1];
        $payload = JwtHelper::validateToken($token);

        if (!$payload) {
            return null;
        }

        return User::findOne(['id' => $payload['userId']]);
    }
}
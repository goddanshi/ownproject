<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\Cors;
use yii\web\Response;
use common\components\JwtHelper;
use common\models\User;
use common\models\Permission;
use common\models\RolePermission;

class SettingsController extends Controller
{
    public function behaviors()
    {
        return [
            'corsFilter' => [
                'class' => Cors::class,
                'cors' => [
                    'Origin' => ['http://localhost:5173', 'http://185.213.240.236:5173', 'http://185.213.240.236'],
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

    public function actionGetRolePermissions()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $role = Yii::$app->request->get('role');

        if (!$role) {
            return ['success' => false, 'message' => 'Role is required'];
        }

        $authHeader = Yii::$app->request->headers->get('Authorization');
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $token = $matches[1];
        $payload = JwtHelper::validateToken($token);

        if (!$payload) {
            return ['success' => false, 'message' => 'Invalid token'];
        }

        $user = User::findOne(['id' => $payload['userId']]);
        if (!$user) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        // Тимлид не может просматривать права админа
        if ($user->role == User::ROLE_TEAMLEAD && $role == User::ROLE_ADMIN) {
            return ['success' => false, 'message' => 'Access denied: cannot view admin permissions'];
        }

        $rolePermissions = RolePermission::find()
            ->where(['role' => $role])
            ->select('permission_name')
            ->column();

        return [
            'success' => true,
            'permissions' => $rolePermissions ?: []
        ];
    }

    public function actionGetPermissions()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $authHeader = Yii::$app->request->headers->get('Authorization');
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $token = $matches[1];
        $payload = JwtHelper::validateToken($token);

        if (!$payload) {
            return ['success' => false, 'message' => 'Invalid token'];
        }

        $user = User::findOne(['id' => $payload['userId']]);
        if (!$user) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        $permissions = Permission::find()->orderBy(['category' => SORT_ASC, 'name' => SORT_ASC])->all();

        $grouped = [];
        foreach ($permissions as $perm) {
            $category = $perm->category ?: 'other';
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = [
                'name' => $perm->name,
                'label' => $perm->label,
                'description' => $perm->description,
            ];
        }

        return [
            'success' => true,
            'permissions' => $grouped
        ];
    }

    public function actionUpdateRolePermissions()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $data = json_decode(Yii::$app->request->rawBody, true);
        $role = $data['role'] ?? null;
        $permissions = $data['permissions'] ?? [];

        if (!$role) {
            return ['success' => false, 'message' => 'Role is required'];
        }

        $authHeader = Yii::$app->request->headers->get('Authorization');
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $token = $matches[1];
        $payload = JwtHelper::validateToken($token);

        if (!$payload) {
            return ['success' => false, 'message' => 'Invalid token'];
        }

        $user = User::findOne(['id' => $payload['userId']]);
        if (!$user) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        // Тимлид не может редактировать права админа
        if ($user->role == User::ROLE_TEAMLEAD && $role == User::ROLE_ADMIN) {
            return ['success' => false, 'message' => 'Access denied: cannot edit admin permissions'];
        }

        $transaction = Yii::$app->db->beginTransaction();

        try {
            RolePermission::deleteAll(['role' => $role]);

            foreach ($permissions as $permName) {
                $rolePermission = new RolePermission();
                $rolePermission->role = $role;
                $rolePermission->permission_name = $permName;
                $rolePermission->save();
            }

            $transaction->commit();

            return [
                'success' => true,
                'message' => 'Права успешно обновлены'
            ];
        } catch (\Exception $e) {
            $transaction->rollBack();
            return [
                'success' => false,
                'message' => 'Ошибка сохранения: ' . $e->getMessage()
            ];
        }
    }
}
<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\Cors;
use yii\web\Response;
use app\helpers\JwtHelper;
use app\models\User;
use app\models\Permission;
use app\models\RolePermission;

class SettingsController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // ВАЖНО: CORS должен быть ПЕРВЫМ
        $behaviors['corsFilter'] = [
            'class' => Cors::class,
            'cors' => [
                'Origin' => ['http://localhost:5173', 'http://81.19.136.133:5173', 'http://81.19.136.133'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => true,
                'Access-Control-Max-Age' => 86400,
                'Access-Control-Expose-Headers' => ['*'],
            ],
        ];

        return $behaviors;
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

    /**
     * Получить все права с группировкой по категориям
     */
    public function actionGetPermissions()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        // Проверка JWT
        $authHeader = Yii::$app->request->headers->get('Authorization');
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $token = $matches[1];
        $payload = JwtHelper::validateToken($token);

        if (!$payload) {
            return ['success' => false, 'message' => 'Invalid token'];
        }

        $user = User::findOne(['id' => $payload['user_id']]);
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }

        // Только админы могут управлять правами
        if ($user->role != User::ROLE_ADMIN) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        // Получаем все права
        $permissions = Permission::find()->orderBy(['category' => SORT_ASC, 'name' => SORT_ASC])->all();

        // Группируем по категориям
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

    /**
     * Получить права для конкретной роли
     */
    public function actionGetRolePermissions()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $role = Yii::$app->request->get('role');

        if (!$role) {
            return ['success' => false, 'message' => 'Role is required'];
        }

        // Проверка JWT
        $authHeader = Yii::$app->request->headers->get('Authorization');
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $token = $matches[1];
        $payload = JwtHelper::validateToken($token);

        if (!$payload) {
            return ['success' => false, 'message' => 'Invalid token'];
        }

        $user = User::findOne(['id' => $payload['user_id']]);
        if (!$user) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        $rolePermissions = RolePermission::find()
            ->where(['role' => $role])
            ->select('permission_name')
            ->column();

        return [
            'success' => true,
            'permissions' => $rolePermissions
        ];
    }

    /**
     * Обновить права для роли
     */
    public function actionUpdateRolePermissions()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $data = json_decode(Yii::$app->request->rawBody, true);
        $role = $data['role'] ?? null;
        $permissions = $data['permissions'] ?? [];

        if (!$role) {
            return ['success' => false, 'message' => 'Role is required'];
        }

        // Проверка JWT
        $authHeader = Yii::$app->request->headers->get('Authorization');
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $token = $matches[1];
        $payload = JwtHelper::validateToken($token);

        if (!$payload) {
            return ['success' => false, 'message' => 'Invalid token'];
        }

        $user = User::findOne(['id' => $payload['user_id']]);
        if (!$user || $user->role != User::ROLE_ADMIN) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        $transaction = Yii::$app->db->beginTransaction();

        try {
            // Удаляем старые права
            RolePermission::deleteAll(['role' => $role]);

            // Добавляем новые
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
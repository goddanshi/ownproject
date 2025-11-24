<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\Cors;
use yii\web\Response;
use common\components\JwtHelper;
use common\models\Folder;
use common\models\User;

class FoldersController extends Controller
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

    /**
     * Получить все папки (с деревом)
     */
    public function actionIndex()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        try {
            // Получаем папки доступные пользователю
            $folders = Folder::getAccessibleFolders($user);

            // Формируем дерево
            $tree = $this->buildFolderTree($folders);

            return [
                'success' => true,
                'folders' => $folders,
                'tree' => $tree,
            ];
        } catch (\Exception $e) {
            Yii::error('Ошибка получения папок: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ошибка получения папок',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Получить дерево папок для команды
     */
    public function actionTree($teamId = null)
    {
        $user = Yii::$app->user->identity;

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Пользователь не авторизован',
            ];
        }

        try {
            $tree = Folder::getTree($teamId);

            return [
                'success' => true,
                'tree' => $tree,
            ];
        } catch (\Exception $e) {
            Yii::error('Ошибка получения дерева папок: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ошибка получения дерева папок',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Получить одну папку
     */
    public function actionView($id)
    {
        $user = Yii::$app->user->identity;

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Пользователь не авторизован',
            ];
        }

        try {
            $folder = Folder::findOne($id);

            return [
                'success' => true,
                'folder' => [
                    'id' => $folder->id,
                    'name' => $folder->name,
                    'description' => $folder->description,
                    'parent_id' => $folder->parent_id,
                    'team_id' => $folder->team_id,
                    'team' => $folder->team ? [
                        'id' => $folder->team->id,
                        'name' => $folder->team->name,
                    ] : null,
                    'parent' => $folder->parent ? [
                        'id' => $folder->parent->id,
                        'name' => $folder->parent->name,
                    ] : null,
                    'children' => array_map(function($child) {
                        return [
                            'id' => $child->id,
                            'name' => $child->name,
                            'type' => 'folder',
                        ];
                    }, $folder->children),
                    'projects' => array_map(function($project) {
                        return [
                            'id' => $project->id,
                            'name' => $project->name,
                            'tasks_count' => $project->getTasks()->count(),
                            'type' => 'project',
                        ];
                    }, $folder->projects),
                    'path' => array_map(function($f) {
                        return [
                            'id' => $f->id,
                            'name' => $f->name,
                        ];
                    }, $folder->getPath()),
                    'created_at' => $folder->created_at,
                    'updated_at' => $folder->updated_at,
                ],
            ];
        } catch (\Exception $e) {
            Yii::error('Ошибка получения папки: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Создать папку
     */
    public function actionCreate()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $data = json_decode(Yii::$app->request->rawBody, true);

        $folder = new Folder();
        $folder->name = $data['name'] ?? '';
        $folder->description = $data['description'] ?? '';
        $folder->parent_id = !empty($data['parent_id']) ? (int)$data['parent_id'] : null;
        $folder->team_id = null;
        $folder->created_by = $user->id;

        if (!$folder->save()) {
            return [
                'success' => false,
                'message' => 'Ошибка создания папки',
                'errors' => $folder->errors
            ];
        }

        return [
            'success' => true,
            'message' => 'Папка успешно создана',
            'folder' => [
                'id' => $folder->id,
                'name' => $folder->name,
                'description' => $folder->description,
                'parent_id' => $folder->parent_id,
            ]
        ];
    }

    /**
     * Обновить папку
     */
    public function actionUpdate($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $data = json_decode(Yii::$app->request->rawBody, true);

        $folder = Folder::findOne($id);
        if (!$folder) {
            return ['success' => false, 'message' => 'Папка не найдена'];
        }

        // Проверка прав доступа
        if (!$this->canManageFolder($folder, $user)) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        $folder->name = $data['name'] ?? $folder->name;
        $folder->description = $data['description'] ?? $folder->description;
        $folder->parent_id = isset($data['parent_id']) ? (!empty($data['parent_id']) ? (int)$data['parent_id'] : null) : $folder->parent_id;

        if (!$folder->save()) {
            return [
                'success' => false,
                'message' => 'Ошибка обновления папки',
                'errors' => $folder->errors
            ];
        }

        return [
            'success' => true,
            'message' => 'Папка успешно обновлена',
            'folder' => [
                'id' => $folder->id,
                'name' => $folder->name,
                'description' => $folder->description,
                'parent_id' => $folder->parent_id,
            ]
        ];
    }

    /**
     * Удалить папку
     */
    public function actionDelete($id)
    {
        $user = Yii::$app->user->identity;

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Пользователь не авторизован',
            ];
        }

        try {
            $folder = Folder::findOne($id);

            if (!$folder->delete()) {
                return [
                    'success' => false,
                    'message' => 'Ошибка удаления папки',
                ];
            }

            return [
                'success' => true,
                'message' => 'Папка успешно удалена',
            ];
        } catch (\Exception $e) {
            Yii::error('Ошибка удаления папки: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Проверка прав доступа к папке
     */
    protected function canAccessFolder($folder, $user)
    {
        // Админ имеет доступ ко всем папкам
        if ($user->role === 1) {
            return true;
        }

        // Проверяем, является ли пользователь участником команды папки
        return $folder->isParticipant($user->id);
    }

    /**
     * Проверка прав на управление папкой
     */
    protected function canManageFolder($folder, $user)
    {
        // Админ может управлять всеми папками
        if ($user->role === 1) {
            return true;
        }

        // Тимлид команды может управлять папками своей команды
        if ($folder->team && $folder->team->isTeamlead($user->id)) {
            return true;
        }

        return false;
    }

    /**
     * Построить дерево из плоского массива папок
     */
    protected function buildFolderTree($folders, $parentId = null)
    {
        $tree = [];

        foreach ($folders as $folder) {
            if ($folder->parent_id == $parentId) {
                $node = [
                    'id' => $folder->id,
                    'name' => $folder->name,
                    'description' => $folder->description,
                    'type' => 'folder',
                    'children' => $this->buildFolderTree($folders, $folder->id),
                ];

                // Добавляем проекты в узел
                foreach ($folder->projects as $project) {
                    $node['children'][] = [
                        'id' => $project->id,
                        'name' => $project->name,
                        'description' => $project->description,
                        'type' => 'project',
                        'tasks_count' => $project->getTasks()->count(),
                    ];
                }

                $tree[] = $node;
            }
        }

        return $tree;
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

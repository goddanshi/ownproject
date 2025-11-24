<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\Cors;
use yii\web\Response;
use common\components\JwtHelper;
use common\models\User;
use common\models\Project;
use common\models\Team;

class ProjectsController extends Controller
{
    public function behaviors()
    {
        return [
            'corsFilter' => [
                'class' => Cors::class,
                'cors' => [
                    'Origin' => ['http://localhost:5173', 'http://185.213.240.236:5173', 'http://185.104.113.132', 'http://185.213.240.236'],
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

    // Получить список проектов
    public function actionIndex()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $projects = Project::getAccessibleProjects($user);

        $result = [];
        foreach ($projects as $project) {
            $result[] = [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'folder_id' => $project->folder_id,
                'team' => [
                    'id' => $project->team->id,
                    'name' => $project->team->name,
                ],
                'tasks_count' => count($project->tasks),
                'participants_count' => count($project->getAllParticipants()),
                'created_at' => $project->created_at,
            ];
        }

        return [
            'success' => true,
            'projects' => $result
        ];
    }

    // Получить детальную информацию о проекте
    public function actionView()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $id = Yii::$app->request->get('id');
        $project = Project::findOne($id);

        if (!$project) {
            return ['success' => false, 'message' => 'Проект не найден'];
        }


        $participants = [];
        foreach ($project->getAllParticipants() as $participant) {
            $participants[] = [
                'id' => $participant->id,
                'username' => $participant->username,
                'email' => $participant->email,
                'name' => $participant->name,
                'surname' => $participant->surname,
            ];
        }

        $tasks = [];
        foreach ($project->tasks as $task) {
            $tasks[] = [
                'id' => $task->id,
                'title' => $task->title,
                'status' => $task->status,
                'status_label' => $task->getStatusLabel(),
                'priority' => $task->priority,
                'priority_label' => $task->getPriorityLabel(),
                'deadline' => $task->deadline,
                'created_at' => $task->created_at,
            ];
        }

        return [
            'success' => true,
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'team' => [
                    'id' => $project->team->id,
                    'name' => $project->team->name,
                    'teamlead' => [
                        'id' => $project->team->teamlead->id,
                        'username' => $project->team->teamlead->username,
                        'name' => $project->team->teamlead->name,
                        'surname' => $project->team->teamlead->surname,
                    ],
                ],
                'participants' => $participants,
                'tasks' => $tasks,
                'created_at' => $project->created_at,
                'updated_at' => $project->updated_at,
            ]
        ];
    }

    // Создать проект
    public function actionCreate()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $data = json_decode(Yii::$app->request->rawBody, true);

        $teamId = $data['team_id'] ?? null;
        $team = Team::findOne($teamId);

        if (!$team) {
            return ['success' => false, 'message' => 'Команда не найдена'];
        }


        $project = new Project();
        $project->name = $data['name'] ?? '';
        $project->description = $data['description'] ?? '';
        $project->team_id = $teamId;
        $project->folder_id = !empty($data['folder_id']) ? (int)$data['folder_id'] : null;

        if (!$project->save()) {
            return [
                'success' => false,
                'message' => 'Ошибка создания проекта',
                'errors' => $project->errors
            ];
        }

        return [
            'success' => true,
            'message' => 'Проект успешно создан',
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ]
        ];
    }

    // Обновить проект
    public function actionUpdate()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $data = json_decode(Yii::$app->request->rawBody, true);
        $id = Yii::$app->request->get('id') ?? $data['id'] ?? null;

        $project = Project::findOne($id);
        if (!$project) {
            return ['success' => false, 'message' => 'Проект не найден'];
        }


        $project->name = $data['name'] ?? $project->name;
        $project->description = $data['description'] ?? $project->description;
        $project->folder_id = isset($data['folder_id']) ? (!empty($data['folder_id']) ? (int)$data['folder_id'] : null) : $project->folder_id;

        if (!$project->save()) {
            return [
                'success' => false,
                'message' => 'Ошибка обновления проекта',
                'errors' => $project->errors
            ];
        }

        return [
            'success' => true,
            'message' => 'Проект успешно обновлен'
        ];
    }

    // Удалить проект
    public function actionDelete()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $id = Yii::$app->request->get('id');
        $project = Project::findOne($id);

        if (!$project) {
            return ['success' => false, 'message' => 'Проект не найден'];
        }


        if ($project->delete()) {
            return [
                'success' => true,
                'message' => 'Проект успешно удален'
            ];
        }

        return [
            'success' => false,
            'message' => 'Ошибка удаления проекта'
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

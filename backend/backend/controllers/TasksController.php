<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\Cors;
use yii\web\Response;
use common\components\JwtHelper;
use common\models\User;
use common\models\Task;
use common\models\Project;
use common\models\TimeTracking;

class TasksController extends Controller
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

    // Получить список задач
    public function actionIndex()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $tasks = Task::getAccessibleTasks($user);

        $result = [];
        foreach ($tasks as $task) {
            $assignees = [];
            foreach ($task->assignees as $assignee) {
                $assignees[] = [
                    'id' => $assignee->id,
                    'username' => $assignee->username,
                    'name' => $assignee->name,
                    'surname' => $assignee->surname,
                ];
            }

            $result[] = [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status,
                'status_label' => $task->getStatusLabel(),
                'priority' => $task->priority,
                'priority_label' => $task->getPriorityLabel(),
                'deadline' => $task->deadline,
                'project' => [
                    'id' => $task->project->id,
                    'name' => $task->project->name,
                ],
                'assignees' => $assignees,
                'total_time' => $task->getTotalTime(),
                'created_at' => $task->created_at,
            ];
        }

        return [
            'success' => true,
            'tasks' => $result
        ];
    }

    // Получить активные задачи (статусы: К выполнению, В работе, На проверке)
    public function actionGetActiveTasks()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        // Получаем активные задачи со статусами 1, 2, 3
        $tasks = Task::find()
            ->with(['project', 'assignees', 'creator'])
            ->where(['status' => [Task::STATUS_TODO, Task::STATUS_IN_PROGRESS, Task::STATUS_REVIEW]])
            ->orderBy(['priority' => SORT_DESC, 'deadline' => SORT_ASC])
            ->all();

        $result = [];
        foreach ($tasks as $task) {
            $assignees = [];
            foreach ($task->assignees as $assignee) {
                $assignees[] = [
                    'id' => $assignee->id,
                    'username' => $assignee->username,
                    'name' => $assignee->name,
                    'surname' => $assignee->surname,
                ];
            }

            $result[] = [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status,
                'status_label' => $task->getStatusLabel(),
                'priority' => $task->priority,
                'priority_label' => $task->getPriorityLabel(),
                'deadline' => $task->deadline,
                'project' => [
                    'id' => $task->project->id,
                    'name' => $task->project->name,
                ],
                'assignees' => $assignees,
                'total_time' => $task->getTotalTime(),
                'created_at' => $task->created_at,
            ];
        }

        return [
            'success' => true,
            'tasks' => $result
        ];
    }

    // Получить детальную информацию о задаче
    public function actionView()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $id = Yii::$app->request->get('id');
        $task = Task::findOne($id);

        if (!$task) {
            return ['success' => false, 'message' => 'Задача не найдена'];
        }


        $assignees = [];
        foreach ($task->assignees as $assignee) {
            $assignees[] = [
                'id' => $assignee->id,
                'username' => $assignee->username,
                'email' => $assignee->email,
                'name' => $assignee->name,
                'surname' => $assignee->surname,
                'time_spent' => $task->getUserTime($assignee->id),
            ];
        }

        $timeTrackings = [];
        foreach ($task->timeTrackings as $tracking) {
            $timeTrackings[] = [
                'id' => $tracking->id,
                'user' => [
                    'id' => $tracking->user->id,
                    'username' => $tracking->user->username,
                    'name' => $tracking->user->name,
                    'surname' => $tracking->user->surname,
                ],
                'started_at' => $tracking->started_at,
                'ended_at' => $tracking->ended_at,
                'duration' => $tracking->duration,
                'formatted_duration' => $tracking->getFormattedDuration(),
                'description' => $tracking->description,
            ];
        }

        return [
            'success' => true,
            'task' => [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status,
                'status_label' => $task->getStatusLabel(),
                'priority' => $task->priority,
                'priority_label' => $task->getPriorityLabel(),
                'deadline' => $task->deadline,
                'project' => [
                    'id' => $task->project->id,
                    'name' => $task->project->name,
                ],
                'creator' => [
                    'id' => $task->creator->id,
                    'username' => $task->creator->username,
                    'name' => $task->creator->name,
                    'surname' => $task->creator->surname,
                ],
                'assignees' => $assignees,
                'time_trackings' => $timeTrackings,
                'total_time' => $task->getTotalTime(),
                'created_at' => $task->created_at,
                'updated_at' => $task->updated_at,
            ]
        ];
    }

    // Создать задачу
    public function actionCreate()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $data = json_decode(Yii::$app->request->rawBody, true);

        $projectId = $data['project_id'] ?? null;
        $project = Project::findOne($projectId);

        if (!$project) {
            return ['success' => false, 'message' => 'Проект не найден'];
        }


        $task = new Task();
        $task->title = $data['title'] ?? '';
        $task->description = $data['description'] ?? '';
        $task->project_id = $projectId;
        $task->status = $data['status'] ?? Task::STATUS_TODO;
        $task->priority = $data['priority'] ?? Task::PRIORITY_MEDIUM;
        $task->deadline = $data['deadline'] ?? null;
        $task->created_by = $user->id;

        if (!$task->save()) {
            return [
                'success' => false,
                'message' => 'Ошибка создания задачи',
                'errors' => $task->errors
            ];
        }

        // Назначаем участников если указаны
        if (!empty($data['assignee_ids'])) {
            foreach ($data['assignee_ids'] as $assigneeId) {
                $task->assignToUser($assigneeId);
            }
        }

        return [
            'success' => true,
            'message' => 'Задача успешно создана',
            'task' => [
                'id' => $task->id,
                'title' => $task->title,
            ]
        ];
    }

    // Обновить задачу
    public function actionUpdate()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $data = json_decode(Yii::$app->request->rawBody, true);
        $id = $data['id'] ?? null;

        $task = Task::findOne($id);
        if (!$task) {
            return ['success' => false, 'message' => 'Задача не найдена'];
        }


        $task->title = $data['title'] ?? $task->title;
        $task->description = $data['description'] ?? $task->description;
        $task->status = $data['status'] ?? $task->status;
        $task->priority = $data['priority'] ?? $task->priority;
        $task->deadline = $data['deadline'] ?? $task->deadline;

        if (!$task->save()) {
            return [
                'success' => false,
                'message' => 'Ошибка обновления задачи',
                'errors' => $task->errors
            ];
        }

        return [
            'success' => true,
            'message' => 'Задача успешно обновлена'
        ];
    }

    // Удалить задачу
    public function actionDelete()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $id = Yii::$app->request->get('id');
        $task = Task::findOne($id);

        if (!$task) {
            return ['success' => false, 'message' => 'Задача не найдена'];
        }


        if ($task->delete()) {
            return [
                'success' => true,
                'message' => 'Задача успешно удалена'
            ];
        }

        return [
            'success' => false,
            'message' => 'Ошибка удаления задачи'
        ];
    }

    // Назначить задачу пользователю
    public function actionAssignUser()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $data = json_decode(Yii::$app->request->rawBody, true);
        $taskId = $data['task_id'] ?? null;
        $userId = $data['user_id'] ?? null;

        $task = Task::findOne($taskId);
        if (!$task) {
            return ['success' => false, 'message' => 'Задача не найдена'];
        }

        if ($task->assignToUser($userId)) {
            return [
                'success' => true,
                'message' => 'Пользователь назначен на задачу'
            ];
        }

        return [
            'success' => false,
            'message' => 'Ошибка назначения пользователя'
        ];
    }

    // Снять назначение с пользователя
    public function actionUnassignUser()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $data = json_decode(Yii::$app->request->rawBody, true);
        $taskId = $data['task_id'] ?? null;
        $userId = $data['user_id'] ?? null;

        $task = Task::findOne($taskId);
        if (!$task) {
            return ['success' => false, 'message' => 'Задача не найдена'];
        }

        if ($task->unassignFromUser($userId)) {
            return [
                'success' => true,
                'message' => 'Пользователь снят с задачи'
            ];
        }

        return [
            'success' => false,
            'message' => 'Ошибка снятия пользователя'
        ];
    }

    // Начать отслеживание времени
    public function actionStartTracking()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $data = json_decode(Yii::$app->request->rawBody, true);
        $taskId = $data['task_id'] ?? null;
        $description = $data['description'] ?? null;

        $task = Task::findOne($taskId);
        if (!$task) {
            return ['success' => false, 'message' => 'Задача не найдена'];
        }

        $tracking = TimeTracking::startTracking($taskId, $user->id, $description);

        if ($tracking) {
            return [
                'success' => true,
                'message' => 'Отслеживание времени начато',
                'tracking' => [
                    'id' => $tracking->id,
                    'started_at' => $tracking->started_at,
                ]
            ];
        }

        return [
            'success' => false,
            'message' => 'Ошибка начала отслеживания времени'
        ];
    }

    // Остановить отслеживание времени
    public function actionStopTracking()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $data = json_decode(Yii::$app->request->rawBody, true);
        $taskId = $data['task_id'] ?? null;

        $task = Task::findOne($taskId);
        if (!$task) {
            return ['success' => false, 'message' => 'Задача не найдена'];
        }

        $tracking = TimeTracking::getActiveTracking($taskId, $user->id);

        if (!$tracking) {
            return ['success' => false, 'message' => 'Активное отслеживание не найдено'];
        }

        if ($tracking->stopTracking()) {
            return [
                'success' => true,
                'message' => 'Отслеживание времени остановлено',
                'tracking' => [
                    'id' => $tracking->id,
                    'duration' => $tracking->duration,
                    'formatted_duration' => $tracking->getFormattedDuration(),
                ]
            ];
        }

        return [
            'success' => false,
            'message' => 'Ошибка остановки отслеживания времени'
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

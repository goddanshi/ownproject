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
use common\models\TaskMessage;
use common\models\TaskTodo;

class TasksController extends Controller
{
    public function behaviors()
    {
        return [
            'corsFilter' => [
                'class' => Cors::class,
                'cors' => [
                    'Origin' => ['http://localhost:5173', 'http://185.213.240.236:5173', 'http://91.218.245.170', 'http://91.218.245.170:8080', 'http://185.213.240.236'],
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
                'start_date' => $task->start_date,
                'deadline' => $task->deadline,
                'estimated_time' => $task->estimated_time,
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
                'start_date' => $task->start_date,
                'deadline' => $task->deadline,
                'estimated_time' => $task->estimated_time,
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

        $todos = [];
        foreach ($task->todos as $todo) {
            $todos[] = [
                'id' => $todo->id,
                'title' => $todo->title,
                'deadline' => $todo->deadline,
                'is_completed' => (bool)$todo->is_completed,
                'position' => $todo->position,
                'created_at' => $todo->created_at,
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
                'start_date' => $task->start_date,
                'deadline' => $task->deadline,
                'estimated_time' => $task->estimated_time,
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
                'todos' => $todos,
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
        $task->start_date = $data['start_date'] ?? null;
        $task->deadline = $data['deadline'] ?? null;
        $task->estimated_time = $data['estimated_time'] ?? null;
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

        // Сохраняем старые значения для логирования
        $oldStatus = $task->status;
        $oldPriority = $task->priority;
        $oldDeadline = $task->deadline;

        $task->title = $data['title'] ?? $task->title;
        $task->description = $data['description'] ?? $task->description;
        $task->status = $data['status'] ?? $task->status;
        $task->priority = $data['priority'] ?? $task->priority;
        $task->start_date = $data['start_date'] ?? $task->start_date;
        $task->deadline = $data['deadline'] ?? $task->deadline;
        $task->estimated_time = $data['estimated_time'] ?? $task->estimated_time;

        if (!$task->save()) {
            return [
                'success' => false,
                'message' => 'Ошибка обновления задачи',
                'errors' => $task->errors
            ];
        }

        // Создаем системные сообщения о изменениях
        if ($oldStatus != $task->status) {
            $statusChangeData = [
                'old_status' => $oldStatus,
                'new_status' => $task->status,
            ];

            // Добавляем ID проверяющего если задача отправлена на проверку
            if ($task->status == Task::STATUS_REVIEW && !empty($data['reviewer_id'])) {
                $statusChangeData['reviewer_id'] = $data['reviewer_id'];
            }

            TaskMessage::createSystemMessage($task->id, $user->id, 'status_changed', $statusChangeData);
        }

        if ($oldPriority != $task->priority) {
            TaskMessage::createSystemMessage($task->id, $user->id, 'priority_changed', [
                'old_priority' => $oldPriority,
                'new_priority' => $task->priority,
            ]);
        }

        if ($oldDeadline != $task->deadline) {
            TaskMessage::createSystemMessage($task->id, $user->id, 'deadline_changed', [
                'new_deadline' => $task->deadline,
            ]);
        }

        return [
            'success' => true,
            'message' => 'Задача успешно обновлена'
        ];
    }

    // Быстрое обновление статуса задачи
    public function actionUpdateStatus()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $data = json_decode(Yii::$app->request->rawBody, true);
        $taskId = $data['task_id'] ?? null;
        $newStatus = $data['status'] ?? null;

        if (!$taskId || !$newStatus) {
            return ['success' => false, 'message' => 'Необходимо указать task_id и status'];
        }

        $task = Task::findOne($taskId);
        if (!$task) {
            return ['success' => false, 'message' => 'Задача не найдена'];
        }

        $oldStatus = $task->status;
        $task->status = $newStatus;

        if (!$task->save()) {
            return [
                'success' => false,
                'message' => 'Ошибка обновления статуса',
                'errors' => $task->errors
            ];
        }

        // Создаем системное сообщение о смене статуса
        if ($oldStatus != $task->status) {
            TaskMessage::createSystemMessage($task->id, $user->id, 'status_changed', [
                'old_status' => $oldStatus,
                'new_status' => $task->status,
            ]);
        }

        return [
            'success' => true,
            'message' => 'Статус задачи обновлен',
            'task' => [
                'id' => $task->id,
                'status' => $task->status,
                'status_label' => $task->getStatusLabel()
            ]
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
            // Создаем системное сообщение о назначении
            TaskMessage::createSystemMessage($taskId, $user->id, 'assignee_added', [
                'assignee_id' => $userId,
            ]);

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
            // Создаем системное сообщение об удалении назначения
            TaskMessage::createSystemMessage($taskId, $user->id, 'assignee_removed', [
                'assignee_id' => $userId,
            ]);

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
            // Создаем системное сообщение о начале отслеживания
            TaskMessage::createSystemMessage($taskId, $user->id, 'tracking_started');

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
            // Создаем системное сообщение об окончании отслеживания
            TaskMessage::createSystemMessage($taskId, $user->id, 'tracking_stopped', [
                'duration' => $tracking->duration,
            ]);

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

    // === Методы чата задачи ===

    /**
     * Получить сообщения чата задачи (с поддержкой long polling)
     */
    public function actionGetMessages()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $taskId = Yii::$app->request->get('task_id');
        $lastMessageId = Yii::$app->request->get('last_message_id');
        $timeout = (int) Yii::$app->request->get('timeout', 30); // Long polling timeout

        if (!$taskId) {
            return ['success' => false, 'message' => 'task_id обязателен'];
        }

        $task = Task::findOne($taskId);
        if (!$task) {
            return ['success' => false, 'message' => 'Задача не найдена'];
        }

        // Long polling: ждем появления новых сообщений
        $startTime = time();
        $maxWaitTime = min($timeout, 30); // Максимум 30 секунд

        while (time() - $startTime < $maxWaitTime) {
            $messages = TaskMessage::getMessages($taskId, 50, $lastMessageId);

            if (!empty($messages)) {
                // Новые сообщения найдены
                $result = [];
                foreach ($messages as $message) {
                    $result[] = $message->toArray();
                }

                return [
                    'success' => true,
                    'messages' => $result
                ];
            }

            // Ждем 1 секунду перед следующей проверкой
            sleep(1);
        }

        // Таймаут истек, новых сообщений нет
        return [
            'success' => true,
            'messages' => []
        ];
    }

    /**
     * Получить все сообщения чата задачи (первоначальная загрузка)
     */
    public function actionGetAllMessages()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $taskId = Yii::$app->request->get('task_id');
        $limit = (int) Yii::$app->request->get('limit', 50);

        if (!$taskId) {
            return ['success' => false, 'message' => 'task_id обязателен'];
        }

        $task = Task::findOne($taskId);
        if (!$task) {
            return ['success' => false, 'message' => 'Задача не найдена'];
        }

        $messages = TaskMessage::getMessages($taskId, $limit);
        $result = [];
        foreach ($messages as $message) {
            $result[] = $message->toArray();
        }

        return [
            'success' => true,
            'messages' => $result
        ];
    }

    /**
     * Отправить сообщение в чат задачи
     */
    public function actionSendMessage()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $data = json_decode(Yii::$app->request->rawBody, true);
        $taskId = $data['task_id'] ?? null;
        $messageText = $data['message'] ?? null;

        if (!$taskId || !$messageText) {
            return ['success' => false, 'message' => 'task_id и message обязательны'];
        }

        $task = Task::findOne($taskId);
        if (!$task) {
            return ['success' => false, 'message' => 'Задача не найдена'];
        }

        $message = TaskMessage::createMessage($taskId, $user->id, trim($messageText));

        if ($message) {
            return [
                'success' => true,
                'message' => $message->toArray()
            ];
        }

        return [
            'success' => false,
            'message' => 'Ошибка отправки сообщения'
        ];
    }

    // === Методы для работы с TODO ===

    /**
     * Создать TODO элемент
     */
    public function actionCreateTodo()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $data = json_decode(Yii::$app->request->rawBody, true);
        $taskId = $data['task_id'] ?? null;
        $title = $data['title'] ?? null;
        $deadline = $data['deadline'] ?? null;

        if (!$taskId || !$title) {
            return ['success' => false, 'message' => 'task_id и title обязательны'];
        }

        $task = Task::findOne($taskId);
        if (!$task) {
            return ['success' => false, 'message' => 'Задача не найдена'];
        }

        $todo = new TaskTodo();
        $todo->task_id = $taskId;
        $todo->title = trim($title);
        $todo->deadline = $deadline;
        $todo->position = TaskTodo::getNextPosition($taskId);
        $todo->is_completed = false;

        if ($todo->save()) {
            return [
                'success' => true,
                'todo' => [
                    'id' => $todo->id,
                    'title' => $todo->title,
                    'deadline' => $todo->deadline,
                    'is_completed' => (bool)$todo->is_completed,
                    'position' => $todo->position,
                    'created_at' => $todo->created_at,
                ]
            ];
        }

        return [
            'success' => false,
            'message' => 'Ошибка создания TODO',
            'errors' => $todo->errors
        ];
    }

    /**
     * Обновить TODO элемент
     */
    public function actionUpdateTodo()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $data = json_decode(Yii::$app->request->rawBody, true);
        $todoId = $data['id'] ?? null;

        if (!$todoId) {
            return ['success' => false, 'message' => 'id обязателен'];
        }

        $todo = TaskTodo::findOne($todoId);
        if (!$todo) {
            return ['success' => false, 'message' => 'TODO не найдено'];
        }

        if (isset($data['title'])) {
            $todo->title = trim($data['title']);
        }

        if (isset($data['is_completed'])) {
            $todo->is_completed = (bool)$data['is_completed'];
        }

        if ($todo->save()) {
            return [
                'success' => true,
                'todo' => [
                    'id' => $todo->id,
                    'title' => $todo->title,
                    'is_completed' => (bool)$todo->is_completed,
                    'position' => $todo->position,
                ]
            ];
        }

        return [
            'success' => false,
            'message' => 'Ошибка обновления TODO',
            'errors' => $todo->errors
        ];
    }

    /**
     * Переключить статус выполнения TODO
     */
    public function actionToggleTodo()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $data = json_decode(Yii::$app->request->rawBody, true);
        $todoId = $data['id'] ?? null;

        if (!$todoId) {
            return ['success' => false, 'message' => 'id обязателен'];
        }

        $todo = TaskTodo::findOne($todoId);
        if (!$todo) {
            return ['success' => false, 'message' => 'TODO не найдено'];
        }

        if ($todo->toggleCompleted()) {
            return [
                'success' => true,
                'todo' => [
                    'id' => $todo->id,
                    'is_completed' => (bool)$todo->is_completed,
                ]
            ];
        }

        return ['success' => false, 'message' => 'Ошибка переключения статуса'];
    }

    /**
     * Удалить TODO элемент
     */
    public function actionDeleteTodo($id = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        // Получаем ID из параметра URL или из GET параметра
        $todoId = $id ?? Yii::$app->request->get('id');

        if (!$todoId) {
            return ['success' => false, 'message' => 'id обязателен'];
        }

        $todo = TaskTodo::findOne($todoId);
        if (!$todo) {
            return ['success' => false, 'message' => 'TODO не найдено'];
        }

        if ($todo->delete()) {
            return [
                'success' => true,
                'message' => 'TODO удалено'
            ];
        }

        return ['success' => false, 'message' => 'Ошибка удаления TODO'];
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

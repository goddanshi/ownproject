<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * TaskMessage model
 *
 * @property int $id
 * @property int $task_id
 * @property int $user_id
 * @property string $message
 * @property bool $is_system
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Task $task
 * @property User $user
 */
class TaskMessage extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%task_messages}}';
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    public function rules()
    {
        return [
            [['task_id', 'user_id', 'message'], 'required'],
            [['task_id', 'user_id'], 'integer'],
            ['message', 'string'],
            ['message', 'trim'],
            ['is_system', 'boolean'],
            ['is_system', 'default', 'value' => false],
            ['task_id', 'exist', 'targetClass' => Task::class, 'targetAttribute' => 'id'],
            ['user_id', 'exist', 'targetClass' => User::class, 'targetAttribute' => 'id'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'task_id' => 'Задача',
            'user_id' => 'Пользователь',
            'message' => 'Сообщение',
            'created_at' => 'Дата создания',
            'updated_at' => 'Обновлено',
        ];
    }

    /**
     * Связь с задачей
     */
    public function getTask()
    {
        return $this->hasOne(Task::class, ['id' => 'task_id']);
    }

    /**
     * Связь с пользователем
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * Получить последние сообщения для задачи
     * @param int $taskId ID задачи
     * @param int $limit Лимит сообщений
     * @param int|null $lastMessageId ID последнего полученного сообщения (для long polling)
     * @return array
     */
    public static function getMessages($taskId, $limit = 50, $lastMessageId = null)
    {
        $query = static::find()
            ->with('user')
            ->where(['task_id' => $taskId])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limit);

        if ($lastMessageId !== null) {
            $query->andWhere(['>', 'id', $lastMessageId]);
        }

        $messages = $query->all();

        // Возвращаем в обратном порядке (от старых к новым)
        return array_reverse($messages);
    }

    /**
     * Создать новое сообщение
     * @param int $taskId
     * @param int $userId
     * @param string $message
     * @param bool $isSystem
     * @return TaskMessage|null
     */
    public static function createMessage($taskId, $userId, $message, $isSystem = false)
    {
        $model = new static();
        $model->task_id = $taskId;
        $model->user_id = $userId;
        $model->message = $message;
        $model->is_system = $isSystem;

        if ($model->save()) {
            // Загружаем связь с пользователем
            $model->refresh();
            return $model;
        }

        return null;
    }

    /**
     * Создать системное сообщение о событии задачи
     * @param int $taskId
     * @param int $userId ID пользователя, инициировавшего событие
     * @param string $event Тип события (status_changed, tracking_started, etc.)
     * @param array $data Дополнительные данные для сообщения
     * @return TaskMessage|null
     */
    public static function createSystemMessage($taskId, $userId, $event, $data = [])
    {
        $user = User::findOne($userId);
        if (!$user) {
            return null;
        }

        $userName = "{$user->name} {$user->surname}";
        $message = '';

        switch ($event) {
            case 'status_changed':
                $statusLabels = [
                    1 => 'К выполнению',
                    2 => 'В работе',
                    3 => 'На проверке',
                    4 => 'Выполнено',
                ];
                $oldStatus = $statusLabels[$data['old_status']] ?? 'Неизвестно';
                $newStatus = $statusLabels[$data['new_status']] ?? 'Неизвестно';
                $message = "[СТАТУС] Статус задачи изменен с \"{$oldStatus}\" на \"{$newStatus}\"";

                // Если задача отправлена на проверку и указан проверяющий
                if ($data['new_status'] == 3 && !empty($data['reviewer_id'])) {
                    $reviewer = User::findOne($data['reviewer_id']);
                    if ($reviewer) {
                        $reviewerName = "{$reviewer->name} {$reviewer->surname}";
                        $message .= " (проверяющий: {$reviewerName})";
                    }
                }
                break;

            case 'tracking_started':
                $message = "[СТАРТ] {$userName} начал выполнять задачу";
                break;

            case 'tracking_stopped':
                $duration = $data['duration'] ?? 0;
                $hours = floor($duration / 3600);
                $minutes = floor(($duration % 3600) / 60);
                $timeStr = "{$hours}ч {$minutes}м";
                $message = "[СТОП] {$userName} закончил выполнять задачу (время: {$timeStr})";
                break;

            case 'sent_to_review':
                $reviewer = User::findOne($data['reviewer_id']);
                if ($reviewer) {
                    $reviewerName = "{$reviewer->name} {$reviewer->surname}";
                    $message = "[ПРОВЕРКА] Задача отправлена на проверку сотруднику {$reviewerName}";
                }
                break;

            case 'task_created':
                $message = "[СОЗДАНО] Задача создана";
                break;

            case 'assignee_added':
                $assignee = User::findOne($data['assignee_id']);
                if ($assignee) {
                    $assigneeName = "{$assignee->name} {$assignee->surname}";
                    $message = "[НАЗНАЧЕНИЕ] {$assigneeName} назначен на задачу";
                }
                break;

            case 'assignee_removed':
                $assignee = User::findOne($data['assignee_id']);
                if ($assignee) {
                    $assigneeName = "{$assignee->name} {$assignee->surname}";
                    $message = "[СНЯТИЕ] {$assigneeName} снят с задачи";
                }
                break;

            case 'priority_changed':
                $priorityLabels = [
                    1 => 'Низкий',
                    2 => 'Средний',
                    3 => 'Высокий',
                    4 => 'Срочный',
                ];
                $oldPriority = $priorityLabels[$data['old_priority']] ?? 'Неизвестно';
                $newPriority = $priorityLabels[$data['new_priority']] ?? 'Неизвестно';
                $message = "[ПРИОРИТЕТ] Приоритет изменен с \"{$oldPriority}\" на \"{$newPriority}\"";
                break;

            case 'deadline_changed':
                if (!empty($data['new_deadline'])) {
                    $deadlineDate = date('d.m.Y', $data['new_deadline']);
                    $message = "[ДЕДЛАЙН] Дедлайн установлен: {$deadlineDate}";
                } else {
                    $message = "[ДЕДЛАЙН] Дедлайн удален";
                }
                break;

            default:
                return null;
        }

        if (empty($message)) {
            return null;
        }

        return self::createMessage($taskId, $userId, $message, true);
    }

    /**
     * Форматирование сообщения для API ответа
     * @return array
     */
    public function toArray(array $fields = [], array $expand = [], $recursive = true)
    {
        return [
            'id' => $this->id,
            'task_id' => $this->task_id,
            'user' => [
                'id' => $this->user->id,
                'username' => $this->user->username,
                'name' => $this->user->name,
                'surname' => $this->user->surname,
            ],
            'message' => $this->message,
            'is_system' => (bool)$this->is_system,
            'created_at' => $this->created_at,
            'formatted_date' => date('d.m.Y H:i', $this->created_at),
        ];
    }
}

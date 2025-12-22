<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Task model
 *
 * @property int $id
 * @property string $title
 * @property string $description
 * @property int $project_id
 * @property int $status
 * @property int $priority
 * @property int $start_date
 * @property int $deadline
 * @property int $estimated_time
 * @property int $created_at
 * @property int $updated_at
 * @property int $created_by
 */
class Task extends ActiveRecord
{
    // Статусы задачи
    const STATUS_TODO = 1;
    const STATUS_IN_PROGRESS = 2;
    const STATUS_REVIEW = 3;
    const STATUS_DONE = 4;

    // Приоритеты
    const PRIORITY_LOW = 1;
    const PRIORITY_MEDIUM = 2;
    const PRIORITY_HIGH = 3;
    const PRIORITY_URGENT = 4;

    public static function tableName()
    {
        return '{{%tasks}}';
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
            [['title', 'project_id', 'created_by'], 'required'],
            ['title', 'string', 'max' => 255],
            ['description', 'string'],
            [['project_id', 'status', 'priority', 'start_date', 'deadline', 'estimated_time', 'created_by'], 'integer'],
            ['project_id', 'exist', 'targetClass' => Project::class, 'targetAttribute' => 'id'],
            ['created_by', 'exist', 'targetClass' => User::class, 'targetAttribute' => 'id'],
            ['status', 'default', 'value' => self::STATUS_TODO],
            ['status', 'in', 'range' => [self::STATUS_TODO, self::STATUS_IN_PROGRESS, self::STATUS_REVIEW, self::STATUS_DONE]],
            ['priority', 'default', 'value' => self::PRIORITY_MEDIUM],
            ['priority', 'in', 'range' => [self::PRIORITY_LOW, self::PRIORITY_MEDIUM, self::PRIORITY_HIGH, self::PRIORITY_URGENT]],
            ['estimated_time', 'integer', 'min' => 0],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Название задачи',
            'description' => 'Описание',
            'project_id' => 'Проект',
            'status' => 'Статус',
            'priority' => 'Приоритет',
            'start_date' => 'Дата начала',
            'deadline' => 'Дедлайн',
            'estimated_time' => 'Планируемое время (сек)',
            'created_at' => 'Дата создания',
            'updated_at' => 'Обновлено',
            'created_by' => 'Автор',
        ];
    }

    // Связь с проектом
    public function getProject()
    {
        return $this->hasOne(Project::class, ['id' => 'project_id']);
    }

    // Связь с автором задачи
    public function getCreator()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    // Связь с участниками задачи через промежуточную таблицу
    public function getTaskAssignments()
    {
        return $this->hasMany(TaskAssignment::class, ['task_id' => 'id']);
    }

    // Связь напрямую с участниками
    public function getAssignees()
    {
        return $this->hasMany(User::class, ['id' => 'user_id'])
            ->via('taskAssignments');
    }

    // Связь с записями отслеживания времени
    public function getTimeTrackings()
    {
        return $this->hasMany(TimeTracking::class, ['task_id' => 'id']);
    }

    // Связь с TODO элементами
    public function getTodos()
    {
        return $this->hasMany(TaskTodo::class, ['task_id' => 'id'])
            ->orderBy(['position' => SORT_ASC]);
    }

    // Получить общее время выполнения задачи
    public function getTotalTime()
    {
        return TimeTracking::find()
            ->where(['task_id' => $this->id])
            ->sum('duration') ?? 0;
    }

    // Получить время выполнения задачи конкретным пользователем
    public function getUserTime($userId)
    {
        return TimeTracking::find()
            ->where(['task_id' => $this->id, 'user_id' => $userId])
            ->sum('duration') ?? 0;
    }

    // Получить название статуса
    public function getStatusLabel()
    {
        $statuses = [
            self::STATUS_TODO => 'К выполнению',
            self::STATUS_IN_PROGRESS => 'В работе',
            self::STATUS_REVIEW => 'На проверке',
            self::STATUS_DONE => 'Выполнено',
        ];

        return $statuses[$this->status] ?? 'Неизвестно';
    }

    // Получить название приоритета
    public function getPriorityLabel()
    {
        $priorities = [
            self::PRIORITY_LOW => 'Низкий',
            self::PRIORITY_MEDIUM => 'Средний',
            self::PRIORITY_HIGH => 'Высокий',
            self::PRIORITY_URGENT => 'Срочный',
        ];

        return $priorities[$this->priority] ?? 'Неизвестно';
    }

    // Проверка: назначена ли задача пользователю
    public function isAssignedTo($userId)
    {
        return TaskAssignment::find()
            ->where(['task_id' => $this->id, 'user_id' => $userId])
            ->exists();
    }

    // Назначить задачу пользователю
    public function assignToUser($userId)
    {
        // Проверяем что еще не назначена
        if ($this->isAssignedTo($userId)) {
            return true;
        }

        $assignment = new TaskAssignment();
        $assignment->task_id = $this->id;
        $assignment->user_id = $userId;
        $assignment->assigned_at = time();

        return $assignment->save();
    }

    // Снять назначение с пользователя
    public function unassignFromUser($userId)
    {
        return TaskAssignment::deleteAll([
            'task_id' => $this->id,
            'user_id' => $userId
        ]);
    }

    // TODO: Добавить чат для задачи (на веб-сокетах)
    // public function getChat() {}

    // Получить задачи доступные пользователю
    public static function getAccessibleTasks($user)
    {
        if (!$user) {
            return [];
        }

        // Возвращаем все задачи (управление правами через RBAC)
        return self::find()->with(['project', 'assignees', 'creator'])->all();
    }
}

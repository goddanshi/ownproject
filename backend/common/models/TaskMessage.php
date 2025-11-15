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
     * @return TaskMessage|null
     */
    public static function createMessage($taskId, $userId, $message)
    {
        $model = new static();
        $model->task_id = $taskId;
        $model->user_id = $userId;
        $model->message = $message;

        if ($model->save()) {
            // Загружаем связь с пользователем
            $model->refresh();
            return $model;
        }

        return null;
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
            'created_at' => $this->created_at,
            'formatted_date' => date('d.m.Y H:i', $this->created_at),
        ];
    }
}

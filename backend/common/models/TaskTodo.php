<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * TaskTodo model - TODO элементы внутри задачи
 *
 * @property int $id
 * @property int $task_id
 * @property string $title
 * @property int $deadline
 * @property bool $is_completed
 * @property int $position
 * @property int $created_at
 * @property int $updated_at
 */
class TaskTodo extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%task_todos}}';
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
            [['task_id', 'title'], 'required'],
            ['title', 'string', 'max' => 500],
            [['task_id', 'position', 'deadline'], 'integer'],
            ['is_completed', 'boolean'],
            ['is_completed', 'default', 'value' => false],
            ['position', 'default', 'value' => 0],
            ['task_id', 'exist', 'targetClass' => Task::class, 'targetAttribute' => 'id'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'task_id' => 'ID задачи',
            'title' => 'Название',
            'deadline' => 'Дедлайн',
            'is_completed' => 'Выполнено',
            'position' => 'Позиция',
            'created_at' => 'Создано',
            'updated_at' => 'Обновлено',
        ];
    }

    // Связь с задачей
    public function getTask()
    {
        return $this->hasOne(Task::class, ['id' => 'task_id']);
    }

    // Переключить статус выполнения
    public function toggleCompleted()
    {
        $this->is_completed = !$this->is_completed;
        return $this->save();
    }

    // Получить следующую позицию для новой TODO
    public static function getNextPosition($taskId)
    {
        $maxPosition = self::find()
            ->where(['task_id' => $taskId])
            ->max('position');

        return ($maxPosition !== null) ? $maxPosition + 1 : 0;
    }
}

<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * TaskAssignment model
 *
 * @property int $id
 * @property int $task_id
 * @property int $user_id
 * @property int $assigned_at
 */
class TaskAssignment extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%task_assignments}}';
    }

    public function rules()
    {
        return [
            [['task_id', 'user_id'], 'required'],
            [['task_id', 'user_id', 'assigned_at'], 'integer'],
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
            'assigned_at' => 'Дата назначения',
        ];
    }

    // Связь с задачей
    public function getTask()
    {
        return $this->hasOne(Task::class, ['id' => 'task_id']);
    }

    // Связь с пользователем
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($this->isNewRecord && !$this->assigned_at) {
                $this->assigned_at = time();
            }
            return true;
        }
        return false;
    }
}

<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * TimeTracking model
 *
 * @property int $id
 * @property int $task_id
 * @property int $user_id
 * @property int $started_at
 * @property int $ended_at
 * @property int $duration (в секундах)
 * @property string $description
 * @property int $created_at
 * @property int $updated_at
 */
class TimeTracking extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%time_trackings}}';
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
            [['task_id', 'user_id', 'started_at'], 'required'],
            [['task_id', 'user_id', 'started_at', 'ended_at', 'duration'], 'integer'],
            ['description', 'string'],
            ['task_id', 'exist', 'targetClass' => Task::class, 'targetAttribute' => 'id'],
            ['user_id', 'exist', 'targetClass' => User::class, 'targetAttribute' => 'id'],
            ['ended_at', 'validateEndTime'],
        ];
    }

    public function validateEndTime($attribute)
    {
        if ($this->ended_at && $this->started_at && $this->ended_at < $this->started_at) {
            $this->addError($attribute, 'Время окончания не может быть раньше времени начала');
        }
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'task_id' => 'Задача',
            'user_id' => 'Пользователь',
            'started_at' => 'Время начала',
            'ended_at' => 'Время окончания',
            'duration' => 'Длительность',
            'description' => 'Описание',
            'created_at' => 'Создано',
            'updated_at' => 'Обновлено',
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

    // Начать отслеживание времени
    public static function startTracking($taskId, $userId, $description = null)
    {
        // Проверяем, нет ли уже активной записи
        $activeTracking = self::find()
            ->where(['task_id' => $taskId, 'user_id' => $userId])
            ->andWhere(['ended_at' => null])
            ->one();

        if ($activeTracking) {
            return $activeTracking; // Уже идет отслеживание
        }

        $tracking = new self();
        $tracking->task_id = $taskId;
        $tracking->user_id = $userId;
        $tracking->started_at = time();
        $tracking->description = $description;

        if ($tracking->save()) {
            return $tracking;
        }

        return null;
    }

    // Остановить отслеживание времени
    public function stopTracking()
    {
        if ($this->ended_at) {
            return false; // Уже остановлено
        }

        $this->ended_at = time();
        $this->duration = $this->ended_at - $this->started_at;

        return $this->save();
    }

    // Получить активное отслеживание для пользователя и задачи
    public static function getActiveTracking($taskId, $userId)
    {
        return self::find()
            ->where(['task_id' => $taskId, 'user_id' => $userId])
            ->andWhere(['ended_at' => null])
            ->one();
    }

    // Получить все записи времени для задачи
    public static function getTaskTrackings($taskId)
    {
        return self::find()
            ->where(['task_id' => $taskId])
            ->orderBy(['started_at' => SORT_DESC])
            ->all();
    }

    // Получить все записи времени для пользователя
    public static function getUserTrackings($userId)
    {
        return self::find()
            ->where(['user_id' => $userId])
            ->orderBy(['started_at' => SORT_DESC])
            ->all();
    }

    // Получить форматированную длительность
    public function getFormattedDuration()
    {
        if (!$this->duration) {
            return '0ч 0м';
        }

        $hours = floor($this->duration / 3600);
        $minutes = floor(($this->duration % 3600) / 60);

        return "{$hours}ч {$minutes}м";
    }

    // Автоматический расчет длительности перед сохранением
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($this->ended_at && $this->started_at && !$this->duration) {
                $this->duration = $this->ended_at - $this->started_at;
            }
            return true;
        }
        return false;
    }
}

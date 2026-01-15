<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Project model
 *
 * @property int $id
 * @property string $name
 * @property string $description
 * @property int $start_date
 * @property int $end_date
 * @property string $website_url
 * @property string $logo_url
 * @property string $documents_url
 * @property int $team_id
 * @property int $folder_id
 * @property int $position
 * @property int $created_at
 * @property int $updated_at
 */
class Project extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%projects}}';
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
            [['name', 'team_id'], 'required'],
            ['name', 'string', 'max' => 255],
            ['description', 'string'],
            [['website_url', 'logo_url', 'documents_url'], 'string', 'max' => 500],
            [['website_url', 'logo_url', 'documents_url'], 'url'],
            [['team_id', 'folder_id', 'start_date', 'end_date', 'position'], 'integer'],
            ['position', 'default', 'value' => 0],
            ['team_id', 'exist', 'targetClass' => Team::class, 'targetAttribute' => 'id'],
            ['folder_id', 'exist', 'targetClass' => Folder::class, 'targetAttribute' => 'id'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Название проекта',
            'description' => 'Описание',
            'start_date' => 'Дата начала',
            'end_date' => 'Дата завершения',
            'website_url' => 'Сайт',
            'logo_url' => 'Логотип',
            'documents_url' => 'Документы',
            'team_id' => 'Команда',
            'folder_id' => 'Папка',
            'position' => 'Позиция',
            'created_at' => 'Создано',
            'updated_at' => 'Обновлено',
        ];
    }

    // Связь с командой
    public function getTeam()
    {
        return $this->hasOne(Team::class, ['id' => 'team_id']);
    }

    // Связь с задачами
    public function getTasks()
    {
        return $this->hasMany(Task::class, ['project_id' => 'id']);
    }

    // Связь с папкой
    public function getFolder()
    {
        return $this->hasOne(Folder::class, ['id' => 'folder_id']);
    }

    // Получить всех участников проекта (через команду)
    public function getParticipants()
    {
        return $this->hasMany(User::class, ['id' => 'user_id'])
            ->via('team.teamMembers');
    }

    // Получить всех участников включая тимлида
    public function getAllParticipants()
    {
        if (!$this->team) {
            return [];
        }

        return $this->team->getAllMembers();
    }

    // Проверка: является ли пользователь участником проекта
    public function isParticipant($userId)
    {
        if (!$this->team) {
            return false;
        }

        // Тимлид команды тоже участник
        if ($this->team->isTeamlead($userId)) {
            return true;
        }

        return $this->team->isMember($userId);
    }

    // Проверка: является ли пользователь тимлидом проекта
    public function isTeamlead($userId)
    {
        if (!$this->team) {
            return false;
        }

        return $this->team->isTeamlead($userId);
    }

    // Получить проекты доступные пользователю
    public static function getAccessibleProjects($user)
    {
        if (!$user) {
            return [];
        }

        // Все авторизованные пользователи видят все проекты
        return self::find()->with(['team', 'tasks'])->all();
    }
}

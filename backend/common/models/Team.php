<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Team model
 *
 * @property int $id
 * @property string $name
 * @property string $description
 * @property int $teamlead_id
 * @property int $created_at
 * @property int $updated_at
 */
class Team extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%teams}}';
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
            [['name', 'teamlead_id'], 'required'],
            ['name', 'string', 'max' => 255],
            ['description', 'string'],
            ['teamlead_id', 'integer'],
            ['teamlead_id', 'exist', 'targetClass' => User::class, 'targetAttribute' => 'id'],
            // Проверка что teamlead_id это действительно тимлид
            ['teamlead_id', 'validateTeamlead'],
        ];
    }

    public function validateTeamlead($attribute)
    {
        $user = User::findOne($this->$attribute);
        if ($user && $user->role != User::ROLE_TEAMLEAD) {
            $this->addError($attribute, 'Выбранный пользователь не является тимлидом');
        }
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Название команды',
            'description' => 'Описание',
            'teamlead_id' => 'Тимлид',
            'created_at' => 'Создано',
            'updated_at' => 'Обновлено',
        ];
    }

    // Связь с тимлидом
    public function getTeamlead()
    {
        return $this->hasOne(User::class, ['id' => 'teamlead_id']);
    }

    // Связь с участниками через промежуточную таблицу
    public function getTeamMembers()
    {
        return $this->hasMany(TeamMember::class, ['team_id' => 'id']);
    }

    // Связь напрямую с пользователями-сотрудниками
    public function getMembers()
    {
        return $this->hasMany(User::class, ['id' => 'user_id'])
            ->via('teamMembers');
    }

    // Связь с проектами
    public function getProjects()
    {
        return $this->hasMany(Project::class, ['team_id' => 'id']);
    }

    // Получить всех участников включая тимлида
    public function getAllMembers()
    {
        $members = $this->members;
        $teamlead = $this->teamlead;

        if ($teamlead) {
            $allMembers = array_merge([$teamlead], $members);
            return $allMembers;
        }

        return $members;
    }

    // Проверка: является ли пользователь тимлидом этой команды
    public function isTeamlead($userId)
    {
        return $this->teamlead_id == $userId;
    }

    // Проверка: является ли пользователь участником команды
    public function isMember($userId)
    {
        return TeamMember::find()
            ->where(['team_id' => $this->id, 'user_id' => $userId])
            ->exists();
    }

    // Добавить участника в команду
    public function addMember($userId)
    {
        // Проверяем что пользователь существует и является сотрудником
        $user = User::findOne($userId);
        if (!$user || $user->role != User::ROLE_EMPLOYER) {
            return false;
        }

        // Проверяем что еще не добавлен
        if ($this->isMember($userId)) {
            return true; // Уже в команде
        }

        $member = new TeamMember();
        $member->team_id = $this->id;
        $member->user_id = $userId;
        $member->joined_at = time();

        return $member->save();
    }

    // Удалить участника из команды
    public function removeMember($userId)
    {
        return TeamMember::deleteAll([
            'team_id' => $this->id,
            'user_id' => $userId
        ]);
    }

    // Получить команды доступные пользователю
    public static function getAccessibleTeams($user)
    {
        if (!$user) {
            return [];
        }

        // Админ видит все команды
        if ($user->role == User::ROLE_ADMIN) {
            return self::find()->with(['teamlead', 'members'])->all();
        }

        // Тимлид видит только свои команды
        if ($user->role == User::ROLE_TEAMLEAD) {
            return self::find()
                ->where(['teamlead_id' => $user->id])
                ->with(['teamlead', 'members'])
                ->all();
        }

        // Сотрудник видит команды, где он участник
        if ($user->role == User::ROLE_EMPLOYER) {
            return self::find()
                ->joinWith('teamMembers')
                ->where(['team_members.user_id' => $user->id])
                ->with(['teamlead', 'members'])
                ->all();
        }

        return [];
    }
}
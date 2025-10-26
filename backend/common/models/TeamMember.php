<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * TeamMember model
 *
 * @property int $id
 * @property int $team_id
 * @property int $user_id
 * @property int $joined_at
 */
class TeamMember extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%team_members}}';
    }

    public function rules()
    {
        return [
            [['team_id', 'user_id'], 'required'],
            [['team_id', 'user_id', 'joined_at'], 'integer'],
            ['team_id', 'exist', 'targetClass' => Team::class, 'targetAttribute' => 'id'],
            ['user_id', 'exist', 'targetClass' => User::class, 'targetAttribute' => 'id'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'team_id' => 'Команда',
            'user_id' => 'Пользователь',
            'joined_at' => 'Дата добавления',
        ];
    }

    // Связь с командой
    public function getTeam()
    {
        return $this->hasOne(Team::class, ['id' => 'team_id']);
    }

    // Связь с пользователем
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($this->isNewRecord && !$this->joined_at) {
                $this->joined_at = time();
            }
            return true;
        }
        return false;
    }
}
<?php

namespace app\models;

use yii\db\ActiveRecord;

class RolePermission extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%role_permissions}}';
    }

    public function rules()
    {
        return [
            [['role', 'permission_name'], 'required'],
            [['role'], 'integer'],
            [['permission_name'], 'string', 'max' => 100],
            [['created_at'], 'integer'],
        ];
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                $this->created_at = time();
            }
            return true;
        }
        return false;
    }

    public function getPermission()
    {
        return $this->hasOne(Permission::class, ['name' => 'permission_name']);
    }
}
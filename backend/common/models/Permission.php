<?php

namespace common\models;

use yii\db\ActiveRecord;

class Permission extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%permissions}}';
    }

    public function rules()
    {
        return [
            [['name', 'label'], 'required'],
            [['description'], 'string'],
            [['name'], 'string', 'max' => 100],
            [['label'], 'string', 'max' => 255],
            [['category'], 'string', 'max' => 50],
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
}
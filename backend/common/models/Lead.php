<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

class Lead extends ActiveRecord
{
    const STATUS_NEW = 1;
    const STATUS_WAITING = 2;
    const STATUS_WORKING = 3;
    const STATUS_LOST = 4;
    const STATUS_COLD = 5;

    const CONTACT_PHONE = 'phone';
    const CONTACT_WHATSAPP = 'whatsapp';
    const CONTACT_VK = 'vk';
    const CONTACT_TELEGRAM = 'telegram';

    const READY = 'ready';
    const NOT_READY = 'not_ready';

    public static function tableName()
    {
        return '{{%leads}}';
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
            [['date', 'contact_type', 'contact_value', 'created_by'], 'required'],
            [['date', 'contact_date', 'status', 'created_by'], 'integer'],
            [['price'], 'number'],
            [['audit_info', 'proposal_info', 'comment'], 'string'],
            [['website', 'contact_value'], 'string', 'max' => 255],
            [['contact_type'], 'string', 'max' => 50],
            [['audit_status', 'proposal_status'], 'string', 'max' => 20],
            ['status', 'default', 'value' => self::STATUS_NEW],
            ['status', 'in', 'range' => [self::STATUS_NEW, self::STATUS_WAITING, self::STATUS_WORKING, self::STATUS_LOST, self::STATUS_COLD]],
            ['contact_type', 'in', 'range' => [self::CONTACT_PHONE, self::CONTACT_WHATSAPP, self::CONTACT_VK, self::CONTACT_TELEGRAM]],
            ['audit_status', 'default', 'value' => self::NOT_READY],
            ['audit_status', 'in', 'range' => [self::READY, self::NOT_READY]],
            ['proposal_status', 'default', 'value' => self::NOT_READY],
            ['proposal_status', 'in', 'range' => [self::READY, self::NOT_READY]],
            ['created_by', 'exist', 'targetClass' => User::class, 'targetAttribute' => 'id'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'date' => 'Дата заявки',
            'website' => 'Сайт',
            'contact_type' => 'Тип связи',
            'contact_value' => 'Контакт',
            'audit_info' => 'Информация по аудиту',
            'audit_status' => 'Статус аудита',
            'proposal_info' => 'Информация по КП',
            'proposal_status' => 'Статус КП',
            'price' => 'Цена',
            'status' => 'Статус',
            'contact_date' => 'Дата связи',
            'comment' => 'Комментарий',
            'created_by' => 'Создал',
            'created_at' => 'Дата создания',
            'updated_at' => 'Обновлено',
        ];
    }

    public function getCreator()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    public function getStatusLabel()
    {
        $statuses = [
            self::STATUS_NEW => 'Новый',
            self::STATUS_WAITING => 'Ждем ответа',
            self::STATUS_WORKING => 'Работаем',
            self::STATUS_LOST => 'Слился',
            self::STATUS_COLD => 'Холодные',
        ];
        return $statuses[$this->status] ?? 'Неизвестно';
    }

    public function getContactTypeLabel()
    {
        $types = [
            self::CONTACT_PHONE => 'Телефон',
            self::CONTACT_WHATSAPP => 'WhatsApp',
            self::CONTACT_VK => 'ВКонтакте',
            self::CONTACT_TELEGRAM => 'Telegram',
        ];
        return $types[$this->contact_type] ?? 'Неизвестно';
    }

    public function getAuditStatusLabel()
    {
        return $this->audit_status === self::READY ? 'Готов' : 'Не готов';
    }

    public function getProposalStatusLabel()
    {
        return $this->proposal_status === self::READY ? 'Готов' : 'Не готов';
    }

    public function isContactDateExpired()
    {
        if (!$this->contact_date) {
            return false;
        }
        return $this->contact_date < time();
    }

    public function isContactDateToday()
    {
        if (!$this->contact_date) {
            return false;
        }
        $today = strtotime('today');
        $tomorrow = strtotime('tomorrow');
        return $this->contact_date >= $today && $this->contact_date < $tomorrow;
    }
}

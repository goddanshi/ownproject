<?php

namespace console\migrations;

use yii\db\Migration;

/**
 * Миграция для создания таблицы лидов (заявок)
 */
class m241127_000000_create_leads_table extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%leads}}', [
            'id' => $this->primaryKey(),
            'date' => $this->integer()->notNull()->comment('Дата заявки (timestamp)'),
            'website' => $this->string(255)->comment('Сайт клиента'),
            'contact_type' => $this->string(50)->notNull()->comment('Тип связи: phone, whatsapp, vk, telegram'),
            'contact_value' => $this->string(255)->notNull()->comment('Контакт для связи'),

            // Аудит
            'audit_info' => $this->text()->comment('Информация по аудиту'),
            'audit_status' => $this->string(20)->notNull()->defaultValue('not_ready')->comment('Статус аудита: ready, not_ready'),

            // Коммерческое предложение
            'proposal_info' => $this->text()->comment('Информация по КП'),
            'proposal_status' => $this->string(20)->notNull()->defaultValue('not_ready')->comment('Статус КП: ready, not_ready'),

            'price' => $this->decimal(10, 2)->comment('Цена'),
            'status' => $this->integer()->notNull()->defaultValue(1)->comment('Статус: 1-Новый, 2-Ждем ответа, 3-Работаем, 4-Слился'),
            'contact_date' => $this->integer()->comment('Дата связи (timestamp)'),
            'comment' => $this->text()->comment('Комментарий'),

            'created_by' => $this->integer()->notNull()->comment('Кто создал'),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        // Индексы
        $this->createIndex('idx-leads-status', '{{%leads}}', 'status');
        $this->createIndex('idx-leads-created_by', '{{%leads}}', 'created_by');
        $this->createIndex('idx-leads-contact_date', '{{%leads}}', 'contact_date');
        $this->createIndex('idx-leads-date', '{{%leads}}', 'date');

        // Внешний ключ на пользователя
        $this->addForeignKey(
            'fk-leads-created_by',
            '{{%leads}}',
            'created_by',
            '{{%user}}',
            'id',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-leads-created_by', '{{%leads}}');
        $this->dropTable('{{%leads}}');
    }
}

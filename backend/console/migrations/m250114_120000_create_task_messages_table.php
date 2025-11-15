<?php
namespace console\migrations;

use yii\db\Migration;

/**
 * Создание таблицы для сообщений чата задач
 */
class m250114_120000_create_task_messages_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%task_messages}}', [
            'id' => $this->primaryKey(),
            'task_id' => $this->integer()->notNull()->comment('ID задачи'),
            'user_id' => $this->integer()->notNull()->comment('ID пользователя'),
            'message' => $this->text()->notNull()->comment('Текст сообщения'),
            'created_at' => $this->integer()->notNull()->comment('Дата создания'),
            'updated_at' => $this->integer()->notNull()->comment('Дата обновления'),
        ]);

        // Добавляем индексы
        $this->createIndex(
            'idx-task_messages-task_id',
            '{{%task_messages}}',
            'task_id'
        );

        $this->createIndex(
            'idx-task_messages-user_id',
            '{{%task_messages}}',
            'user_id'
        );

        $this->createIndex(
            'idx-task_messages-created_at',
            '{{%task_messages}}',
            'created_at'
        );

        // Добавляем внешние ключи
        $this->addForeignKey(
            'fk-task_messages-task_id',
            '{{%task_messages}}',
            'task_id',
            '{{%tasks}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-task_messages-user_id',
            '{{%task_messages}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        // Удаляем внешние ключи
        $this->dropForeignKey('fk-task_messages-task_id', '{{%task_messages}}');
        $this->dropForeignKey('fk-task_messages-user_id', '{{%task_messages}}');

        // Удаляем индексы
        $this->dropIndex('idx-task_messages-task_id', '{{%task_messages}}');
        $this->dropIndex('idx-task_messages-user_id', '{{%task_messages}}');
        $this->dropIndex('idx-task_messages-created_at', '{{%task_messages}}');

        // Удаляем таблицу
        $this->dropTable('{{%task_messages}}');
    }
}

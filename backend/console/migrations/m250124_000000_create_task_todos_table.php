<?php
namespace console\migrations;

use yii\db\Migration;

class m250124_000000_create_task_todos_table extends Migration
{
    public function safeUp()
    {
        // Таблица TODO задач внутри задачи
        $this->createTable('{{%task_todos}}', [
            'id' => $this->primaryKey(),
            'task_id' => $this->integer()->notNull()->comment('ID задачи'),
            'title' => $this->string(500)->notNull()->comment('Название TODO'),
            'is_completed' => $this->boolean()->notNull()->defaultValue(0)->comment('Выполнено ли'),
            'position' => $this->integer()->notNull()->defaultValue(0)->comment('Позиция для сортировки'),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        // Индекс для task_id
        $this->createIndex(
            'idx-task_todos-task_id',
            '{{%task_todos}}',
            'task_id'
        );

        // Внешний ключ на таблицу tasks
        $this->addForeignKey(
            'fk-task_todos-task_id',
            '{{%task_todos}}',
            'task_id',
            '{{%tasks}}',
            'id',
            'CASCADE'
        );

        // Индекс для сортировки
        $this->createIndex(
            'idx-task_todos-position',
            '{{%task_todos}}',
            ['task_id', 'position']
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-task_todos-task_id', '{{%task_todos}}');
        $this->dropIndex('idx-task_todos-task_id', '{{%task_todos}}');
        $this->dropIndex('idx-task_todos-position', '{{%task_todos}}');
        $this->dropTable('{{%task_todos}}');
    }
}

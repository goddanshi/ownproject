<?php
namespace console\migrations;

use yii\db\Migration;

class m250131_000000_create_projects_and_tasks_tables extends Migration
{
    public function safeUp()
    {
        // Таблица проектов
        $this->createTable('{{%projects}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull(),
            'description' => $this->text(),
            'team_id' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        // Индексы и внешние ключи для projects
        $this->createIndex(
            'idx-projects-team_id',
            '{{%projects}}',
            'team_id'
        );

        $this->addForeignKey(
            'fk-projects-team_id',
            '{{%projects}}',
            'team_id',
            '{{%teams}}',
            'id',
            'CASCADE'
        );

        // Таблица задач
        $this->createTable('{{%tasks}}', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull(),
            'description' => $this->text(),
            'project_id' => $this->integer()->notNull(),
            'status' => $this->integer()->notNull()->defaultValue(1),
            'priority' => $this->integer()->notNull()->defaultValue(2),
            'deadline' => $this->integer(),
            'created_by' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        // Индексы и внешние ключи для tasks
        $this->createIndex(
            'idx-tasks-project_id',
            '{{%tasks}}',
            'project_id'
        );

        $this->createIndex(
            'idx-tasks-created_by',
            '{{%tasks}}',
            'created_by'
        );

        $this->createIndex(
            'idx-tasks-status',
            '{{%tasks}}',
            'status'
        );

        $this->addForeignKey(
            'fk-tasks-project_id',
            '{{%tasks}}',
            'project_id',
            '{{%projects}}',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-tasks-created_by',
            '{{%tasks}}',
            'created_by',
            '{{%user}}',
            'id',
            'CASCADE'
        );

        // Таблица назначений задач (many-to-many между задачами и пользователями)
        $this->createTable('{{%task_assignments}}', [
            'id' => $this->primaryKey(),
            'task_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'assigned_at' => $this->integer()->notNull(),
        ]);

        // Индексы и внешние ключи для task_assignments
        $this->createIndex(
            'idx-task_assignments-task_id',
            '{{%task_assignments}}',
            'task_id'
        );

        $this->createIndex(
            'idx-task_assignments-user_id',
            '{{%task_assignments}}',
            'user_id'
        );

        $this->addForeignKey(
            'fk-task_assignments-task_id',
            '{{%task_assignments}}',
            'task_id',
            '{{%tasks}}',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-task_assignments-user_id',
            '{{%task_assignments}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE'
        );

        // Уникальный индекс: один пользователь не может быть назначен на одну задачу дважды
        $this->createIndex(
            'idx-task_assignments-unique',
            '{{%task_assignments}}',
            ['task_id', 'user_id'],
            true
        );

        // Таблица отслеживания времени
        $this->createTable('{{%time_trackings}}', [
            'id' => $this->primaryKey(),
            'task_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'started_at' => $this->integer()->notNull(),
            'ended_at' => $this->integer(),
            'duration' => $this->integer(),
            'description' => $this->text(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        // Индексы и внешние ключи для time_trackings
        $this->createIndex(
            'idx-time_trackings-task_id',
            '{{%time_trackings}}',
            'task_id'
        );

        $this->createIndex(
            'idx-time_trackings-user_id',
            '{{%time_trackings}}',
            'user_id'
        );

        $this->addForeignKey(
            'fk-time_trackings-task_id',
            '{{%time_trackings}}',
            'task_id',
            '{{%tasks}}',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-time_trackings-user_id',
            '{{%time_trackings}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        // Удаляем в обратном порядке
        $this->dropForeignKey('fk-time_trackings-user_id', '{{%time_trackings}}');
        $this->dropForeignKey('fk-time_trackings-task_id', '{{%time_trackings}}');
        $this->dropTable('{{%time_trackings}}');

        $this->dropForeignKey('fk-task_assignments-user_id', '{{%task_assignments}}');
        $this->dropForeignKey('fk-task_assignments-task_id', '{{%task_assignments}}');
        $this->dropTable('{{%task_assignments}}');

        $this->dropForeignKey('fk-tasks-created_by', '{{%tasks}}');
        $this->dropForeignKey('fk-tasks-project_id', '{{%tasks}}');
        $this->dropTable('{{%tasks}}');

        $this->dropForeignKey('fk-projects-team_id', '{{%projects}}');
        $this->dropTable('{{%projects}}');
    }
}

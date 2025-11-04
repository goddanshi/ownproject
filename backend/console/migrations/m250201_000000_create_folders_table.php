<?php
namespace console\migrations;

use yii\db\Migration;

/**
 * Миграция для создания таблицы папок с поддержкой вложенности
 *
 * Структура: Папки -> Подпапки -> Проекты -> Задачи
 */
class m250201_000000_create_folders_table extends Migration
{
    public function safeUp()
    {
        // Таблица папок
        $this->createTable('{{%folders}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull(),
            'description' => $this->text(),
            'parent_id' => $this->integer()->null(), // Родительская папка (null = корневая папка)
            'team_id' => $this->integer()->Null(), 
            'created_by' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        // Индексы для folders
        $this->createIndex(
            'idx-folders-parent_id',
            '{{%folders}}',
            'parent_id'
        );

        $this->createIndex(
            'idx-folders-team_id',
            '{{%folders}}',
            'team_id'
        );

        $this->createIndex(
            'idx-folders-created_by',
            '{{%folders}}',
            'created_by'
        );

        // Внешние ключи для folders
        $this->addForeignKey(
            'fk-folders-parent_id',
            '{{%folders}}',
            'parent_id',
            '{{%folders}}',
            'id',
            'CASCADE' // При удалении родительской папки удаляются и дочерние
        );

        $this->addForeignKey(
            'fk-folders-team_id',
            '{{%folders}}',
            'team_id',
            '{{%teams}}',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-folders-created_by',
            '{{%folders}}',
            'created_by',
            '{{%user}}',
            'id',
            'CASCADE'
        );

        // Добавляем поле folder_id в таблицу projects
        $this->addColumn('{{%projects}}', 'folder_id', $this->integer()->null());

        $this->createIndex(
            'idx-projects-folder_id',
            '{{%projects}}',
            'folder_id'
        );

        $this->addForeignKey(
            'fk-projects-folder_id',
            '{{%projects}}',
            'folder_id',
            '{{%folders}}',
            'id',
            'SET NULL' // При удалении папки проекты становятся корневыми
        );
    }

    public function safeDown()
    {
        // Удаляем изменения в обратном порядке
        $this->dropForeignKey('fk-projects-folder_id', '{{%projects}}');
        $this->dropIndex('idx-projects-folder_id', '{{%projects}}');
        $this->dropColumn('{{%projects}}', 'folder_id');

        $this->dropForeignKey('fk-folders-created_by', '{{%folders}}');
        $this->dropForeignKey('fk-folders-team_id', '{{%folders}}');
        $this->dropForeignKey('fk-folders-parent_id', '{{%folders}}');

        $this->dropTable('{{%folders}}');
    }
}

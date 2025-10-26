<?php
namespace console\migrations;

use yii\db\Migration;

class m241021_000000_create_teams_tables extends Migration
{
    public function safeUp()
    {
        // Таблица команд
        $this->createTable('{{%teams}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull(),
            'description' => $this->text(),
            'teamlead_id' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        // Таблица участников команд (many-to-many)
        $this->createTable('{{%team_members}}', [
            'id' => $this->primaryKey(),
            'team_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'joined_at' => $this->integer()->notNull(),
        ]);

        // Индексы и внешние ключи для teams
        $this->createIndex(
            'idx-teams-teamlead_id',
            '{{%teams}}',
            'teamlead_id'
        );

        $this->addForeignKey(
            'fk-teams-teamlead_id',
            '{{%teams}}',
            'teamlead_id',
            '{{%user}}',
            'id',
            'CASCADE'
        );

        // Индексы и внешние ключи для team_members
        $this->createIndex(
            'idx-team_members-team_id',
            '{{%team_members}}',
            'team_id'
        );

        $this->createIndex(
            'idx-team_members-user_id',
            '{{%team_members}}',
            'user_id'
        );

        $this->addForeignKey(
            'fk-team_members-team_id',
            '{{%team_members}}',
            'team_id',
            '{{%teams}}',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-team_members-user_id',
            '{{%team_members}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE'
        );

        // Уникальный индекс: один пользователь не может быть в одной команде дважды
        $this->createIndex(
            'idx-team_members-unique',
            '{{%team_members}}',
            ['team_id', 'user_id'],
            true
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-team_members-user_id', '{{%team_members}}');
        $this->dropForeignKey('fk-team_members-team_id', '{{%team_members}}');
        $this->dropForeignKey('fk-teams-teamlead_id', '{{%teams}}');

        $this->dropTable('{{%team_members}}');
        $this->dropTable('{{%teams}}');
    }
}
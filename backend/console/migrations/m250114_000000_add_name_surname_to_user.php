<?php
namespace console\migrations;
use yii\db\Migration;

class m250114_000000_add_name_surname_to_user extends Migration
{
    public function safeUp()
    {
        $tableSchema = $this->db->getTableSchema('{{%user}}');

        // Добавляем name если его нет
        if (!isset($tableSchema->columns['name'])) {
            $this->addColumn('{{%user}}', 'name', $this->string()->after('username'));
        }

        // Добавляем surname если его нет
        if (!isset($tableSchema->columns['surname'])) {
            $this->addColumn('{{%user}}', 'surname', $this->string()->after('name'));
        }

        // Добавляем role если его нет
        if (!isset($tableSchema->columns['role'])) {
            $this->addColumn('{{%user}}', 'role', $this->integer()->defaultValue(3)->after('status'));
        }
    }

    public function safeDown()
    {
        $tableSchema = $this->db->getTableSchema('{{%user}}');

        if (isset($tableSchema->columns['role'])) {
            $this->dropColumn('{{%user}}', 'role');
        }

        if (isset($tableSchema->columns['surname'])) {
            $this->dropColumn('{{%user}}', 'surname');
        }

        if (isset($tableSchema->columns['name'])) {
            $this->dropColumn('{{%user}}', 'name');
        }
    }
}
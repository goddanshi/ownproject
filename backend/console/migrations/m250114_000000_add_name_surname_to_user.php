<?php

use yii\db\Migration;


class m250114_000000_add_name_surname_to_user extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%user}}', 'name', $this->string()->after('username'));
        $this->addColumn('{{%user}}', 'surname', $this->string()->after('name'));
        $this->addColumn('{{%user}}', 'role', $this->string()->after('role'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%user}}', 'name');
        $this->dropColumn('{{%user}}', 'surname');
    }
}
<?php

namespace console\migrations;

use yii\db\Migration;

/**
 * Adds deadline column to task_todos table
 */
class m250124_000001_add_deadline_to_task_todos extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%task_todos}}', 'deadline', $this->integer()->null()->after('title'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%task_todos}}', 'deadline');
    }
}

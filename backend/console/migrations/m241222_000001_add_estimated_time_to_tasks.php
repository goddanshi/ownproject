<?php

namespace console\migrations;

use yii\db\Migration;

/**
 * Добавляет поле estimated_time в таблицу tasks
 * для хранения планируемого времени выполнения задачи
 */
class m241222_000001_add_estimated_time_to_tasks extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%tasks}}', 'estimated_time', $this->integer()->after('deadline')->comment('Планируемое время выполнения в секундах'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%tasks}}', 'estimated_time');
    }
}

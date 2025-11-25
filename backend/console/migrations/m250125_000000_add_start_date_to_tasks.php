<?php
namespace console\migrations;

use yii\db\Migration;

class m250125_000000_add_start_date_to_tasks extends Migration
{
    public function safeUp()
    {
        // Добавляем поле start_date в таблицу tasks
        $this->addColumn('{{%tasks}}', 'start_date', $this->integer()->null()->comment('Дата начала задачи (timestamp)')->after('deadline'));

        // Создаем индекс для start_date
        $this->createIndex(
            'idx-tasks-start_date',
            '{{%tasks}}',
            'start_date'
        );
    }

    public function safeDown()
    {
        // Удаляем индекс
        $this->dropIndex('idx-tasks-start_date', '{{%tasks}}');

        // Удаляем поле
        $this->dropColumn('{{%tasks}}', 'start_date');
    }
}

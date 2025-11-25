<?php
namespace console\migrations;

use yii\db\Migration;

class m250125_000001_add_is_system_to_task_messages extends Migration
{
    public function safeUp()
    {
        // Добавляем поле is_system в таблицу task_messages
        $this->addColumn('{{%task_messages}}', 'is_system', $this->boolean()->defaultValue(false)->notNull()->comment('Системное сообщение')->after('message'));

        // Создаем индекс для is_system
        $this->createIndex(
            'idx-task_messages-is_system',
            '{{%task_messages}}',
            'is_system'
        );
    }

    public function safeDown()
    {
        // Удаляем индекс
        $this->dropIndex('idx-task_messages-is_system', '{{%task_messages}}');

        // Удаляем поле
        $this->dropColumn('{{%task_messages}}', 'is_system');
    }
}

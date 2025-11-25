<?php
namespace console\migrations;

use yii\db\Migration;

class m250125_000002_change_task_messages_charset extends Migration
{
    public function safeUp()
    {
        // Изменяем кодировку таблицы и поля message на utf8mb4 для поддержки emoji
        $this->execute("ALTER TABLE {{%task_messages}} CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $this->execute("ALTER TABLE {{%task_messages}} MODIFY COLUMN `message` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL");
    }

    public function safeDown()
    {
        // Возвращаем обратно на utf8 (опционально, может привести к потере emoji)
        $this->execute("ALTER TABLE {{%task_messages}} CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci");
        $this->execute("ALTER TABLE {{%task_messages}} MODIFY COLUMN `message` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL");
    }
}

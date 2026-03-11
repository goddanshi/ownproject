<?php

namespace console\migrations;

use yii\db\Migration;

/**
 * Adds position column to projects table for drag-and-drop ordering
 */
class m250201_000001_add_position_to_projects extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%projects}}', 'position', $this->integer()->notNull()->defaultValue(0)->after('folder_id'));

        // Устанавливаем позиции для существующих проектов
        $this->execute("
            SET @pos = 0;
            UPDATE {{%projects}}
            SET position = (@pos := @pos + 1)
            ORDER BY id;
        ");
    }

    public function safeDown()
    {
        $this->dropColumn('{{%projects}}', 'position');
    }
}

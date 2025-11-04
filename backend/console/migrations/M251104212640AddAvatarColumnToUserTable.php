<?php

namespace console\migrations;

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%user}}`.
 */
class M251104212640AddAvatarColumnToUserTable extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
         $tableSchema = $this->db->getTableSchema('{{%user}}');

         $this->addColumn('{{%user}}', 'avatar', $this->string()->after('role'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $tableSchema = $this->db->getTableSchema('{{%user}}');

        $this->dropColumn('{{%user}}', 'avatar');
    }
}

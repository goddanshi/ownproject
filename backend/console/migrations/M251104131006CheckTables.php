<?php

namespace console\migrations;

use yii\db\Migration;

class M251104131006CheckTables extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "M251104131006CheckTables cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "M251104131006CheckTables cannot be reverted.\n";

        return false;
    }
    */
}

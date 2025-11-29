<?php
namespace console\migrations;

use yii\db\Migration;

class m241128_000003_add_end_date_to_projects extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%projects}}', 'end_date', $this->integer()->after('start_date'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%projects}}', 'end_date');
    }
}

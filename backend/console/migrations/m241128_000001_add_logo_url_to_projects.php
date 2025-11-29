<?php
namespace console\migrations;

use yii\db\Migration;

class m241128_000001_add_logo_url_to_projects extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%projects}}', 'logo_url', $this->string(500)->after('website_url'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%projects}}', 'logo_url');
    }
}

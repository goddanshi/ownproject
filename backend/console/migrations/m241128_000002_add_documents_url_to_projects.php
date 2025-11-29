<?php
namespace console\migrations;

use yii\db\Migration;

class m241128_000002_add_documents_url_to_projects extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%projects}}', 'documents_url', $this->string(500)->after('logo_url'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%projects}}', 'documents_url');
    }
}

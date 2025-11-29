<?php
namespace console\migrations;

use yii\db\Migration;

class m241128_000000_add_start_date_and_website_to_projects extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%projects}}', 'start_date', $this->integer()->after('description'));
        $this->addColumn('{{%projects}}', 'website_url', $this->string(500)->after('start_date'));

        // Индекс для start_date для быстрой сортировки и фильтрации
        $this->createIndex(
            'idx-projects-start_date',
            '{{%projects}}',
            'start_date'
        );
    }

    public function safeDown()
    {
        $this->dropIndex('idx-projects-start_date', '{{%projects}}');
        $this->dropColumn('{{%projects}}', 'website_url');
        $this->dropColumn('{{%projects}}', 'start_date');
    }
}

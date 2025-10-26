<?php
namespace console\migrations;


use yii\db\Migration;




class m241021_000000_add_view_comands_permission_to_permissions_table extends Migration
{
    public function safeUp()
    {
        // Можно добавить несколько прав за раз
        $this->batchInsert('{{%permissions}}',
            ['name', 'label', 'description', 'category', 'created_at'],
            [
                ['view_teams', 'Просмотр команд', 'Просмотр списка команд', 'teams', time()],
                ['manage_teams', 'Управление командами', 'Создание и редактирование команд', 'teams', time()],
                ['delete_teams', 'Удаление команд', 'Удаление команд из системы', 'teams', time()],
                ['view_my_team', 'Просмотр моей команды', 'Просмотр моей команды', 'teams', time() ],
            ]
        );
    }

    public function safeDown()
    {
        // Откат миграции - удаляем добавленные права
        $this->delete('{{%permissions}}', ['name' => 'manage_teams']);

        // Или несколько за раз
        $this->delete('{{%permissions}}', ['in', 'name', [
            'view_teams',
            'manage_teams',
            'delete_teams'
        ]]);
    }

}
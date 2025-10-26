<?php
namespace console\migrations;


use yii\db\Migration;




class m241014_000000_create_permissions_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%permissions}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(100)->notNull()->unique(),
            'label' => $this->string(255)->notNull(),
            'description' => $this->text(),
            'category' => $this->string(50),
            'created_at' => $this->integer()->notNull(),
        ]);

        // Вставляем базовые права
        $this->batchInsert('{{%permissions}}', ['name', 'label', 'description', 'category', 'created_at'], [
            ['view_dashboard', 'Просмотр дашборда', 'Доступ к главной панели управления', 'dashboard', time()],
            ['view_workers', 'Просмотр сотрудников', 'Просмотр списка сотрудников', 'workers', time()],
            ['manage_workers', 'Управление сотрудниками', 'Добавление и редактирование сотрудников', 'workers', time()],
            ['delete_workers', 'Удаление сотрудников', 'Удаление сотрудников из системы', 'workers', time()],
            ['view_tasks', 'Просмотр задач', 'Просмотр списка задач', 'tasks', time()],
            ['manage_tasks', 'Управление задачами', 'Создание и редактирование задач', 'tasks', time()],
            ['assign_tasks', 'Назначение задач', 'Назначение задач работникам', 'tasks', time()],
            ['view_requests', 'Просмотр заявок', 'Просмотр списка заявок', 'requests', time()],
            ['manage_requests', 'Управление заявками', 'Обработка и изменение заявок', 'requests', time()],
            ['view_profile', 'Просмотр профиля', 'Просмотр своего профиля', 'profile', time()],
            ['edit_profile', 'Редактирование профиля', 'Изменение данных профиля', 'profile', time()],
            ['manage_permissions', 'Управление правами', 'Настройка прав доступа для ролей', 'admin', time()],
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%permissions}}');
    }
}
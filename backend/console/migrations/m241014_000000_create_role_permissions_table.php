<?php

namespace console\migrations;

use yii\db\Migration;

class m241014_000000_create_role_permissions_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%role_permissions}}', [
            'id' => $this->primaryKey(),
            'role' => $this->integer()->notNull(),
            'permission_name' => $this->string(100)->notNull(),
            'created_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx-role_permissions-role', '{{%role_permissions}}', 'role');
        $this->createIndex('idx-role_permissions-permission', '{{%role_permissions}}', 'permission_name');
        $this->addForeignKey(
            'fk-role_permissions-permission',
            '{{%role_permissions}}',
            'permission_name',
            '{{%permissions}}',
            'name',
            'CASCADE'
        );

        // Назначаем права для ролей
        $adminPermissions = [
            'view_dashboard', 'view_workers', 'manage_workers', 'delete_workers',
            'view_tasks', 'manage_tasks', 'assign_tasks',
            'view_requests', 'manage_requests',
            'view_profile', 'edit_profile', 'manage_permissions'
        ];

        $teamleadPermissions = [
            'view_dashboard', 'view_workers', 'manage_workers',
            'view_tasks', 'manage_tasks', 'assign_tasks',
            'view_requests', 'manage_requests',
            'view_profile', 'edit_profile'
        ];

        $employeePermissions = [
            'view_dashboard', 'view_workers',
            'view_tasks', 'view_requests',
            'view_profile', 'edit_profile'
        ];

        $time = time();

        // Админ (role = 1)
        foreach ($adminPermissions as $perm) {
            $this->insert('{{%role_permissions}}', ['role' => 1, 'permission_name' => $perm, 'created_at' => $time]);
        }

        // Тимлид (role = 2)
        foreach ($teamleadPermissions as $perm) {
            $this->insert('{{%role_permissions}}', ['role' => 2, 'permission_name' => $perm, 'created_at' => $time]);
        }

        // Сотрудник (role = 3)
        foreach ($employeePermissions as $perm) {
            $this->insert('{{%role_permissions}}', ['role' => 3, 'permission_name' => $perm, 'created_at' => $time]);
        }
    }

    public function safeDown()
    {
        $this->dropTable('{{%role_permissions}}');
    }
}
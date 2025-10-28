<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddAdminFieldsToOrganizations extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('organizations');
        $table->addColumn('is_active', 'boolean', [
                'default' => true,
                'null' => false,
                'after' => 'name'
            ])
            ->addColumn('settings', 'text', [
                'null' => true,
                'after' => 'is_active'
            ])
            ->addColumn('contact_email', 'string', [
                'limit' => 255,
                'null' => true,
                'after' => 'settings'
            ])
            ->addColumn('contact_phone', 'string', [
                'limit' => 50,
                'null' => true,
                'after' => 'contact_email'
            ])
            ->update();
    }
}

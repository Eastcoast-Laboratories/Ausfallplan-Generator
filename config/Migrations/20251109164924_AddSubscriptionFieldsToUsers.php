<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddSubscriptionFieldsToUsers extends BaseMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/4/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('users');
        $table->addColumn('subscription_plan', 'string', [
            'default' => 'test',
            'limit' => 50,
            'null' => false,
            'comment' => 'test, pro, enterprise'
        ]);
        $table->addColumn('subscription_status', 'string', [
            'default' => 'active',
            'limit' => 50,
            'null' => false,
            'comment' => 'active, expired, cancelled, pending'
        ]);
        $table->addColumn('subscription_started_at', 'datetime', [
            'default' => null,
            'null' => true,
        ]);
        $table->addColumn('subscription_expires_at', 'datetime', [
            'default' => null,
            'null' => true,
        ]);
        $table->addColumn('payment_method', 'string', [
            'default' => null,
            'limit' => 50,
            'null' => true,
            'comment' => 'paypal, bank_transfer'
        ]);
        $table->update();
    }
}

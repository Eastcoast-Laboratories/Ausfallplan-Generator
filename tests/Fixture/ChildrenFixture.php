<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ChildrenFixture
 */
class ChildrenFixture extends TestFixture
{
    /**
     * Table name
     *
     * @var string
     */
    public string $table = 'children';

    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [];
        parent::init();
    }
}

<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Test organization autocomplete API
 */
class OrganizationsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Organizations',
    ];

    /**
     * Test search returns matching organizations
     */
    public function testSearchReturnsMatchingOrganizations()
    {
        // Create test organizations
        $orgsTable = $this->getTableLocator()->get('Organizations');
        $orgsTable->saveMany($orgsTable->newEntities([
            ['name' => 'Kita Sonnenschein'],
            ['name' => 'Kita Regenbogen'],
            ['name' => 'Kita Sternenhimmel'],
            ['name' => 'keine organisation'],
        ]));

        $this->configRequest(['headers' => ['Accept' => 'application/json']]);
        $this->get('/api/organizations/search?q=Kita');

        $this->assertResponseOk();
        $this->assertContentType('application/json');

        $result = json_decode((string)$this->_response->getBody(), true);
        $this->assertArrayHasKey('organizations', $result);
        $this->assertCount(5, $result['organizations']); // 2 from fixture + 3 from test data (excludes "keine organisation")

        $names = array_column($result['organizations'], 'name');
        $this->assertContains('Kita Sonnenschein', $names);
        $this->assertContains('Test Kita', $names); // From fixture
        $this->assertNotContains('keine organisation', $names);
    }

    /**
     * Test search requires minimum 2 characters
     */
    public function testSearchRequiresMinimumChars()
    {
        $this->configRequest(['headers' => ['Accept' => 'application/json']]);
        $this->get('/api/organizations/search?q=K');

        $this->assertResponseOk();

        $result = json_decode((string)$this->_response->getBody(), true);
        $this->assertEmpty($result['organizations']);
    }
}

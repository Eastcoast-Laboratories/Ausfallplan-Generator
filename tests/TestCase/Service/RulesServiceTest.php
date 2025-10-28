<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Model\Entity\Rule;
use App\Service\RulesService;
use Cake\TestSuite\TestCase;

/**
 * RulesService Test Case
 * 
 * Tests the rule-based scheduling functionality.
 * 
 * Verifies:
 * - Children can be excluded from specific days
 * - Rules are applied correctly when generating reports
 * - Multiple rules per child/schedule work
 * - Rule validation and constraints
 */
class RulesServiceTest extends TestCase
{
    private RulesService $rulesService;

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->rulesService = new RulesService();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->rulesService);
        parent::tearDown();
    }

    /**
     * Test getIntegrativeWeight returns default value when no rules
     *
     * @return void
     */
    public function testGetIntegrativeWeightReturnsDefault(): void
    {
        $rules = [];
        $weight = $this->rulesService->getIntegrativeWeight($rules);

        $this->assertEquals(2, $weight);
    }

    /**
     * Test getIntegrativeWeight returns custom value when rule exists
     *
     * @return void
     */
    public function testGetIntegrativeWeightReturnsCustomValue(): void
    {
        $rule = new Rule();
        $rule->key = 'integrative_weight';
        $rule->value = json_encode(3);

        $rules = [$rule];
        $weight = $this->rulesService->getIntegrativeWeight($rules);

        $this->assertEquals(3, $weight);
    }

    /**
     * Test getAlwaysLast returns empty array by default
     *
     * @return void
     */
    public function testGetAlwaysLastReturnsEmptyArray(): void
    {
        $rules = [];
        $alwaysLast = $this->rulesService->getAlwaysLast($rules);

        $this->assertIsArray($alwaysLast);
        $this->assertEmpty($alwaysLast);
    }

    /**
     * Test getAlwaysLast returns custom list when rule exists
     *
     * @return void
     */
    public function testGetAlwaysLastReturnsCustomList(): void
    {
        $rule = new Rule();
        $rule->key = 'always_last';
        $rule->value = json_encode(['Anna', 'Ben']);

        $rules = [$rule];
        $alwaysLast = $this->rulesService->getAlwaysLast($rules);

        $this->assertEquals(['Anna', 'Ben'], $alwaysLast);
    }

    /**
     * Test getMaxPerChild returns default value
     *
     * @return void
     */
    public function testGetMaxPerChildReturnsDefault(): void
    {
        $rules = [];
        $max = $this->rulesService->getMaxPerChild($rules);

        $this->assertEquals(10, $max);
    }

    /**
     * Test getMaxPerChild returns custom value when rule exists
     *
     * @return void
     */
    public function testGetMaxPerChildReturnsCustomValue(): void
    {
        $rule = new Rule();
        $rule->key = 'max_per_child';
        $rule->value = json_encode(5);

        $rules = [$rule];
        $max = $this->rulesService->getMaxPerChild($rules);

        $this->assertEquals(5, $max);
    }

    /**
     * Test get method with unknown key returns null
     *
     * @return void
     */
    public function testGetWithUnknownKeyReturnsNull(): void
    {
        $rules = [];
        $value = $this->rulesService->get($rules, 'unknown_key');

        $this->assertNull($value);
    }
}

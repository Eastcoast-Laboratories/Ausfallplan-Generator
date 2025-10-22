<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Model\Entity\Assignment;
use App\Model\Entity\Child;
use App\Model\Entity\Organization;
use App\Model\Entity\Rule;
use App\Model\Entity\Schedule;
use App\Model\Entity\ScheduleDay;
use App\Model\Entity\SiblingGroup;
use App\Service\ScheduleBuilder;
use Cake\I18n\Date;
use Cake\TestSuite\TestCase;

/**
 * ScheduleBuilder Service Test Case
 */
class ScheduleBuilderTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var list<string>
     */
    protected array $fixtures = [
        'app.Organizations',
        'app.Children',
        'app.SiblingGroups',
        'app.Schedules',
        'app.ScheduleDays',
        'app.Assignments',
        'app.Rules',
    ];

    private ScheduleBuilder $builder;

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new ScheduleBuilder();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->builder);
        parent::tearDown();
    }

    /**
     * Test that builder respects capacity limits
     *
     * @return void
     */
    public function testBuilderRespectsCapacity(): void
    {
        // Create organization
        $organizationsTable = $this->getTableLocator()->get('Organizations');
        $org = $organizationsTable->newEntity([
            'name' => 'Test Kita',
            'locale' => 'de_DE',
        ]);
        $organizationsTable->save($org);

        // Create schedule with one day, capacity 3
        $schedulesTable = $this->getTableLocator()->get('Schedules');
        $schedule = $schedulesTable->newEntity([
            'organization_id' => $org->id,
            'title' => 'Week 1',
            'starts_on' => Date::now(),
            'ends_on' => Date::now()->addDays(7),
            'state' => 'draft',
        ]);
        $schedulesTable->save($schedule);

        $scheduleDaysTable = $this->getTableLocator()->get('ScheduleDays');
        $day = $scheduleDaysTable->newEntity([
            'schedule_id' => $schedule->id,
            'title' => 'Tag 1',
            'position' => 1,
            'capacity' => 3,
        ]);
        $scheduleDaysTable->save($day);

        // Create 5 children (more than capacity)
        $childrenTable = $this->getTableLocator()->get('Children');
        for ($i = 1; $i <= 5; $i++) {
            $child = $childrenTable->newEntity([
                'organization_id' => $org->id,
                'name' => "Child $i",
                'is_integrative' => false,
                'is_active' => true,
            ]);
            $childrenTable->save($child);
        }

        // Load schedule with associations
        $schedule = $schedulesTable->get(
            $schedule->id,
            contain: ['ScheduleDays', 'Rules']
        );
        $schedule->schedule_days = [$day];
        $schedule->rules = [];

        // Build schedule
        $count = $this->builder->build($schedule);

        // Should create exactly 3 assignments (capacity limit)
        $this->assertEquals(3, $count);

        // Verify in database
        $assignmentsTable = $this->getTableLocator()->get('Assignments');
        $assignments = $assignmentsTable->find()
            ->where(['schedule_day_id' => $day->id])
            ->count();
        $this->assertEquals(3, $assignments);
    }

    /**
     * Test integrative children use correct weight
     *
     * @return void
     */
    public function testIntegrativeChildrenUseCorrectWeight(): void
    {
        // Create organization
        $organizationsTable = $this->getTableLocator()->get('Organizations');
        $org = $organizationsTable->newEntity([
            'name' => 'Test Kita',
            'locale' => 'de_DE',
        ]);
        $organizationsTable->save($org);

        // Create schedule with one day, capacity 5
        $schedulesTable = $this->getTableLocator()->get('Schedules');
        $schedule = $schedulesTable->newEntity([
            'organization_id' => $org->id,
            'title' => 'Week 1',
            'starts_on' => Date::now(),
            'ends_on' => Date::now()->addDays(7),
            'state' => 'draft',
        ]);
        $schedulesTable->save($schedule);

        $scheduleDaysTable = $this->getTableLocator()->get('ScheduleDays');
        $day = $scheduleDaysTable->newEntity([
            'schedule_id' => $schedule->id,
            'title' => 'Tag 1',
            'position' => 1,
            'capacity' => 5,
        ]);
        $scheduleDaysTable->save($day);

        // Create 1 integrative child (weight 2) and 5 normal children
        $childrenTable = $this->getTableLocator()->get('Children');
        
        $integrativeChild = $childrenTable->newEntity([
            'organization_id' => $org->id,
            'name' => 'Integrative Child',
            'is_integrative' => true,
            'is_active' => true,
        ]);
        $childrenTable->save($integrativeChild);

        for ($i = 1; $i <= 5; $i++) {
            $child = $childrenTable->newEntity([
                'organization_id' => $org->id,
                'name' => "Normal Child $i",
                'is_integrative' => false,
                'is_active' => true,
            ]);
            $childrenTable->save($child);
        }

        // Load schedule with associations
        $schedule = $schedulesTable->get(
            $schedule->id,
            contain: ['ScheduleDays', 'Rules']
        );
        $schedule->schedule_days = [$day];
        $schedule->rules = [];

        // Build schedule
        $count = $this->builder->build($schedule);

        // With capacity 5: integrative takes 2, so can fit 3 normal children or 1 integrative + 3 normal
        $this->assertGreaterThan(0, $count);
        $this->assertLessThanOrEqual(4, $count); // 1 integrative (weight 2) + 3 normal = 4 children

        // Verify integrative child has weight 2
        $assignmentsTable = $this->getTableLocator()->get('Assignments');
        $integrativeAssignment = $assignmentsTable->find()
            ->where([
                'schedule_day_id' => $day->id,
                'child_id' => $integrativeChild->id,
            ])
            ->first();

        if ($integrativeAssignment) {
            $this->assertEquals(2, $integrativeAssignment->weight);
        }
    }
}

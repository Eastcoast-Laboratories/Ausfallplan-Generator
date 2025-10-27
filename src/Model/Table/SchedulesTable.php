<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Schedules Model
 *
 * @property \App\Model\Table\OrganizationsTable&\Cake\ORM\Association\BelongsTo $Organizations
 * @property \App\Model\Table\ScheduleDaysTable&\Cake\ORM\Association\HasMany $ScheduleDays
 * @property \App\Model\Table\WaitlistEntriesTable&\Cake\ORM\Association\HasMany $WaitlistEntries
 * @property \App\Model\Table\RulesTable&\Cake\ORM\Association\HasMany $Rules
 *
 * @method \App\Model\Entity\Schedule newEmptyEntity()
 * @method \App\Model\Entity\Schedule newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Schedule> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Schedule get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Schedule findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Schedule patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Schedule> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Schedule|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Schedule saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Schedule>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Schedule>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Schedule>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Schedule> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Schedule>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Schedule>|false deleteMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\Schedule>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Schedule> deleteManyOrFail(iterable $entities, array $options = [])
 */
class SchedulesTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('schedules');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Organizations', [
            'foreignKey' => 'organization_id',
            'joinType' => 'INNER',
            'className' => 'Organizations',
        ]);
        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'className' => 'Users',
        ]);
        $this->hasMany('ScheduleDays', [
            'foreignKey' => 'schedule_id',
            'className' => 'ScheduleDays',
        ]);
        $this->hasMany('WaitlistEntries', [
            'foreignKey' => 'schedule_id',
            'className' => 'WaitlistEntries',
        ]);
        $this->hasMany('Rules', [
            'foreignKey' => 'schedule_id',
            'className' => 'Rules',
        ]);
        $this->hasMany('ScheduleChildren', [
            'foreignKey' => 'schedule_id',
            'className' => 'ScheduleChildren',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('organization_id')
            ->requirePresence('organization_id', 'create')
            ->notEmptyString('organization_id');

        $validator
            ->scalar('title')
            ->maxLength('title', 255)
            ->requirePresence('title', 'create')
            ->notEmptyString('title');

        $validator
            ->date('starts_on')
            ->requirePresence('starts_on', 'create')
            ->notEmptyDate('starts_on');

        $validator
            ->date('ends_on')
            ->allowEmptyDate('ends_on');

        $validator
            ->scalar('state')
            ->maxLength('state', 20)
            ->notEmptyString('state')
            ->inList('state', ['draft', 'final']);

        $validator
            ->integer('capacity_per_day')
            ->allowEmptyString('capacity_per_day')
            ->greaterThan('capacity_per_day', 0);

        return $validator;
    }
}

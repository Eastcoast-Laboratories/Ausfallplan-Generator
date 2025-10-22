<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * WaitlistEntries Model
 *
 * @property \App\Model\Table\SchedulesTable&\Cake\ORM\Association\BelongsTo $Schedules
 * @property \App\Model\Table\ChildrenTable&\Cake\ORM\Association\BelongsTo $Children
 *
 * @method \App\Model\Entity\WaitlistEntry newEmptyEntity()
 * @method \App\Model\Entity\WaitlistEntry newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\WaitlistEntry> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\WaitlistEntry get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\WaitlistEntry findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\WaitlistEntry patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\WaitlistEntry> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\WaitlistEntry|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\WaitlistEntry saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\WaitlistEntry>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\WaitlistEntry>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\WaitlistEntry>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\WaitlistEntry> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\WaitlistEntry>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\WaitlistEntry>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\WaitlistEntry>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\WaitlistEntry> deleteManyOrFail(iterable $entities, array $options = [])
 */
class WaitlistEntriesTable extends Table
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

        $this->setTable('waitlist_entries');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Schedules', [
            'foreignKey' => 'schedule_id',
            'joinType' => 'INNER',
            'className' => 'Schedules',
        ]);
        $this->belongsTo('Children', [
            'foreignKey' => 'child_id',
            'joinType' => 'INNER',
            'className' => 'Children',
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
            ->integer('schedule_id')
            ->requirePresence('schedule_id', 'create')
            ->notEmptyString('schedule_id');

        $validator
            ->integer('child_id')
            ->requirePresence('child_id', 'create')
            ->notEmptyString('child_id');

        $validator
            ->integer('priority')
            ->notEmptyString('priority');

        $validator
            ->integer('remaining')
            ->notEmptyString('remaining')
            ->greaterThanOrEqual('remaining', 0);

        return $validator;
    }
}

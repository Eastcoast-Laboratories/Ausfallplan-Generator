<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Assignments Model
 *
 * @property \App\Model\Table\ScheduleDaysTable&\Cake\ORM\Association\BelongsTo $ScheduleDays
 * @property \App\Model\Table\ChildrenTable&\Cake\ORM\Association\BelongsTo $Children
 *
 * @method \App\Model\Entity\Assignment newEmptyEntity()
 * @method \App\Model\Entity\Assignment newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Assignment> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Assignment get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Assignment findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Assignment patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Assignment> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Assignment|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Assignment saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Assignment>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Assignment>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Assignment>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Assignment> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Assignment>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Assignment>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Assignment>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Assignment> deleteManyOrFail(iterable $entities, array $options = [])
 */
class AssignmentsTable extends Table
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

        $this->setTable('assignments');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('ScheduleDays', [
            'foreignKey' => 'schedule_day_id',
            'joinType' => 'INNER',
            'className' => 'ScheduleDays',
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
            ->integer('schedule_day_id')
            ->requirePresence('schedule_day_id', 'create')
            ->notEmptyString('schedule_day_id');

        $validator
            ->integer('child_id')
            ->requirePresence('child_id', 'create')
            ->notEmptyString('child_id');

        $validator
            ->integer('weight')
            ->notEmptyString('weight')
            ->greaterThan('weight', 0);

        $validator
            ->scalar('source')
            ->maxLength('source', 20)
            ->notEmptyString('source')
            ->inList('source', ['auto', 'manual', 'waitlist']);

        return $validator;
    }
}

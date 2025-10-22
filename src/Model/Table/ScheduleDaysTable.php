<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * ScheduleDays Model
 *
 * @property \App\Model\Table\SchedulesTable&\Cake\ORM\Association\BelongsTo $Schedules
 * @property \App\Model\Table\ChildrenTable&\Cake\ORM\Association\BelongsTo $Children
 * @property \App\Model\Table\AssignmentsTable&\Cake\ORM\Association\HasMany $Assignments
 *
 * @method \App\Model\Entity\ScheduleDay newEmptyEntity()
 * @method \App\Model\Entity\ScheduleDay newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\ScheduleDay> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\ScheduleDay get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\ScheduleDay findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\ScheduleDay patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\ScheduleDay> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\ScheduleDay|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\ScheduleDay saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\ScheduleDay>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\ScheduleDay>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\ScheduleDay>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\ScheduleDay> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\ScheduleDay>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\ScheduleDay>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\ScheduleDay>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\ScheduleDay> deleteManyOrFail(iterable $entities, array $options = [])
 */
class ScheduleDaysTable extends Table
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

        $this->setTable('schedule_days');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Schedules', [
            'foreignKey' => 'schedule_id',
            'joinType' => 'INNER',
            'className' => 'Schedules',
        ]);
        $this->belongsTo('Children', [
            'foreignKey' => 'start_child_id',
            'className' => 'Children',
            'propertyName' => 'start_child',
        ]);
        $this->hasMany('Assignments', [
            'foreignKey' => 'schedule_day_id',
            'className' => 'Assignments',
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
            ->scalar('title')
            ->maxLength('title', 255)
            ->requirePresence('title', 'create')
            ->notEmptyString('title');

        $validator
            ->integer('position')
            ->notEmptyString('position');

        $validator
            ->integer('capacity')
            ->notEmptyString('capacity')
            ->greaterThan('capacity', 0);

        $validator
            ->integer('start_child_id')
            ->allowEmptyString('start_child_id');

        return $validator;
    }
}

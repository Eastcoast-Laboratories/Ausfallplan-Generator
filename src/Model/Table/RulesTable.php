<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Rules Model
 *
 * @property \App\Model\Table\SchedulesTable&\Cake\ORM\Association\BelongsTo $Schedules
 *
 * @method \App\Model\Entity\Rule newEmptyEntity()
 * @method \App\Model\Entity\Rule newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Rule> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Rule get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Rule findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Rule patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Rule> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Rule|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Rule saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Rule>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Rule>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Rule>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Rule> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Rule>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Rule>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Rule>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Rule> deleteManyOrFail(iterable $entities, array $options = [])
 */
class RulesTable extends Table
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

        $this->setTable('rules');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Schedules', [
            'foreignKey' => 'schedule_id',
            'joinType' => 'INNER',
            'className' => 'Schedules',
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
            ->scalar('key')
            ->maxLength('key', 100)
            ->requirePresence('key', 'create')
            ->notEmptyString('key');

        $validator
            ->scalar('value')
            ->requirePresence('value', 'create')
            ->notEmptyString('value');

        return $validator;
    }
}

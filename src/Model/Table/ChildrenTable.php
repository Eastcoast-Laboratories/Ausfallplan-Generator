<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Children Model
 *
 * @property \App\Model\Table\OrganizationsTable&\Cake\ORM\Association\BelongsTo $Organizations
 * @property \App\Model\Table\SiblingGroupsTable&\Cake\ORM\Association\BelongsTo $SiblingGroups
 * @property \App\Model\Table\AssignmentsTable&\Cake\ORM\Association\HasMany $Assignments
 * @property \App\Model\Table\WaitlistEntriesTable&\Cake\ORM\Association\HasMany $WaitlistEntries
 *
 * @method \App\Model\Entity\Child newEmptyEntity()
 * @method \App\Model\Entity\Child newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Child> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Child get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Child findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Child patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Child> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Child|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Child saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Child>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Child>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Child>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Child> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Child>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Child>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Child>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Child> deleteManyOrFail(iterable $entities, array $options = [])
 */
class ChildrenTable extends Table
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

        $this->setTable('children');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Organizations', [
            'foreignKey' => 'organization_id',
            'joinType' => 'INNER',
            'className' => 'Organizations',
        ]);
        $this->belongsTo('SiblingGroups', [
            'foreignKey' => 'sibling_group_id',
            'className' => 'SiblingGroups',
        ]);
        $this->hasMany('Assignments', [
            'foreignKey' => 'child_id',
            'className' => 'Assignments',
        ]);
        $this->hasMany('WaitlistEntries', [
            'foreignKey' => 'child_id',
            'className' => 'WaitlistEntries',
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
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->boolean('is_integrative')
            ->notEmptyString('is_integrative');

        $validator
            ->boolean('is_active')
            ->notEmptyString('is_active');

        $validator
            ->integer('sibling_group_id')
            ->allowEmptyString('sibling_group_id');

        return $validator;
    }
}

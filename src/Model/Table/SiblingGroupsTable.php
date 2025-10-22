<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * SiblingGroups Model
 *
 * @property \App\Model\Table\OrganizationsTable&\Cake\ORM\Association\BelongsTo $Organizations
 * @property \App\Model\Table\ChildrenTable&\Cake\ORM\Association\HasMany $Children
 *
 * @method \App\Model\Entity\SiblingGroup newEmptyEntity()
 * @method \App\Model\Entity\SiblingGroup newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\SiblingGroup> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\SiblingGroup get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\SiblingGroup findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\SiblingGroup patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\SiblingGroup> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\SiblingGroup|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\SiblingGroup saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\SiblingGroup>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\SiblingGroup>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\SiblingGroup>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\SiblingGroup> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\SiblingGroup>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\SiblingGroup>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\SiblingGroup>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\SiblingGroup> deleteManyOrFail(iterable $entities, array $options = [])
 */
class SiblingGroupsTable extends Table
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

        $this->setTable('sibling_groups');
        $this->setDisplayField('label');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Organizations', [
            'foreignKey' => 'organization_id',
            'joinType' => 'INNER',
            'className' => 'Organizations',
        ]);
        $this->hasMany('Children', [
            'foreignKey' => 'sibling_group_id',
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
            ->integer('organization_id')
            ->requirePresence('organization_id', 'create')
            ->notEmptyString('organization_id');

        $validator
            ->scalar('label')
            ->maxLength('label', 255)
            ->allowEmptyString('label');

        return $validator;
    }
}

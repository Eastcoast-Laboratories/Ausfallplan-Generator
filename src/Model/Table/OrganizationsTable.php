<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Organizations Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\HasMany $Users
 * @property \App\Model\Table\ChildrenTable&\Cake\ORM\Association\HasMany $Children
 * @property \App\Model\Table\SchedulesTable&\Cake\ORM\Association\HasMany $Schedules
 * @property \App\Model\Table\SiblingGroupsTable&\Cake\ORM\Association\HasMany $SiblingGroups
 *
 * @method \App\Model\Entity\Organization newEmptyEntity()
 * @method \App\Model\Entity\Organization newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Organization> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Organization get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Organization findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Organization patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Organization> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Organization|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Organization saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Organization>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Organization>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Organization>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Organization> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Organization>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Organization>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Organization>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Organization> deleteManyOrFail(iterable $entities, array $options = [])
 */
class OrganizationsTable extends Table
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

        $this->setTable('organizations');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->hasMany('Users', [
            'foreignKey' => 'organization_id',
            'className' => 'Users',
        ]);
        $this->hasMany('Children', [
            'foreignKey' => 'organization_id',
            'className' => 'Children',
        ]);
        $this->hasMany('Schedules', [
            'foreignKey' => 'organization_id',
            'className' => 'Schedules',
        ]);
        $this->hasMany('SiblingGroups', [
            'foreignKey' => 'organization_id',
            'className' => 'SiblingGroups',
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
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('locale')
            ->maxLength('locale', 10)
            ->notEmptyString('locale');

        return $validator;
    }
}

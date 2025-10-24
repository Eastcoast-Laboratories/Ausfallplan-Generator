<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\ORM\RulesChecker;

/**
 * OrganizationUsers Model
 *
 * @property \App\Model\Table\OrganizationsTable&\Cake\ORM\Association\BelongsTo $Organizations
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Inviters
 *
 * @method \App\Model\Entity\OrganizationUser newEmptyEntity()
 * @method \App\Model\Entity\OrganizationUser newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\OrganizationUser> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\OrganizationUser get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\OrganizationUser findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\OrganizationUser patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\OrganizationUser> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\OrganizationUser|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\OrganizationUser saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\OrganizationUser>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\OrganizationUser>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\OrganizationUser>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\OrganizationUser> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\OrganizationUser>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\OrganizationUser>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\OrganizationUser>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\OrganizationUser> deleteManyOrFail(iterable $entities, array $options = [])
 */
class OrganizationUsersTable extends Table
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

        $this->setTable('organization_users');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Organizations', [
            'foreignKey' => 'organization_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Inviters', [
            'className' => 'Users',
            'foreignKey' => 'invited_by',
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
            ->integer('user_id')
            ->requirePresence('user_id', 'create')
            ->notEmptyString('user_id');

        $validator
            ->scalar('role')
            ->maxLength('role', 50)
            ->requirePresence('role', 'create')
            ->notEmptyString('role')
            ->inList('role', ['org_admin', 'editor', 'viewer'], __('Ungültige Rolle'));

        $validator
            ->boolean('is_primary')
            ->requirePresence('is_primary', 'create')
            ->notEmptyString('is_primary');

        $validator
            ->dateTime('joined_at')
            ->requirePresence('joined_at', 'create')
            ->notEmptyDateTime('joined_at');

        $validator
            ->integer('invited_by')
            ->allowEmptyString('invited_by');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['organization_id'], 'Organizations'), ['errorField' => 'organization_id']);
        $rules->add($rules->existsIn(['user_id'], 'Users'), ['errorField' => 'user_id']);
        $rules->add($rules->existsIn(['invited_by'], 'Inviters'), ['errorField' => 'invited_by']);
        
        // Unique constraint: User can only be member once per organization
        $rules->add($rules->isUnique(['organization_id', 'user_id'], __('Benutzer ist bereits Mitglied dieser Organisation')));

        // Cannot delete last org_admin
        $rules->addDelete(function ($entity, $options) {
            if ($entity->role !== 'org_admin') {
                return true;
            }

            $adminCount = $this->find()
                ->where([
                    'organization_id' => $entity->organization_id,
                    'role' => 'org_admin',
                ])
                ->count();

            if ($adminCount <= 1) {
                return __('Die letzte Organisations-Admin kann nicht entfernt werden.');
            }

            return true;
        }, 'lastAdminCheck');

        return $rules;
    }
}

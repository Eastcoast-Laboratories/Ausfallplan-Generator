<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * EncryptedDeks Model
 *
 * @property \App\Model\Table\OrganizationsTable&\Cake\ORM\Association\BelongsTo $Organizations
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 *
 * @method \App\Model\Entity\EncryptedDek newEmptyEntity()
 * @method \App\Model\Entity\EncryptedDek newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\EncryptedDek> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\EncryptedDek get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\EncryptedDek findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\EncryptedDek patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\EncryptedDek> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\EncryptedDek|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\EncryptedDek saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\EncryptedDek>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\EncryptedDek>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\EncryptedDek>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\EncryptedDek> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\EncryptedDek>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\EncryptedDek>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\EncryptedDek>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\EncryptedDek> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class EncryptedDeksTable extends Table
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

        $this->setTable('encrypted_deks');
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
            ->scalar('wrapped_dek')
            ->requirePresence('wrapped_dek', 'create')
            ->notEmptyString('wrapped_dek');

        return $validator;
    }

    /**
     * Get wrapped DEK for a user in an organization
     *
     * @param int $organizationId Organization ID
     * @param int $userId User ID
     * @return \App\Model\Entity\EncryptedDek|null
     */
    public function getForUser(int $organizationId, int $userId): ?object
    {
        return $this->find()
            ->where([
                'organization_id' => $organizationId,
                'user_id' => $userId,
            ])
            ->first();
    }

    /**
     * Save or update wrapped DEK for a user
     *
     * @param int $organizationId Organization ID
     * @param int $userId User ID
     * @param string $wrappedDek Wrapped DEK data
     * @return \App\Model\Entity\EncryptedDek|false
     */
    public function setForUser(int $organizationId, int $userId, string $wrappedDek): object|false
    {
        $existing = $this->getForUser($organizationId, $userId);
        
        if ($existing) {
            $existing->wrapped_dek = $wrappedDek;
            return $this->save($existing);
        }
        
        $entity = $this->newEntity([
            'organization_id' => $organizationId,
            'user_id' => $userId,
            'wrapped_dek' => $wrappedDek,
        ]);
        
        return $this->save($entity);
    }

    /**
     * Remove wrapped DEK for a user (revoke access)
     *
     * @param int $organizationId Organization ID
     * @param int $userId User ID
     * @return bool
     */
    public function revokeForUser(int $organizationId, int $userId): bool
    {
        $existing = $this->getForUser($organizationId, $userId);
        
        if ($existing) {
            return (bool)$this->delete($existing);
        }
        
        return true;
    }
}

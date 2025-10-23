<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class PasswordResetsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('password_resets');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('reset_token')
            ->maxLength('reset_token', 255)
            ->requirePresence('reset_token', 'create')
            ->notEmptyString('reset_token');

        $validator
            ->scalar('reset_code')
            ->maxLength('reset_code', 10)
            ->requirePresence('reset_code', 'create')
            ->notEmptyString('reset_code');

        return $validator;
    }
}

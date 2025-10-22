<?php
declare(strict_types=1);

namespace App\Service;

/**
 * Rules Service
 * Manages default and schedule-specific rules
 */
class RulesService
{
    /**
     * Default rules
     */
    private const DEFAULTS = [
        'integrative_weight' => 2,
        'always_last' => [],
        'max_per_child' => 10,
    ];

    /**
     * Get rule value for a schedule
     *
     * @param array<\App\Model\Entity\Rule> $rules Rules from schedule
     * @param string $key Rule key
     * @return mixed Rule value
     */
    public function get(array $rules, string $key): mixed
    {
        foreach ($rules as $rule) {
            if ($rule->key === $key) {
                return json_decode($rule->value, true);
            }
        }

        return self::DEFAULTS[$key] ?? null;
    }

    /**
     * Get integrative weight for a schedule
     *
     * @param array<\App\Model\Entity\Rule> $rules Rules from schedule
     * @return int Weight for integrative children
     */
    public function getIntegrativeWeight(array $rules): int
    {
        return (int)$this->get($rules, 'integrative_weight');
    }

    /**
     * Get always_last list for a schedule
     *
     * @param array<\App\Model\Entity\Rule> $rules Rules from schedule
     * @return array<string> List of child names
     */
    public function getAlwaysLast(array $rules): array
    {
        return (array)$this->get($rules, 'always_last');
    }

    /**
     * Get max assignments per child for a schedule
     *
     * @param array<\App\Model\Entity\Rule> $rules Rules from schedule
     * @return int Maximum assignments per child
     */
    public function getMaxPerChild(array $rules): int
    {
        return (int)$this->get($rules, 'max_per_child');
    }
}

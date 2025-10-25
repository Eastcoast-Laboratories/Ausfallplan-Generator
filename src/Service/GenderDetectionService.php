<?php
declare(strict_types=1);

namespace App\Service;

/**
 * Gender Detection Service
 * 
 * Detects gender based on German first names
 */
class GenderDetectionService
{
    /**
     * Common German male names
     */
    private const MALE_NAMES = [
        'aaron', 'alexander', 'amadeus', 'andreas', 'anton', 'arun',
        'ben', 'benjamin', 'bo',
        'carl', 'caspar', 'christian',
        'daniel', 'david', 'dennis',
        'elias', 'eleutherius', 'emil', 'eric', 'ezra',
        'felix', 'finn', 'florian', 'franz',
        'hannes', 'henrik',
        'jakob', 'jan', 'jannis', 'jonas', 'julian',
        'karl', 'konstantin',
        'lars', 'lennard', 'leon', 'levin', 'liam', 'linus', 'louis', 'luca', 'lukas',
        'marcel', 'marco', 'marcus', 'markus', 'martin', 'mathias', 'matthias', 'max', 'maximilian', 'michael',
        'nael', 'nico', 'niklas', 'noah', 'numan',
        'ole', 'oliver', 'oskar',
        'paul', 'peter', 'philipp',
        'richard', 'robert', 'robin', 'ruben',
        'samuel', 'sebastian', 'simon', 'stefan', 'steffen',
        'theo', 'thomas', 'tim', 'timotheus', 'tobias', 'tom',
        'vincent',
        'wilhelm',
    ];

    /**
     * Common German female names
     */
    private const FEMALE_NAMES = [
        'alessia', 'amelie', 'ana', 'anna', 'annika',
        'charlotte', 'clara', 'clara',
        'elena', 'elisa', 'emilia', 'emma',
        'franziska', 'frieda',
        'hanna', 'hannah',
        'ida',
        'johanna', 'jonna', 'julia',
        'kamila', 'katharina', 'kathrin',
        'lara', 'lea', 'lena', 'lene', 'leonie', 'lilly', 'linda', 'lisa', 'luisa',
        'malia', 'malla', 'marie', 'mia', 'mila',
        'nele', 'nina',
        'paula',
        'rosa',
        'sabrina', 'sarah', 'sina', 'sofia', 'sophia', 'sophie', 'stefanie',
        'theresa', 'tina',
        'valentina', 'vicky', 'viktoria',
        'zoe',
    ];

    /**
     * Detect gender from a given name
     *
     * @param string $name The first name to analyze
     * @return string 'male', 'female', or 'unknown'
     */
    public function detectGender(string $name): string
    {
        $normalized = $this->normalizeName($name);
        
        // Direct match
        if (in_array($normalized, self::MALE_NAMES)) {
            return 'male';
        }
        if (in_array($normalized, self::FEMALE_NAMES)) {
            return 'female';
        }
        
        // Common German name endings
        $lastTwo = substr($normalized, -2);
        $lastThree = substr($normalized, -3);
        
        // Typical female endings
        if (in_array($lastOne = substr($normalized, -1), ['a', 'e']) && 
            !in_array($normalized, self::MALE_NAMES)) {
            // Many German female names end in 'a' or 'e'
            // But check exceptions
            if (in_array($lastTwo, ['el', 'en', 'er', 'on'])) {
                return 'unknown'; // Could be male (Daniel, Sven, Peter, Aaron)
            }
            return 'female';
        }
        
        // Typical male endings
        if (in_array($lastTwo, ['us', 'ar', 'er', 'en', 'on'])) {
            return 'male';
        }
        
        return 'unknown';
    }

    /**
     * Normalize name for comparison
     *
     * @param string $name
     * @return string
     */
    private function normalizeName(string $name): string
    {
        $name = trim($name);
        $name = mb_strtolower($name, 'UTF-8');
        
        // Remove special characters but keep umlauts
        $name = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $name);
        
        return $name;
    }
}

<?php
declare(strict_types=1);

namespace App\Service;

/**
 * CSV Import Service
 * 
 * Handles parsing and processing of kindergarten CSV imports
 */
class CsvImportService
{
    private GenderDetectionService $genderService;
    private AnimalNameService $animalService;

    public function __construct()
    {
        $this->genderService = new GenderDetectionService();
        $this->animalService = new AnimalNameService();
    }

    /**
     * Parse CSV file and extract child data
     *
     * @param string $filePath Path to CSV file
     * @return array Parsed children data
     */
    public function parseCsv(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \RuntimeException('Could not open CSV file');
        }

        $children = [];
        $usedAnimals = [];
        $addressGroups = []; // Group children by address for sibling detection

        // Skip header row
        fgetcsv($handle, 0, ';');

        $rowIndex = 0;
        while (($data = fgetcsv($handle, 0, ';')) !== false) {
            // Skip empty rows
            if (empty($data[0])) {
                continue;
            }

            $firstName = trim($data[0] ?? '');
            $lastName = trim($data[1] ?? '');
            $birthDateStr = trim($data[8] ?? '');
            $address = trim($data[12] ?? ''); // Street
            $postalCode = trim($data[13] ?? ''); // PLZ
            $integrative = (int)trim($data[14] ?? '0'); // i column

            // Skip if no name
            if (empty($firstName)) {
                continue;
            }

            // Parse birth date intelligently
            $birthDate = $this->parseBirthDate($birthDateStr);

            // Detect gender
            $gender = $this->genderService->detectGender($firstName);

            // Get unique animal name for anonymization
            $animalName = $this->animalService->getUniqueAnimal($usedAnimals);
            $usedAnimals[] = $animalName;

            $childData = [
                'row_index' => $rowIndex++,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'full_name' => $firstName . ' ' . $lastName,
                'birth_date' => $birthDate,
                'birth_date_str' => $birthDateStr,
                'gender' => $gender,
                'is_integrative' => $integrative >= 2,
                'address' => $address,
                'postal_code' => $postalCode,
                'animal_name' => $animalName,
                'initial_animal' => substr($firstName, 0, 1) . '. ' . $animalName,
                'sibling_group_id' => null, // Will be set below
            ];

            // Group by address for sibling detection
            if (!empty($address)) {
                $addressKey = mb_strtolower($address);
                if (!isset($addressGroups[$addressKey])) {
                    $addressGroups[$addressKey] = [];
                }
                $addressGroups[$addressKey][] = count($children);
            }

            $children[] = $childData;
        }

        fclose($handle);

        // Detect siblings based on same address
        $siblingGroupId = 1;
        foreach ($addressGroups as $addressKey => $childIndices) {
            if (count($childIndices) > 1) {
                // Multiple children at same address = siblings
                foreach ($childIndices as $index) {
                    $children[$index]['sibling_group_id'] = $siblingGroupId;
                    $children[$index]['sibling_count'] = count($childIndices);
                }
                $siblingGroupId++;
            }
        }

        return $children;
    }

    /**
     * Parse birth date string intelligently
     * Supports formats: DD.MM.YY, DD.MM.YYYY, YYYY-MM-DD, etc.
     *
     * @param string $dateStr Date string
     * @return \DateTime|null Parsed date or null
     */
    private function parseBirthDate(string $dateStr): ?\DateTime
    {
        if (empty($dateStr)) {
            return null;
        }

        // Try DD.MM.YY or DD.MM.YYYY
        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{2,4})$/', $dateStr, $matches)) {
            $day = (int)$matches[1];
            $month = (int)$matches[2];
            $year = (int)$matches[3];

            // Convert 2-digit year to 4-digit
            if ($year < 100) {
                // Assume 20xx for years 0-50, 19xx for 51-99
                $year += ($year <= 50) ? 2000 : 1900;
            }

            try {
                return new \DateTime(sprintf('%04d-%02d-%02d', $year, $month, $day));
            } catch (\Exception $e) {
                return null;
            }
        }

        // Try ISO format YYYY-MM-DD
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $dateStr)) {
            try {
                return new \DateTime($dateStr);
            } catch (\Exception $e) {
                return null;
            }
        }

        // Try other common formats
        try {
            return new \DateTime($dateStr);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Apply anonymization to child name based on mode
     *
     * @param array $childData Child data
     * @param string $anonymizationMode Mode: 'full', 'first_name', 'initial_animal'
     * @return string Anonymized name
     */
    public function anonymizeName(array $childData, string $anonymizationMode): string
    {
        switch ($anonymizationMode) {
            case 'full':
                return $childData['full_name'];
            case 'first_name':
                return $childData['first_name'];
            case 'initial_animal':
                return $childData['initial_animal'];
            default:
                return $childData['full_name'];
        }
    }
}

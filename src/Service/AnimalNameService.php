<?php
declare(strict_types=1);

namespace App\Service;

/**
 * Animal Name Service
 * 
 * Provides random animal names for child anonymization
 */
class AnimalNameService
{
    /**
     * List of cute animal names in German
     */
    private const ANIMALS = [
        'Ameise', 'Bär', 'Biber', 'Biene', 'Dachs', 'Delfin', 'Eichhörnchen', 'Elch', 'Elefant', 'Ente',
        'Esel', 'Eule', 'Falke', 'Fisch', 'Flamingo', 'Fledermaus', 'Fuchs', 'Gans', 'Giraffe', 'Hamster',
        'Hase', 'Hirsch', 'Hund', 'Igel', 'Kakadu', 'Kamel', 'Katze', 'Koala', 'Krokodil', 'Kuh',
        'Lamm', 'Leopard', 'Libelle', 'Löwe', 'Luchs', 'Marienkäfer', 'Maus', 'Möwe', 'Otter', 'Panda',
        'Papagei', 'Pelikan', 'Pfau', 'Pferd', 'Pinguin', 'Rabe', 'Reh', 'Reiher', 'Robbe', 'Schaf',
        'Schmetterling', 'Schnecke', 'Seehund', 'Seepferdchen', 'Schildkröte', 'Schwan', 'Storch', 'Tiger', 'Uhu', 'Waschbär',
        'Wal', 'Wolf', 'Zebra', 'Ziege',
    ];

    /**
     * Get a random animal name
     *
     * @return string
     */
    public function getRandomAnimal(): string
    {
        return self::ANIMALS[array_rand(self::ANIMALS)];
    }

    /**
     * Get a unique animal name (checks against already used names)
     *
     * @param array $usedAnimals Already used animal names
     * @return string
     */
    public function getUniqueAnimal(array $usedAnimals = []): string
    {
        $availableAnimals = array_diff(self::ANIMALS, $usedAnimals);
        
        if (empty($availableAnimals)) {
            // If all animals used, start adding numbers
            return $this->getRandomAnimal() . ' ' . (count($usedAnimals) + 1);
        }
        
        $availableAnimals = array_values($availableAnimals);
        return $availableAnimals[array_rand($availableAnimals)];
    }

    /**
     * Get all available animals
     *
     * @return array
     */
    public function getAllAnimals(): array
    {
        return self::ANIMALS;
    }
}

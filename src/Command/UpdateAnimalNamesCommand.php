<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\ORM\TableRegistry;
use App\Service\ReportService;

/**
 * Update existing schedules with EN animal names
 */
class UpdateAnimalNamesCommand extends Command
{
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $io->out('Updating schedules with EN animal names...');
        
        $schedulesTable = TableRegistry::getTableLocator()->get('Schedules');
        $schedules = $schedulesTable->find()->all();
        
        $reportService = new ReportService();
        $updated = 0;
        
        foreach ($schedules as $schedule) {
            $needsUpdate = false;
            
            if ($schedule->animal_names_sequence) {
                $sequences = @unserialize($schedule->animal_names_sequence);
                
                // Check if it's old format (single array) or missing EN or EN has German names
                if ($sequences && is_array($sequences)) {
                    // If old format (direct array with letters), treat as DE
                    if (!isset($sequences['de']) && !isset($sequences['en'])) {
                        $io->verbose("Old format detected for schedule #{$schedule->id}: {$schedule->title}");
                        $oldSequences = $sequences;
                        $sequences = ['de' => $oldSequences];
                        $needsUpdate = true;
                    }
                    
                    // Generate EN names if missing
                    if (!isset($sequences['en'])) {
                        $io->verbose("Missing EN for schedule #{$schedule->id}: {$schedule->title}");
                        $sequences['en'] = $reportService->generateAnimalNamesSequence('en');
                        $needsUpdate = true;
                    } else {
                        // Check if EN contains German words (wrong)
                        $enSequence = $sequences['en'];
                        $hasGermanWords = false;
                        $germanWords = ['Ameisen', 'Bienen', 'Dachse', 'Esel', 'Fisch', 'Gnu'];
                        
                        foreach ($enSequence as $letter => $animals) {
                            foreach ($animals as $animal) {
                                if (in_array($animal, $germanWords)) {
                                    $hasGermanWords = true;
                                    break 2;
                                }
                            }
                        }
                        
                        if ($hasGermanWords) {
                            $io->warning("EN has German words for schedule #{$schedule->id}: {$schedule->title}");
                            $sequences['en'] = $reportService->generateAnimalNamesSequence('en');
                            $needsUpdate = true;
                        }
                    }
                    
                    if ($needsUpdate) {
                        $schedule->animal_names_sequence = serialize($sequences);
                        
                        if ($schedulesTable->save($schedule)) {
                            $updated++;
                            $io->success("  ✓ Updated schedule #{$schedule->id}");
                        } else {
                            $io->error("  ✗ Failed to update schedule #{$schedule->id}");
                        }
                    }
                }
            } else {
                // No sequence at all, generate both
                $io->verbose("Generating animal names for schedule #{$schedule->id}: {$schedule->title}");
                $sequences = [
                    'de' => $reportService->generateAnimalNamesSequence('de'),
                    'en' => $reportService->generateAnimalNamesSequence('en')
                ];
                $schedule->animal_names_sequence = serialize($sequences);
                
                if ($schedulesTable->save($schedule)) {
                    $updated++;
                    $io->success("  ✓ Generated for schedule #{$schedule->id}");
                } else {
                    $io->error("  ✗ Failed to generate for schedule #{$schedule->id}");
                }
            }
        }
        
        $io->out('');
        $io->success("Updated {$updated} schedule(s)");
        
        return static::CODE_SUCCESS;
    }
}

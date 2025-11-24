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
            if ($schedule->animal_names_sequence) {
                $sequences = @unserialize($schedule->animal_names_sequence);
                
                // Check if it's old format (single array) or missing EN
                if ($sequences && (!is_array($sequences) || !isset($sequences['en']))) {
                    $io->verbose("Updating schedule #{$schedule->id}: {$schedule->title}");
                    
                    // If old format (direct array), treat as DE
                    if (!isset($sequences['de'])) {
                        $oldSequences = $sequences;
                        $sequences = ['de' => $oldSequences];
                    }
                    
                    // Generate EN names
                    if (!isset($sequences['en'])) {
                        $sequences['en'] = $reportService->generateAnimalNamesSequence('en');
                    }
                    
                    $schedule->animal_names_sequence = serialize($sequences);
                    
                    if ($schedulesTable->save($schedule)) {
                        $updated++;
                        $io->success("  ✓ Updated schedule #{$schedule->id}");
                    } else {
                        $io->error("  ✗ Failed to update schedule #{$schedule->id}");
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

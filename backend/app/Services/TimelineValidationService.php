<?php

namespace App\Services;

use App\Models\ProcurementTimelineRule;
use App\Models\ProcessTracking;
use App\Exceptions\TimelineViolationException;

class TimelineValidationService
{
    protected $nepaliDateService;

    public function __construct(NepaliDateService $nepaliDateService)
    {
        $this->nepaliDateService = $nepaliDateService;
    }

    /**
     * Validate if a process step can be performed at given date
     * 
     * Checks:
     * 1. Previous step completed?
     * 2. Minimum days elapsed?
     * 3. Maximum days not exceeded?
     * 4. Legal timeline requirements met?
     * 
     * @throws TimelineViolationException with Nepali error message
     */
    public function validateStep(
        int $projectId, 
        string $stepCode, 
        string $proposedDateBs
    ): bool {
        // Get applicable rules
        $rules = ProcurementTimelineRule::where('to_step', $stepCode)
            ->where('is_active', true)
            ->get();
        
        foreach ($rules as $rule) {
            // Get previous step completion date
            $previousStep = ProcessTracking::where('project_id', $projectId)
                ->where('step_code', $rule->from_step)
                ->where('status', 'completed')
                ->first();
            
            if (!$previousStep && $rule->is_mandatory) {
                throw new TimelineViolationException(
                    "अघिल्लो चरण '{$rule->from_step}' सम्पन्न भएको छैन। " .
                    $rule->legal_reference_np,
                    $rule->act_section,
                    $rule->regulation_rule
                );
            }
            
            if ($previousStep) {
                $daysDiff = $this->nepaliDateService
                    ->daysBetween($previousStep->completed_date_bs, $proposedDateBs);
                
                // Check minimum days
                if ($rule->minimum_days && $daysDiff < $rule->minimum_days) {
                    if ($rule->violation_action === 'block') {
                        throw new TimelineViolationException(
                            $rule->validation_message_np . 
                            " (न्यूनतम {$rule->minimum_days} दिन आवश्यक, " .
                            "तर {$daysDiff} दिन मात्र भएको छ।)",
                            $rule->act_section,
                            $rule->regulation_rule
                        );
                    }
                }
                
                // Check maximum days
                if ($rule->maximum_days && $daysDiff > $rule->maximum_days) {
                    if ($rule->violation_action === 'block') {
                        throw new TimelineViolationException(
                            $rule->validation_message_np .
                            " (अधिकतम {$rule->maximum_days} दिन भित्र गर्नुपर्ने, " .
                            "तर {$daysDiff} दिन भैसकेको छ।)",
                            $rule->act_section,
                            $rule->regulation_rule
                        );
                    }
                }
            }
        }
        
        return true;
    }
    
    /**
     * Check all upcoming deadlines and send notifications
     * Called by scheduled command
     */
    public function checkDeadlines(): void
    {
        $trackings = ProcessTracking::where('status', 'in_progress')
            ->whereNotNull('deadline_at')
            ->get();
        
        foreach ($trackings as $tracking) {
            $daysRemaining = $this->nepaliDateService
                ->daysBetween(
                    $this->nepaliDateService->getCurrentBsDate(),
                    $tracking->deadline_date_bs
                );
            
            // Send notifications at 7, 3, 1, 0 days remaining
            if (in_array($daysRemaining, [7, 3, 1, 0])) {
                $this->notificationService->sendDeadlineNotification(
                    $tracking, $daysRemaining
                );
            }
            
            // Mark as overdue
            if ($daysRemaining < 0) {
                $tracking->update(['status' => 'overdue']);
                $this->notificationService->sendOverdueNotification($tracking);
            }
        }
    }
    
    /**
     * Calculate automatic dates based on rules
     */
    public function calculateDeadlines(
        int $projectId, 
        string $currentStep
    ): array {
        $rules = ProcurementTimelineRule::where('from_step', $currentStep)
            ->where('is_active', true)
            ->get();
        
        $deadlines = [];
        $currentDate = $this->nepaliDateService->getCurrentBsDate();
        
        foreach ($rules as $rule) {
            if ($rule->maximum_days) {
                $deadlines[$rule->to_step] = [
                    'deadline_date_bs' => $this->nepaliDateService
                        ->addDays($currentDate, $rule->maximum_days),
                    'minimum_date_bs' => $rule->minimum_days ? 
                        $this->nepaliDateService
                            ->addDays($currentDate, $rule->minimum_days) : null,
                    'rule' => $rule
                ];
            }
        }
        
        return $deadlines;
    }

    /**
     * Validate if a date meets all timeline rules for a project
     */
    public function validateProjectTimeline(int $projectId, array $steps): array
    {
        $violations = [];
        
        foreach ($steps as $stepCode => $stepDateBs) {
            try {
                $this->validateStep($projectId, $stepCode, $stepDateBs);
            } catch (TimelineViolationException $e) {
                $violations[] = [
                    'step' => $stepCode,
                    'date' => $stepDateBs,
                    'message' => $e->getMessage(),
                    'act_section' => $e->getActSection(),
                    'regulation_rule' => $e->getRegulationRule(),
                ];
            }
        }
        
        return $violations;
    }

    /**
     * Get timeline rules for a specific procurement method
     */
    public function getRulesForMethod(string $procurementMethod): array
    {
        return ProcurementTimelineRule::where('procurement_method', $procurementMethod)
            ->orWhere('procurement_method', 'all')
            ->where('is_active', true)
            ->get()
            ->toArray();
    }

    /**
     * Check if a step can be skipped
     */
    public function canSkipStep(int $projectId, string $stepCode): bool
    {
        $rule = ProcurementTimelineRule::where('to_step', $stepCode)
            ->where('is_mandatory', false)
            ->first();
        
        return $rule !== null;
    }
}
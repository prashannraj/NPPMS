<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProcessStep;
use App\Models\ProcessInstance;
use App\Models\ProcessTransition;
use App\Services\NepaliDateService;
use App\Services\TimelineValidationService;
use App\Exceptions\TimelineViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessFlowService
{
    protected $nepaliDateService;
    protected $timelineValidationService;

    public function __construct(
        NepaliDateService $nepaliDateService,
        TimelineValidationService $timelineValidationService
    ) {
        $this->nepaliDateService = $nepaliDateService;
        $this->timelineValidationService = $timelineValidationService;
    }

    /**
     * Start a new process for a project
     *
     * @param int $projectId
     * @param string $processType 'bidding' or 'consumer_committee'
     * @param array $initialData
     * @return ProcessInstance
     */
    public function startProcess(int $projectId, string $processType, array $initialData = []): ProcessInstance
    {
        return DB::transaction(function () use ($projectId, $processType, $initialData) {
            // Validate project exists
            $project = Project::findOrFail($projectId);

            // Get initial step for this process type
            $initialStep = ProcessStep::where('process_type', $processType)
                ->where('is_initial', true)
                ->firstOrFail();

            // Create process instance
            $instance = ProcessInstance::create([
                'project_id' => $projectId,
                'process_type' => $processType,
                'current_step_id' => $initialStep->id,
                'current_step_code' => $initialStep->step_code,
                'current_status' => 'in_progress',
                'started_at' => now(),
                'started_at_bs' => $this->nepaliDateService->adToBs(now()->format('Y-m-d')),
                'metadata' => $initialData,
            ]);

            // Create first transition
            ProcessTransition::create([
                'process_instance_id' => $instance->id,
                'from_step_id' => null,
                'to_step_id' => $initialStep->id,
                'transition_type' => 'start',
                'transitioned_at' => now(),
                'transitioned_at_bs' => $this->nepaliDateService->adToBs(now()->format('Y-m-d')),
                'transitioned_by' => auth()->id(),
                'remarks_np' => 'प्रक्रिया सुरु गरियो',
                'remarks_en' => 'Process started',
            ]);

            Log::info("Process started for project {$projectId} with type {$processType}", [
                'instance_id' => $instance->id,
                'user_id' => auth()->id(),
            ]);

            return $instance;
        });
    }

    /**
     * Complete a step and move to next step
     *
     * @param int $projectId
     * @param string $stepCode
     * @param array $stepData
     * @param string $remarks
     * @return ProcessInstance
     * @throws TimelineViolationException
     */
    public function completeStep(int $projectId, string $stepCode, array $stepData = [], string $remarks = ''): ProcessInstance
    {
        return DB::transaction(function () use ($projectId, $stepCode, $stepData, $remarks) {
            // Get current process instance
            $instance = ProcessInstance::where('project_id', $projectId)
                ->where('current_status', 'in_progress')
                ->firstOrFail();

            // Validate current step matches
            if ($instance->current_step_code !== $stepCode) {
                throw new \InvalidArgumentException("Current step mismatch. Expected {$instance->current_step_code}, got {$stepCode}");
            }

            // Get current step
            $currentStep = ProcessStep::where('step_code', $stepCode)->firstOrFail();

            // Validate timeline for this step
            $this->timelineValidationService->validateStep(
                $instance->process_type,
                $stepCode,
                $instance->started_at
            );

            // Get next step
            $nextStep = $this->getNextStep($currentStep->id, $stepData);

            // Update instance
            $instance->current_step_id = $nextStep->id;
            $instance->current_step_code = $nextStep->step_code;
            
            // Check if this is final step
            if ($nextStep->is_final) {
                $instance->current_status = 'completed';
                $instance->completed_at = now();
                $instance->completed_at_bs = $this->nepaliDateService->adToBs(now()->format('Y-m-d'));
            }
            
            // Update metadata
            $metadata = $instance->metadata ?? [];
            $metadata['steps'][$stepCode] = [
                'completed_at' => now()->toISOString(),
                'completed_at_bs' => $this->nepaliDateService->adToBs(now()->format('Y-m-d')),
                'data' => $stepData,
                'completed_by' => auth()->id(),
            ];
            $instance->metadata = $metadata;
            
            $instance->save();

            // Create transition
            ProcessTransition::create([
                'process_instance_id' => $instance->id,
                'from_step_id' => $currentStep->id,
                'to_step_id' => $nextStep->id,
                'transition_type' => 'complete',
                'transitioned_at' => now(),
                'transitioned_at_bs' => $this->nepaliDateService->adToBs(now()->format('Y-m-d')),
                'transitioned_by' => auth()->id(),
                'remarks_np' => $remarks ?: "चरण {$currentStep->name_np} पूरा गरियो",
                'remarks_en' => $remarks ?: "Step {$currentStep->name_en} completed",
                'step_data' => $stepData,
            ]);

            Log::info("Step {$stepCode} completed for project {$projectId}", [
                'instance_id' => $instance->id,
                'next_step' => $nextStep->step_code,
                'user_id' => auth()->id(),
            ]);

            return $instance;
        });
    }

    /**
     * Get next step based on current step and conditions
     *
     * @param int $currentStepId
     * @param array $stepData
     * @return ProcessStep
     */
    protected function getNextStep(int $currentStepId, array $stepData = []): ProcessStep
    {
        // Get all possible next steps
        $nextSteps = ProcessStep::whereHas('previousSteps', function ($query) use ($currentStepId) {
            $query->where('previous_step_id', $currentStepId);
        })->get();

        if ($nextSteps->isEmpty()) {
            throw new \RuntimeException("No next step defined for current step ID {$currentStepId}");
        }

        // If only one next step, return it
        if ($nextSteps->count() === 1) {
            return $nextSteps->first();
        }

        // Evaluate conditions to determine which step to take
        foreach ($nextSteps as $step) {
            if ($this->evaluateStepCondition($step, $stepData)) {
                return $step;
            }
        }

        // Default to first step if no conditions match
        return $nextSteps->first();
    }

    /**
     * Evaluate step condition
     *
     * @param ProcessStep $step
     * @param array $stepData
     * @return bool
     */
    protected function evaluateStepCondition(ProcessStep $step, array $stepData): bool
    {
        // Simple condition evaluation based on step metadata
        $condition = $step->condition ?? null;
        
        if (!$condition) {
            return true; // No condition means always valid
        }

        // Parse condition (simplified - in real implementation would use a proper expression evaluator)
        // Example condition: "project_type == 'construction' && estimated_cost > 5000000"
        try {
            // This is a simplified implementation
            // In production, you would use a proper expression evaluator
            return $this->evaluateSimpleCondition($condition, $stepData);
        } catch (\Exception $e) {
            Log::error("Failed to evaluate step condition", [
                'step_id' => $step->id,
                'condition' => $condition,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Simple condition evaluator
     *
     * @param string $condition
     * @param array $data
     * @return bool
     */
    protected function evaluateSimpleCondition(string $condition, array $data): bool
    {
        // Very basic implementation for demonstration
        // In production, use a proper library like symfony/expression-language
        
        $tokens = explode('&&', $condition);
        foreach ($tokens as $token) {
            $token = trim($token);
            
            if (strpos($token, '==') !== false) {
                list($key, $value) = explode('==', $token, 2);
                $key = trim($key);
                $value = trim($value, " '\"");
                
                if (!isset($data[$key]) || $data[$key] != $value) {
                    return false;
                }
            } elseif (strpos($token, '>') !== false) {
                list($key, $value) = explode('>', $token, 2);
                $key = trim($key);
                $value = trim($value);
                
                if (!isset($data[$key]) || $data[$key] <= $value) {
                    return false;
                }
            } elseif (strpos($token, '<') !== false) {
                list($key, $value) = explode('<', $token, 2);
                $key = trim($key);
                $value = trim($value);
                
                if (!isset($data[$key]) || $data[$key] >= $value) {
                    return false;
                }
            }
        }
        
        return true;
    }

    /**
     * Get current step for a project
     *
     * @param int $projectId
     * @return array
     */
    public function getCurrentStep(int $projectId): array
    {
        $instance = ProcessInstance::where('project_id', $projectId)
            ->where('current_status', 'in_progress')
            ->first();

        if (!$instance) {
            return [
                'status' => 'not_started',
                'message' => 'Process not started for this project',
            ];
        }

        $currentStep = ProcessStep::find($instance->current_step_id);

        return [
            'status' => 'in_progress',
            'process_type' => $instance->process_type,
            'current_step' => $currentStep,
            'started_at' => $instance->started_at,
            'started_at_bs' => $instance->started_at_bs,
            'instance_id' => $instance->id,
        ];
    }

    /**
     * Get next possible steps for a project
     *
     * @param int $projectId
     * @return array
     */
    public function getNextSteps(int $projectId): array
    {
        $instance = ProcessInstance::where('project_id', $projectId)
            ->where('current_status', 'in_progress')
            ->first();

        if (!$instance) {
            return [];
        }

        $currentStep = ProcessStep::find($instance->current_step_id);
        $nextSteps = ProcessStep::whereHas('previousSteps', function ($query) use ($currentStep) {
            $query->where('previous_step_id', $currentStep->id);
        })->get();

        return $nextSteps->map(function ($step) {
            return [
                'id' => $step->id,
                'step_code' => $step->step_code,
                'name_np' => $step->name_np,
                'name_en' => $step->name_en,
                'description_np' => $step->description_np,
                'description_en' => $step->description_en,
                'estimated_days' => $step->estimated_days,
                'is_final' => $step->is_final,
                'condition' => $step->condition,
            ];
        })->toArray();
    }

    /**
     * Get process timeline with deadlines
     *
     * @param int $projectId
     * @return array
     */
    public function getProcessTimeline(int $projectId): array
    {
        $instance = ProcessInstance::where('project_id', $projectId)
            ->where('current_status', 'in_progress')
            ->first();

        if (!$instance) {
            return [];
        }

        // Get all steps for this process type
        $steps = ProcessStep::where('process_type', $instance->process_type)
            ->orderBy('sequence')
            ->get();

        $timeline = [];
        $currentDate = $instance->started_at;

        foreach ($steps as $step) {
            $deadline = null;
            if ($step->estimated_days) {
                $deadline = clone $currentDate;
                $deadline->addDays($step->estimated_days);
            }

            $timeline[] = [
                'step' => $step,
                'estimated_start' => $currentDate,
                'estimated_deadline' => $deadline,
                'is_current' => $step->id === $instance->current_step_id,
                'is_completed' => $this->isStepCompleted($instance, $step->step_code),
            ];

            if (!$step->is_parallel) {
                $currentDate = $deadline ? clone $deadline : $currentDate;
            }
        }

        return $timeline;
    }

    /**
     * Check if a step is completed
     *
     * @param ProcessInstance $instance
     * @param string $stepCode
     * @return bool
     */
    protected function isStepCompleted(ProcessInstance $instance, string $stepCode): bool
    {
        $metadata = $instance->metadata ?? [];
        return isset($metadata['steps'][$stepCode]);
    }

    /**
     * Rollback to previous step
     *
     * @param int $projectId
     * @param string $reason
     * @return ProcessInstance
     */
    public function rollbackStep(int $projectId, string $reason = ''): ProcessInstance
    {
        return DB::transaction(function () use ($projectId, $reason) {
            $instance = ProcessInstance::where('project_id', $projectId)
                ->where('current_status', 'in_progress')
                ->firstOrFail();

            // Get last transition
            $lastTransition = ProcessTransition::where('process_instance_id', $instance->id)
                ->where('transition_type', 'complete')
                ->orderBy('transitioned_at', 'desc')
                ->first();

            if (!$lastTransition) {
                throw new \RuntimeException('No previous step to rollback to');
            }

            // Get previous step
            $previousStep = ProcessStep::find($lastTransition->from_step_id);

            // Update instance
            $instance->current_step_id = $previousStep->id;
            $instance->current_step_code = $previousStep->step_code;
            $instance->save();

            // Create rollback transition
            ProcessTransition::create([
                'process_instance_id' => $instance->id,
                'from_step_id' => $instance->current_step_id,
                'to_step_id' => $previousStep->id,
                'transition_type' => 'rollback',
                'transitioned_at' => now(),
                'transitioned_at_bs' => $this->nepaliDateService->adToBs(now()->format('Y-m-d')),
                'transitioned_by' => auth()->id(),
                'remarks_np' => $reason ?: "चरण रोलब्याक गरियो",
                'remarks_en' => $reason ?: "Step rolled back",
            ]);

            Log::warning("Step rolled back for project {$projectId}", [
                'instance_id' => $instance->id,
                'from_step' => $instance->current_step_code,
                'to_step' => $previousStep->step_code,
                'reason' => $reason,
                'user_id' => auth()->id(),
            ]);

            return $instance;
        });
    }

    /**
     * Get process history
     *
     * @param int $projectId
     * @return array
     */
    public function getProcessHistory(int $projectId): array
    {
        $instance = ProcessInstance::where('project_id', $projectId)->first();

        if (!$instance) {
            return [];
        }

        $transitions = ProcessTransition::where('process_instance_id', $instance->id)
            ->with(['fromStep', 'toStep', 'transitionedBy'])
            ->orderBy('transitioned_at', 'asc')
            ->get();

        return $transitions->map(function ($transition) {
            return [
                'id' => $transition->id,
                'from_step' => $transition->fromStep ? $transition->fromStep->name_np : 'सुरुवात',
                'to_step' => $transition->toStep ? $transition->toStep->name_np : 'अन्त्य',
                'transition_type' => $transition->transition_type,
                'transitioned_at' => $transition->transitioned_at,
                'transitioned_at_bs' => $transition->transitioned_at_bs,
                'transitioned_by' => $transition->transitionedBy ? $transition->transitionedBy->name : 'System',
                'remarks_np' => $transition->remarks_np,
                'remarks_en' => $transition->remarks_en,
            ];
        })->toArray();
    }
}
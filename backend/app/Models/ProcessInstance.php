<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProcessInstance extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'project_id',
        'process_type',
        'current_step_id',
        'current_step_code',
        'current_status',
        'started_at',
        'started_at_bs',
        'completed_at',
        'completed_at_bs',
        'metadata',
        'is_active',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = \Illuminate\Support\Str::uuid();
            }
        });
    }

    /**
     * Get the project that owns the process instance.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the current step.
     */
    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(ProcessStep::class, 'current_step_id');
    }

    /**
     * Get the user who created the process instance.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the process instance.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the transitions for the process instance.
     */
    public function transitions(): HasMany
    {
        return $this->hasMany(ProcessTransition::class)->orderBy('transitioned_at', 'asc');
    }

    /**
     * Get the latest transition.
     */
    public function latestTransition(): HasOne
    {
        return $this->hasOne(ProcessTransition::class)->latestOfMany();
    }

    /**
     * Get the first transition.
     */
    public function firstTransition(): HasOne
    {
        return $this->hasOne(ProcessTransition::class)->oldestOfMany();
    }

    /**
     * Scope a query to only include active instances.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include instances by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('current_status', $status);
    }

    /**
     * Scope a query to only include instances by process type.
     */
    public function scopeByProcessType($query, $processType)
    {
        return $query->where('process_type', $processType);
    }

    /**
     * Scope a query to only include instances by project.
     */
    public function scopeByProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Scope a query to only include in-progress instances.
     */
    public function scopeInProgress($query)
    {
        return $query->where('current_status', 'in_progress');
    }

    /**
     * Scope a query to only include completed instances.
     */
    public function scopeCompleted($query)
    {
        return $query->where('current_status', 'completed');
    }

    /**
     * Check if instance is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->current_status === 'in_progress';
    }

    /**
     * Check if instance is completed.
     */
    public function isCompleted(): bool
    {
        return $this->current_status === 'completed';
    }

    /**
     * Check if instance is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->current_status === 'cancelled';
    }

    /**
     * Get the duration in days.
     */
    public function getDurationInDays(): ?int
    {
        if (!$this->started_at) {
            return null;
        }

        $endDate = $this->completed_at ?? now();
        return $this->started_at->diffInDays($endDate);
    }

    /**
     * Get the step data for a specific step.
     */
    public function getStepData(string $stepCode): ?array
    {
        $metadata = $this->metadata ?? [];
        return $metadata['steps'][$stepCode]['data'] ?? null;
    }

    /**
     * Check if a step is completed.
     */
    public function isStepCompleted(string $stepCode): bool
    {
        $metadata = $this->metadata ?? [];
        return isset($metadata['steps'][$stepCode]);
    }

    /**
     * Get completed steps.
     */
    public function getCompletedSteps(): array
    {
        $metadata = $this->metadata ?? [];
        return array_keys($metadata['steps'] ?? []);
    }

    /**
     * Get the completion percentage.
     */
    public function getCompletionPercentage(): float
    {
        $totalSteps = ProcessStep::where('process_type', $this->process_type)->count();
        if ($totalSteps === 0) {
            return 0;
        }

        $completedSteps = count($this->getCompletedSteps());
        return ($completedSteps / $totalSteps) * 100;
    }

    /**
     * Get the current step name in the current locale.
     */
    public function getCurrentStepNameAttribute(): ?string
    {
        if (!$this->currentStep) {
            return null;
        }

        $locale = app()->getLocale();
        return $locale === 'np' ? $this->currentStep->name_np : $this->currentStep->name_en;
    }

    /**
     * Get the process type name.
     */
    public function getProcessTypeNameAttribute(): string
    {
        $types = [
            'bidding' => 'बोलपत्र प्रक्रिया',
            'consumer_committee' => 'उपभोक्ता समिति प्रक्रिया',
        ];

        return $types[$this->process_type] ?? $this->process_type;
    }

    /**
     * Get the status name in the current locale.
     */
    public function getStatusNameAttribute(): string
    {
        $statuses = [
            'not_started' => 'सुरु भएको छैन',
            'in_progress' => 'कार्यरत',
            'completed' => 'पूर्ण',
            'cancelled' => 'रद्द',
            'on_hold' => 'रोकिएको',
        ];

        return $statuses[$this->current_status] ?? $this->current_status;
    }
}
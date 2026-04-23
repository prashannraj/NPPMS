<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessTransition extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'process_instance_id',
        'from_step_id',
        'to_step_id',
        'transition_type',
        'transitioned_at',
        'transitioned_at_bs',
        'transitioned_by',
        'remarks_np',
        'remarks_en',
        'step_data',
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
        'transitioned_at' => 'datetime',
        'step_data' => 'array',
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
            
            if (empty($model->transitioned_at)) {
                $model->transitioned_at = now();
            }
        });
    }

    /**
     * Get the process instance that owns the transition.
     */
    public function processInstance(): BelongsTo
    {
        return $this->belongsTo(ProcessInstance::class);
    }

    /**
     * Get the from step.
     */
    public function fromStep(): BelongsTo
    {
        return $this->belongsTo(ProcessStep::class, 'from_step_id');
    }

    /**
     * Get the to step.
     */
    public function toStep(): BelongsTo
    {
        return $this->belongsTo(ProcessStep::class, 'to_step_id');
    }

    /**
     * Get the user who performed the transition.
     */
    public function transitionedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transitioned_by');
    }

    /**
     * Get the user who created the transition.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the transition.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope a query to only include active transitions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include transitions by type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('transition_type', $type);
    }

    /**
     * Scope a query to only include transitions for a specific process instance.
     */
    public function scopeByProcessInstance($query, $processInstanceId)
    {
        return $query->where('process_instance_id', $processInstanceId);
    }

    /**
     * Scope a query to only include transitions by user.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('transitioned_by', $userId);
    }

    /**
     * Scope a query to only include transitions within a date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('transitioned_at', [$startDate, $endDate]);
    }

    /**
     * Get the transition type name.
     */
    public function getTransitionTypeNameAttribute(): string
    {
        $types = [
            'start' => 'सुरु गर्नुहोस्',
            'complete' => 'पूरा गर्नुहोस्',
            'rollback' => 'रोलब्याक गर्नुहोस्',
            'skip' => 'छोड्नुहोस्',
            'pause' => 'रोक्नुहोस्',
            'resume' => 'पुनः सुरु गर्नुहोस्',
        ];

        return $types[$this->transition_type] ?? $this->transition_type;
    }

    /**
     * Get the remarks in the current locale.
     */
    public function getRemarksAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'np' ? $this->remarks_np : $this->remarks_en;
    }

    /**
     * Get the from step name in the current locale.
     */
    public function getFromStepNameAttribute(): ?string
    {
        if (!$this->fromStep) {
            return null;
        }

        $locale = app()->getLocale();
        return $locale === 'np' ? $this->fromStep->name_np : $this->fromStep->name_en;
    }

    /**
     * Get the to step name in the current locale.
     */
    public function getToStepNameAttribute(): ?string
    {
        if (!$this->toStep) {
            return null;
        }

        $locale = app()->getLocale();
        return $locale === 'np' ? $this->toStep->name_np : $this->toStep->name_en;
    }

    /**
     * Get the duration from previous transition in hours.
     */
    public function getDurationFromPrevious(): ?float
    {
        if (!$this->from_step_id) {
            return null;
        }

        $previousTransition = self::where('process_instance_id', $this->process_instance_id)
            ->where('to_step_id', $this->from_step_id)
            ->orderBy('transitioned_at', 'desc')
            ->first();

        if (!$previousTransition || !$previousTransition->transitioned_at) {
            return null;
        }

        return $this->transitioned_at->diffInHours($previousTransition->transitioned_at);
    }

    /**
     * Get the duration from previous transition in days.
     */
    public function getDurationFromPreviousInDays(): ?float
    {
        $hours = $this->getDurationFromPrevious();
        return $hours ? $hours / 24 : null;
    }

    /**
     * Check if this is the first transition.
     */
    public function isFirstTransition(): bool
    {
        return $this->transition_type === 'start';
    }

    /**
     * Check if this is a completion transition.
     */
    public function isCompletion(): bool
    {
        return $this->transition_type === 'complete';
    }

    /**
     * Check if this is a rollback transition.
     */
    public function isRollback(): bool
    {
        return $this->transition_type === 'rollback';
    }

    /**
     * Get step data value by key.
     */
    public function getStepDataValue(string $key, $default = null)
    {
        $stepData = $this->step_data ?? [];
        return $stepData[$key] ?? $default;
    }

    /**
     * Get all step data keys.
     */
    public function getStepDataKeys(): array
    {
        $stepData = $this->step_data ?? [];
        return array_keys($stepData);
    }

    /**
     * Check if step data contains a specific key.
     */
    public function hasStepDataKey(string $key): bool
    {
        $stepData = $this->step_data ?? [];
        return array_key_exists($key, $stepData);
    }
}
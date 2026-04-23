<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcessStep extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'process_type',
        'step_code',
        'name_np',
        'name_en',
        'description_np',
        'description_en',
        'sequence',
        'estimated_days',
        'is_initial',
        'is_final',
        'is_parallel',
        'is_mandatory',
        'condition',
        'required_permissions',
        'notification_template_np',
        'notification_template_en',
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
        'sequence' => 'integer',
        'estimated_days' => 'integer',
        'is_initial' => 'boolean',
        'is_final' => 'boolean',
        'is_parallel' => 'boolean',
        'is_mandatory' => 'boolean',
        'is_active' => 'boolean',
        'required_permissions' => 'array',
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
     * Get the next steps for this step.
     */
    public function nextSteps(): BelongsToMany
    {
        return $this->belongsToMany(
            ProcessStep::class,
            'process_step_transitions',
            'from_step_id',
            'to_step_id'
        )->withPivot(['condition', 'is_default'])->withTimestamps();
    }

    /**
     * Get the previous steps for this step.
     */
    public function previousSteps(): BelongsToMany
    {
        return $this->belongsToMany(
            ProcessStep::class,
            'process_step_transitions',
            'to_step_id',
            'from_step_id'
        )->withPivot(['condition', 'is_default'])->withTimestamps();
    }

    /**
     * Get the process instances for this step.
     */
    public function processInstances(): HasMany
    {
        return $this->hasMany(ProcessInstance::class, 'current_step_id');
    }

    /**
     * Get the transitions where this step is the from step.
     */
    public function fromTransitions(): HasMany
    {
        return $this->hasMany(ProcessTransition::class, 'from_step_id');
    }

    /**
     * Get the transitions where this step is the to step.
     */
    public function toTransitions(): HasMany
    {
        return $this->hasMany(ProcessTransition::class, 'to_step_id');
    }

    /**
     * Scope a query to only include steps for a specific process type.
     */
    public function scopeByProcessType($query, $processType)
    {
        return $query->where('process_type', $processType);
    }

    /**
     * Scope a query to only include active steps.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include initial steps.
     */
    public function scopeInitial($query)
    {
        return $query->where('is_initial', true);
    }

    /**
     * Scope a query to only include final steps.
     */
    public function scopeFinal($query)
    {
        return $query->where('is_final', true);
    }

    /**
     * Get the step name in the current locale.
     */
    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'np' ? $this->name_np : $this->name_en;
    }

    /**
     * Get the step description in the current locale.
     */
    public function getDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $locale === 'np' ? $this->description_np : $this->description_en;
    }

    /**
     * Get the notification template in the current locale.
     */
    public function getNotificationTemplateAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $locale === 'np' ? $this->notification_template_np : $this->notification_template_en;
    }

    /**
     * Check if this step is the initial step.
     */
    public function isInitialStep(): bool
    {
        return $this->is_initial;
    }

    /**
     * Check if this step is the final step.
     */
    public function isFinalStep(): bool
    {
        return $this->is_final;
    }

    /**
     * Check if this step can be executed in parallel.
     */
    public function canExecuteInParallel(): bool
    {
        return $this->is_parallel;
    }

    /**
     * Check if this step is mandatory.
     */
    public function isMandatory(): bool
    {
        return $this->is_mandatory;
    }

    /**
     * Get the estimated completion date from a start date.
     */
    public function getEstimatedCompletionDate(\DateTime $startDate): \DateTime
    {
        $completionDate = clone $startDate;
        if ($this->estimated_days) {
            $completionDate->add(new \DateInterval("P{$this->estimated_days}D"));
        }
        return $completionDate;
    }

    /**
     * Check if user has required permissions for this step.
     */
    public function userHasPermission(User $user): bool
    {
        $requiredPermissions = $this->required_permissions ?? [];
        
        if (empty($requiredPermissions)) {
            return true;
        }

        foreach ($requiredPermissions as $permission) {
            if (!$user->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'local_body_id',
        'fiscal_year_id',
        'ward_id',
        'budget_head_id',
        'project_code',
        'project_name_np',
        'project_name_en',
        'project_description_np',
        'project_description_en',
        'project_type',
        'location_np',
        'location_en',
        'latitude',
        'longitude',
        'estimated_cost',
        'approved_budget',
        'procurement_method',
        'priority_rank',
        'status',
        'current_step',
        'ward_meeting_date_bs',
        'executive_approval_date_bs',
        'assembly_approval_date_bs',
        'total_beneficiary_households',
        'total_beneficiary_population',
        'created_date_bs',
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
        'estimated_cost' => 'decimal:2',
        'approved_budget' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'priority_rank' => 'integer',
        'total_beneficiary_households' => 'integer',
        'total_beneficiary_population' => 'integer',
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
     * Get the local body that owns the project.
     */
    public function localBody(): BelongsTo
    {
        return $this->belongsTo(LocalBody::class);
    }

    /**
     * Get the fiscal year that owns the project.
     */
    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    /**
     * Get the ward that owns the project.
     */
    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    /**
     * Get the budget head that owns the project.
     */
    public function budgetHead(): BelongsTo
    {
        return $this->belongsTo(BudgetHead::class);
    }

    /**
     * Get the user who created the project.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the project.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the process instances for the project.
     */
    public function processInstances(): HasMany
    {
        return $this->hasMany(ProcessInstance::class);
    }

    /**
     * Get the active process instance for the project.
     */
    public function activeProcessInstance(): HasOne
    {
        return $this->hasOne(ProcessInstance::class)->where('current_status', 'in_progress');
    }

    /**
     * Get the cost estimates for the project.
     */
    public function costEstimates(): HasMany
    {
        return $this->hasMany(CostEstimate::class);
    }

    /**
     * Get the procurement plans for the project.
     */
    public function procurementPlans(): HasMany
    {
        return $this->hasMany(ProcurementPlan::class);
    }

    /**
     * Get the bids for the project.
     */
    public function bids(): HasMany
    {
        return $this->hasMany(Bid::class);
    }

    /**
     * Get the contracts for the project.
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    /**
     * Get the work executions for the project.
     */
    public function workExecutions(): HasMany
    {
        return $this->hasMany(WorkExecution::class);
    }

    /**
     * Get the bills for the project.
     */
    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    /**
     * Scope a query to only include active projects.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include projects by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include projects by local body.
     */
    public function scopeByLocalBody($query, $localBodyId)
    {
        return $query->where('local_body_id', $localBodyId);
    }

    /**
     * Scope a query to only include projects by fiscal year.
     */
    public function scopeByFiscalYear($query, $fiscalYearId)
    {
        return $query->where('fiscal_year_id', $fiscalYearId);
    }

    /**
     * Get the project name in the current locale.
     */
    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'np' ? $this->project_name_np : $this->project_name_en;
    }

    /**
     * Get the project description in the current locale.
     */
    public function getDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $locale === 'np' ? $this->project_description_np : $this->project_description_en;
    }

    /**
     * Get the location in the current locale.
     */
    public function getLocationAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $locale === 'np' ? $this->location_np : $this->location_en;
    }

    /**
     * Check if project is in draft status.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if project is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Check if project is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if project is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Get the current process step name.
     */
    public function getCurrentStepName(): ?string
    {
        if (!$this->current_step) {
            return null;
        }

        // This would typically come from a process steps configuration
        $steps = [
            'ward_meeting' => 'वडा भेला',
            'executive_approval' => 'कार्यपालिका स्वीकृति',
            'assembly_approval' => 'सभा स्वीकृति',
            'cost_estimate' => 'लागत आकलन',
            'procurement_plan' => 'खरिद योजना',
            'bid_invitation' => 'बोलपत्र आमन्त्रण',
            'bid_evaluation' => 'बोलपत्र मुल्याङ्कन',
            'contract_signing' => 'सम्झौता हस्ताक्षर',
            'work_execution' => 'कार्य कार्यान्वयन',
            'bill_payment' => 'बिल भुक्तानी',
            'completion' => 'पूर्णता',
        ];

        return $steps[$this->current_step] ?? $this->current_step;
    }
}
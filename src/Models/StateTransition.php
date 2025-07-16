<?php

declare(strict_types=1);

namespace Shavonn\StatusMachina\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $model_type
 * @property int $model_id
 * @property string $property
 * @property string|null $transition
 * @property string $from_state
 * @property string $to_state
 * @property array $context
 * @property array $metadata
 * @property string $ip_address
 * @property string $user_agent
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class StateTransition extends Model
{
    protected $fillable = [
        'model_type',
        'model_id',
        'property',
        'transition',
        'from_state',
        'to_state',
        'context',
        'transitioner_type',
        'transitioner_id',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'context' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // Set custom table name from config
        $this->setTable(config('status-machina.db_history_tracking.history_table_name', 'state_transitions'));
    }

    /**
     * Get the model that owns the state transition
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user/entity that made the transition
     */
    public function transitioner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope to filter by model
     */
    public function scopeForModel(Builder $query, Model $model): Builder
    {
        return $query->where([
            'model_type' => $model::class,
            'model_id' => $model->getKey(),
        ]);
    }

    /**
     * Scope to filter by property
     */
    public function scopeForProperty(Builder $query, string $property): Builder
    {
        return $query->where('property', $property);
    }

    /**
     * Scope to filter by transition name
     */
    public function scopeForTransition(Builder $query, string $transition): Builder
    {
        return $query->where('transition', $transition);
    }

    /**
     * Scope to filter by state
     */
    public function scopeFromState(Builder $query, string $state): Builder
    {
        return $query->where('from_state', $state);
    }

    /**
     * Scope to filter by state
     */
    public function scopeToState(Builder $query, string $state): Builder
    {
        return $query->where('to_state', $state);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeBetween(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    /**
     * Get a human-readable duration in the previous state
     */
    public function getDurationInPreviousStateForHumans(): ?string
    {
        $duration = $this->getDurationInPreviousState();

        if ($duration === null) {
            return null;
        }

        return now()->subSeconds($duration)->diffForHumans(now(), CarbonInterface::DIFF_ABSOLUTE);
    }

    /**
     * Get the duration in the previous state
     */
    public function getDurationInPreviousState(): ?float
    {
        $previousTransition = self::forModel($this->model)
            ->forProperty($this->property)
            ->where('created_at', '<', $this->created_at)
            ->latest()
            ->first();

        if (! $previousTransition) {
            return null;
        }

        return $this->created_at->diffInSeconds($previousTransition->created_at);
    }

    /**
     * Check if this transition happened within a certain time
     */
    public function happenedWithin(int $seconds): bool
    {
        return $this->created_at->diffInSeconds(now()) <= $seconds;
    }

    /**
     * Get context value by key
     */
    public function getContextValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->context, $key, $default);
    }

    /**
     * Get metadata value by key
     */
    public function getMetadataValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->metadata, $key, $default);
    }
}

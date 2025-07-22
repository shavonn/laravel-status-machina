<?php

declare(strict_types=1);

namespace SysMatter\StatusMachina\History;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use SysMatter\StatusMachina\Models\StateTransition;

class StateTransitionRepository
{
    public function __construct(
        protected StateTransition $model
    ) {
    }

    /**
     * Record a state transition
     */
    public function record(
        object $model,
        string $property,
        string $fromState,
        string $toState,
        ?string $transition = null,
        array $context = [],
        ?Model $transitioner = null,
        array $metadata = []
    ): StateTransition {
        return $this->model->create([
            'model_type' => $model::class,
            'model_id' => $model->getKey(),
            'property' => $property,
            'transition' => $transition,
            'from_state' => $fromState,
            'to_state' => $toState,
            'context' => $context,
            'transitioner_type' => $transitioner?->getMorphClass(),
            'transitioner_id' => $transitioner?->getKey(),
            'metadata' => array_merge($metadata, [
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]),
        ]);
    }

    /**
     * Get history for a model
     */
    public function getHistoryFor(Model $model, ?string $property = null): Collection
    {
        $query = $this->model->forModel($model);

        if ($property !== null) {
            $query->forProperty($property);
        }

        return $query->latest()->get();
    }

    /**
     * Get the last transition for a model
     */
    public function getLastTransition(Model $model, string $property): ?StateTransition
    {
        return $this->model
            ->forModel($model)
            ->forProperty($property)
            ->latest()
            ->first();
    }

    /**
     * Count transitions for a model
     */
    public function countTransitions(Model $model, ?string $property = null): int
    {
        $query = $this->model->forModel($model);

        if ($property !== null) {
            $query->forProperty($property);
        }

        return $query->count();
    }

    /**
     * Get state duration statistics
     */
    public function getStateDurations(Model $model, string $property): array
    {
        $transitions = $this->model
            ->forModel($model)
            ->forProperty($property)
            ->orderBy('created_at')
            ->get();

        $durations = [];

        for ($i = 0; $i < $transitions->count() - 1; $i++) {
            $current = $transitions[$i];
            $next = $transitions[$i + 1];

            $state = $current->to_state;
            $duration = $next->created_at->diffInSeconds($current->created_at);

            if (! isset($durations[$state])) {
                $durations[$state] = [
                    'total_seconds' => 0,
                    'count' => 0,
                    'transitions' => [],
                ];
            }

            $durations[$state]['total_seconds'] += $duration;
            $durations[$state]['count']++;
            $durations[$state]['transitions'][] = [
                'from' => $current->created_at,
                'to' => $next->created_at,
                'duration' => $duration,
            ];
        }

        // Calculate averages
        foreach ($durations as $state => &$data) {
            $data['average_seconds'] = $data['total_seconds'] / $data['count'];
            $data['average_human'] = now()->subSeconds((int) $data['average_seconds'])->diffForHumans(now(), CarbonInterface::DIFF_ABSOLUTE);
            $data['total_human'] = now()->subSeconds($data['total_seconds'])->diffForHumans(now(), CarbonInterface::DIFF_ABSOLUTE);
        }

        return $durations;
    }

    /**
     * Prune old transitions
     */
    public function prune(?int $days = null): int
    {
        $days ??= config('status-machina.max_history_retention');

        if ($days === null) {
            return 0;
        }

        $count = $this->model->newQuery()  // Explicitly use query builder
            ->where('created_at', '<', now()->subDays($days))
            ->delete();

        // Ensure we always return an integer
        if (is_bool($count)) {
            return 0;
        }

        return $count;
    }

    /**
     * Get transition frequency
     */
    public function getTransitionFrequency(
        Model $model,
        string $property,
        Carbon $from,
        Carbon $to
    ): array {
        return $this->model
            ->forModel($model)
            ->forProperty($property)
            ->between($from, $to)
            ->get()
            ->groupBy('transition')
            ->map(fn ($group) => $group->count())
            ->toArray();
    }
}

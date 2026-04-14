<?php

namespace App\Repositories\Patient\Eloquent;

use App\Models\HealthHistory;
use App\Repositories\Base\EloquentBaseRepository;
use App\Repositories\Patient\Contracts\HealthHistoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class HealthHistoryRepository extends EloquentBaseRepository implements HealthHistoryRepositoryInterface
{
    public function __construct(HealthHistory $model)
    {
        parent::__construct($model);
    }

    public function getByPatient(int $patientId): Collection
    {
        return $this->query()
            ->where('patient_id', $patientId)
            ->orderByDesc('diagnosed_at')
            ->orderByDesc('id')
            ->get();
    }

    public function createForPatient(int $patientId, array $data): HealthHistory
    {
        return $this->create([
            'health_history_id' => 'HLT-'.Str::upper((string) Str::ulid()),
            ...$data,
            'patient_id' => $patientId,
        ]);
    }

    public function updateForPatient(int $patientId, string $externalHistoryId, array $data): ?HealthHistory
    {
        /** @var HealthHistory|null $history */
        $history = $this->query()
            ->where('patient_id', $patientId)
            ->where('health_history_id', $externalHistoryId)
            ->first();

        if (! $history) {
            return null;
        }

        $history->fill($data)->save();

        return $history->refresh();
    }

    public function deleteForPatient(int $patientId, string $externalHistoryId): bool
    {
        return (bool) $this->query()
            ->where('patient_id', $patientId)
            ->where('health_history_id', $externalHistoryId)
            ->delete();
    }
}

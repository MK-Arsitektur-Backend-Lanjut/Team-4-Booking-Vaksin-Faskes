<?php

namespace App\Repositories\Patient\Eloquent;

use App\Models\VaccinationHistory;
use App\Repositories\Base\EloquentBaseRepository;
use App\Repositories\Patient\Contracts\VaccinationHistoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class VaccinationHistoryRepository extends EloquentBaseRepository implements VaccinationHistoryRepositoryInterface
{
    public function __construct(VaccinationHistory $model)
    {
        parent::__construct($model);
    }

    public function getByPatient(int $patientId): Collection
    {
        return $this->query()
            ->where('patient_id', $patientId)
            ->orderByDesc('vaccinated_at')
            ->orderByDesc('id')
            ->get();
    }

    public function createForPatient(int $patientId, array $data): VaccinationHistory
    {
        return $this->create([
            'vaccination_history_id' => 'VAC-'.Str::upper((string) Str::ulid()),
            ...$data,
            'patient_id' => $patientId,
        ]);
    }

    public function updateForPatient(int $patientId, string $externalHistoryId, array $data): ?VaccinationHistory
    {
        /** @var VaccinationHistory|null $history */
        $history = $this->query()
            ->where('patient_id', $patientId)
            ->where('vaccination_history_id', $externalHistoryId)
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
            ->where('vaccination_history_id', $externalHistoryId)
            ->delete();
    }
}

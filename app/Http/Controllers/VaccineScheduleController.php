<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\VaccineScheduleRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VaccineScheduleController extends Controller
{
    public function __construct(private VaccineScheduleRepositoryInterface $repository)
    {
    }

    /**
     * Get all vaccine schedules
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->query('per_page', 15);
        $result = $this->repository->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $result->items(),
            'pagination' => [
                'total' => $result->total(),
                'per_page' => $result->perPage(),
                'current_page' => $result->currentPage(),
                'last_page' => $result->lastPage(),
            ],
        ]);
    }

    /**
     * Get vaccine schedule by ID
     */
    public function show(int $id): JsonResponse
    {
        $schedule = $this->repository->find($id);

        if (!$schedule) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vaccine schedule not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $schedule,
        ]);
    }

    /**
     * Create new vaccine schedule
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'health_center_id' => 'required|integer|exists:health_centers,id',
            'vaccine_id' => 'required|integer|exists:vaccines,id',
            'schedule_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'quota' => 'required|integer|min:1',
            'notes' => 'nullable|string',
            'status' => 'in:scheduled,ongoing,completed,cancelled',
        ]);

        $validated['available_quota'] = $validated['quota'];

        $schedule = $this->repository->create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Vaccine schedule created successfully',
            'data' => $schedule,
        ], 201);
    }

    /**
     * Update vaccine schedule
     */
    public function update(Request $request, int $id): JsonResponse
    {
        if (!$this->repository->find($id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vaccine schedule not found',
            ], 404);
        }

        $validated = $request->validate([
            'schedule_date' => 'date',
            'start_time' => 'date_format:H:i',
            'end_time' => 'date_format:H:i',
            'quota' => 'integer|min:1',
            'notes' => 'nullable|string',
            'status' => 'in:scheduled,ongoing,completed,cancelled',
        ]);

        $this->repository->update($id, $validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Vaccine schedule updated successfully',
            'data' => $this->repository->find($id),
        ]);
    }

    /**
     * Delete vaccine schedule
     */
    public function destroy(int $id): JsonResponse
    {
        if (!$this->repository->find($id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vaccine schedule not found',
            ], 404);
        }

        $this->repository->delete($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Vaccine schedule deleted successfully',
        ]);
    }

    /**
     * Get schedules by health center
     */
    public function getByHealthCenter(int $healthCenterId): JsonResponse
    {
        $schedules = $this->repository->getByHealthCenter($healthCenterId);

        return response()->json([
            'status' => 'success',
            'health_center_id' => $healthCenterId,
            'data' => $schedules,
        ]);
    }

    /**
     * Get schedules by date
     */
    public function getByDate(Request $request): JsonResponse
    {
        $date = $request->query('date');

        if (!$date) {
            return response()->json([
                'status' => 'error',
                'message' => 'Date parameter is required',
            ], 422);
        }

        $schedules = $this->repository->getByDate($date);

        return response()->json([
            'status' => 'success',
            'date' => $date,
            'data' => $schedules,
        ]);
    }

    /**
     * Get available schedules
     */
    public function getAvailable(): JsonResponse
    {
        $schedules = $this->repository->getAvailable();

        return response()->json([
            'status' => 'success',
            'data' => $schedules,
        ]);
    }

    /**
     * Get schedules by health center and date range
     */
    public function getByDateRange(Request $request): JsonResponse
    {
        $healthCenterId = $request->query('health_center_id');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        if (!$healthCenterId || !$startDate || !$endDate) {
            return response()->json([
                'status' => 'error',
                'message' => 'health_center_id, start_date, and end_date are required',
            ], 422);
        }

        $schedules = $this->repository->getByHealthCenterAndDateRange($healthCenterId, $startDate, $endDate);

        return response()->json([
            'status' => 'success',
            'health_center_id' => $healthCenterId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'data' => $schedules,
        ]);
    }
}

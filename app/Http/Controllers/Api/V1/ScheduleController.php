<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Repositories\BookingRepositoryInterface;
use App\Services\ScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function __construct(
        protected ScheduleService $scheduleService,
        protected BookingRepositoryInterface $bookingRepository,
    ) {}

    /**
     * List available schedules, filterable by date and health_center_id.
     *
     * GET /api/v1/schedules?date=2026-04-20&health_center_id=1
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'health_center_id' => ['nullable', 'integer', 'exists:health_centers,id'],
        ]);

        $schedules = $this->scheduleService->getAvailableSchedules(
            $request->input('date'),
            $request->input('health_center_id') ? (int) $request->input('health_center_id') : null,
        );

        return response()->json([
            'success' => true,
            'message' => 'Schedules retrieved successfully.',
            'data' => $schedules,
        ]);
    }

    /**
     * Get schedule detail with quota info.
     *
     * GET /api/v1/schedules/{id}
     */
    public function show(int $id): JsonResponse
    {
        $schedule = $this->scheduleService->getScheduleDetail($id);

        return response()->json([
            'success' => true,
            'message' => 'Schedule retrieved successfully.',
            'data' => $schedule,
        ]);
    }

    /**
     * Get real-time quota report for a schedule.
     *
     * GET /api/v1/schedules/{id}/quota
     */
    public function quota(int $id): JsonResponse
    {
        $quotaReport = $this->bookingRepository->getQuotaUsage($id);

        return response()->json([
            'success' => true,
            'message' => 'Quota report retrieved successfully.',
            'data' => $quotaReport,
        ]);
    }
}

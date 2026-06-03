<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\HealthCenterRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HealthCenterController extends Controller
{
    public function __construct(private HealthCenterRepositoryInterface $repository)
    {
    }

    /**
     * Get all health centers
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
     * Get health center by ID with relations
     */
    public function show(int $id): JsonResponse
    {
        $healthCenter = $this->repository->find($id);

        if (!$healthCenter) {
            return response()->json([
                'status' => 'error',
                'message' => 'Health center not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $healthCenter,
        ]);
    }

    /**
     * Create new health center
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:health_centers,code|max:50',
            'address' => 'required|string',
            'province' => 'required|string',
            'city' => 'required|string',
            'district' => 'required|string',
            'village' => 'required|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'phone' => 'nullable|string|max:20',
            'capacity' => 'required|integer|min:1',
            'status' => 'in:active,inactive',
        ]);

        $healthCenter = $this->repository->create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Health center created successfully',
            'data' => $healthCenter,
        ], 201);
    }

    /**
     * Update health center
     */
    public function update(Request $request, int $id): JsonResponse
    {
        if (!$this->repository->find($id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Health center not found',
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'string|max:255',
            'code' => 'string|unique:health_centers,code,' . $id . '|max:50',
            'address' => 'string',
            'province' => 'string',
            'city' => 'string',
            'district' => 'string',
            'village' => 'string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'phone' => 'nullable|string|max:20',
            'capacity' => 'integer|min:1',
            'status' => 'in:active,inactive',
        ]);

        $this->repository->update($id, $validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Health center updated successfully',
            'data' => $this->repository->find($id),
        ]);
    }

    /**
     * Delete health center
     */
    public function destroy(int $id): JsonResponse
    {
        if (!$this->repository->find($id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Health center not found',
            ], 404);
        }

        $this->repository->delete($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Health center deleted successfully',
        ]);
    }

    /**
     * Get health centers by province
     */
    public function getByProvince(string $province): JsonResponse
    {
        $healthCenters = $this->repository->getByProvince($province);

        return response()->json([
            'status' => 'success',
            'province' => $province,
            'data' => $healthCenters,
        ]);
    }

    /**
     * Get health centers by city
     */
    public function getByCity(string $city): JsonResponse
    {
        $healthCenters = $this->repository->getByCity($city);

        return response()->json([
            'status' => 'success',
            'city' => $city,
            'data' => $healthCenters,
        ]);
    }

    /**
     * Search health centers
     */
    public function search(Request $request): JsonResponse
    {
        $q = $request->query('q', '');

        if (strlen($q) < 2) {
            return response()->json([
                'status' => 'error',
                'message' => 'Search query must be at least 2 characters',
            ], 422);
        }

        $healthCenters = $this->repository->search($q);

        return response()->json([
            'status' => 'success',
            'query' => $q,
            'results' => count($healthCenters),
            'data' => $healthCenters,
        ]);
    }

    /**
     * Get active health centers
     */
    public function getActive(): JsonResponse
    {
        $healthCenters = $this->repository->getActive();

        return response()->json([
            'status' => 'success',
            'data' => $healthCenters,
        ]);
    }
}

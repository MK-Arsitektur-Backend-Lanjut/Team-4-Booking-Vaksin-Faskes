<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\VaccineRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VaccineController extends Controller
{
    public function __construct(private VaccineRepositoryInterface $repository)
    {
    }

    /**
     * Get all vaccines
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
     * Get vaccine by ID
     */
    public function show(int $id): JsonResponse
    {
        $vaccine = $this->repository->find($id);

        if (!$vaccine) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vaccine not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $vaccine,
        ]);
    }

    /**
     * Create new vaccine
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:vaccines,code|max:50',
            'description' => 'nullable|string',
            'doses_required' => 'required|integer|min:1',
            'days_between_doses' => 'nullable|integer|min:0',
            'manufacturer' => 'nullable|string|max:255',
            'status' => 'in:active,inactive',
        ]);

        $vaccine = $this->repository->create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Vaccine created successfully',
            'data' => $vaccine,
        ], 201);
    }

    /**
     * Update vaccine
     */
    public function update(Request $request, int $id): JsonResponse
    {
        if (!$this->repository->find($id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vaccine not found',
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'string|max:255',
            'code' => 'string|unique:vaccines,code,' . $id . '|max:50',
            'description' => 'nullable|string',
            'doses_required' => 'integer|min:1',
            'days_between_doses' => 'nullable|integer|min:0',
            'manufacturer' => 'nullable|string|max:255',
            'status' => 'in:active,inactive',
        ]);

        $this->repository->update($id, $validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Vaccine updated successfully',
            'data' => $this->repository->find($id),
        ]);
    }

    /**
     * Delete vaccine
     */
    public function destroy(int $id): JsonResponse
    {
        if (!$this->repository->find($id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vaccine not found',
            ], 404);
        }

        $this->repository->delete($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Vaccine deleted successfully',
        ]);
    }

    /**
     * Get active vaccines
     */
    public function getActive(): JsonResponse
    {
        $vaccines = $this->repository->getActive();

        return response()->json([
            'status' => 'success',
            'data' => $vaccines,
        ]);
    }

    /**
     * Search vaccines
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

        $vaccines = $this->repository->search($q);

        return response()->json([
            'status' => 'success',
            'query' => $q,
            'results' => count($vaccines),
            'data' => $vaccines,
        ]);
    }
}

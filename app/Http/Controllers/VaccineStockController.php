<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\VaccineStockRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VaccineStockController extends Controller
{
    public function __construct(private VaccineStockRepositoryInterface $repository)
    {
    }

    /**
     * Get all vaccine stocks
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
     * Get vaccine stock by ID
     */
    public function show(int $id): JsonResponse
    {
        $stock = $this->repository->find($id);

        if (!$stock) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vaccine stock not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $stock,
        ]);
    }

    /**
     * Create new vaccine stock
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'health_center_id' => 'required|integer|exists:health_centers,id',
            'vaccine_id' => 'required|integer|exists:vaccines,id',
            'total_stock' => 'required|integer|min:1',
            'available_stock' => 'required|integer|min:0',
            'expiration_date' => 'required|date',
        ]);

        // Validate available_stock <= total_stock
        if ($validated['available_stock'] > $validated['total_stock']) {
            return response()->json([
                'status' => 'error',
                'message' => 'Available stock cannot exceed total stock',
            ], 422);
        }

        $stock = $this->repository->create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Vaccine stock created successfully',
            'data' => $stock,
        ], 201);
    }

    /**
     * Update vaccine stock
     */
    public function update(Request $request, int $id): JsonResponse
    {
        if (!$this->repository->find($id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vaccine stock not found',
            ], 404);
        }

        $validated = $request->validate([
            'total_stock' => 'integer|min:1',
            'available_stock' => 'integer|min:0',
            'expiration_date' => 'date',
        ]);

        $stock = $this->repository->find($id);

        // Validate available_stock <= total_stock
        if (isset($validated['available_stock']) && isset($validated['total_stock'])) {
            if ($validated['available_stock'] > $validated['total_stock']) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Available stock cannot exceed total stock',
                ], 422);
            }
        }

        $this->repository->update($id, $validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Vaccine stock updated successfully',
            'data' => $this->repository->find($id),
        ]);
    }

    /**
     * Delete vaccine stock
     */
    public function destroy(int $id): JsonResponse
    {
        if (!$this->repository->find($id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vaccine stock not found',
            ], 404);
        }

        $this->repository->delete($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Vaccine stock deleted successfully',
        ]);
    }

    /**
     * Get vaccine stocks by health center
     */
    public function getByHealthCenter(int $healthCenterId): JsonResponse
    {
        $stocks = $this->repository->getByHealthCenter($healthCenterId);

        return response()->json([
            'status' => 'success',
            'health_center_id' => $healthCenterId,
            'data' => $stocks,
        ]);
    }

    /**
     * Get available vaccine stocks
     */
    public function getAvailable(): JsonResponse
    {
        $stocks = $this->repository->getAvailable();

        return response()->json([
            'status' => 'success',
            'data' => $stocks,
        ]);
    }
}

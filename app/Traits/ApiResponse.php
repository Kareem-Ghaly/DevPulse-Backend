<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function successResponse(mixed $data = null, string $message = 'Operation completed successfully', int $code = 200): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    protected function errorResponse(string $message = 'Error message', mixed $errors = null, int $code = 400): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }

    protected function paginatedResponse(mixed $resourceCollection, mixed $paginatedData, string $message = 'Data retrieved successfully'): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $resourceCollection,
            'meta' => [
                'current_page' => $paginatedData->currentPage(),
                'last_page' => $paginatedData->lastPage(),
                'per_page' => $paginatedData->perPage(),
                'total' => $paginatedData->total(),
            ],
        ]);
    }
}

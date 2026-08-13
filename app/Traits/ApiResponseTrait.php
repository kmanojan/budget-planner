<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

trait ApiResponseTrait
{
    protected function success($data = null, string $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    protected function error(string $message = 'Error', int $code = 400, $errors = null): JsonResponse
    {
        $response = [
            'status' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    protected function created($data = null, string $message = 'Created'): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data,
        ], 201);
    }

    protected function noContent(string $message = 'Deleted'): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => $message,
        ], 200);
    }

    protected function paginated($data, string $message = 'Success'): JsonResponse
    {
        if ($data instanceof ResourceCollection) {
            $paginated = $data->response()->getData(true);
        } else {
            $paginated = $data->toArray();
        }

        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $paginated['data'] ?? $paginated,
            'meta' => [
                'current_page' => $paginated['meta']['current_page'] ?? $paginated['current_page'] ?? null,
                'last_page' => $paginated['meta']['last_page'] ?? $paginated['last_page'] ?? null,
                'per_page' => $paginated['meta']['per_page'] ?? $paginated['per_page'] ?? null,
                'total' => $paginated['meta']['total'] ?? $paginated['total'] ?? null,
            ],
        ], 200);
    }
}

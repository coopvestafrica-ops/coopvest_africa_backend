<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    /**
     * Success response
     *
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    public static function success($data = null, string $message = "Success", int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    /**
     * Error response
     *
     * @param string $message
     * @param string $error
     * @param int $statusCode
     * @param array $details
     * @return JsonResponse
     */
    public static function error(string $message, string $error = "ERROR", int $statusCode = 400, array $details = []): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
            'error' => $error,
        ];

        if (!empty($details)) {
            $response['details'] = $details;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Validation error response
     *
     * @param array $errors
     * @param string $message
     * @return JsonResponse
     */
    public static function validationError(array $errors, string $message = "Validation failed"): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => 'VALIDATION_ERROR',
            'errors' => $errors,
        ], 422);
    }

    /**
     * Unauthorized response
     *
     * @param string $message
     * @return JsonResponse
     */
    public static function unauthorized(string $message = "Unauthorized"): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => 'UNAUTHORIZED',
        ], 401);
    }

    /**
     * Forbidden response
     *
     * @param string $message
     * @return JsonResponse
     */
    public static function forbidden(string $message = "Forbidden"): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => 'FORBIDDEN',
        ], 403);
    }

    /**
     * Not found response
     *
     * @param string $message
     * @return JsonResponse
     */
    public static function notFound(string $message = "Resource not found"): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => 'NOT_FOUND',
        ], 404);
    }

    /**
     * Server error response
     *
     * @param string $message
     * @param string $error
     * @return JsonResponse
     */
    public static function serverError(string $message = "Internal server error", string $error = "SERVER_ERROR"): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => $error,
        ], 500);
    }

    /**
     * Paginated response
     *
     * @param mixed $data
     * @param int $total
     * @param int $perPage
     * @param int $currentPage
     * @param string $message
     * @return JsonResponse
     */
    public static function paginated($data, int $total, int $perPage, int $currentPage, string $message = "Success"): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $currentPage,
                'last_page' => ceil($total / $perPage),
            ],
        ], 200);
    }
}

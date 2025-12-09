<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;
use Illuminate\Support\Facades\Log;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  Request  $request
     * @param  Throwable  $exception
     * @return JsonResponse|\Illuminate\Http\Response
     */
    public function render($request, Throwable $exception)
    {
        // Handle custom exceptions
        if ($exception instanceof FirebaseException) {
            return $this->handleFirebaseException($exception);
        }

        if ($exception instanceof TokenVerificationException) {
            return $this->handleTokenVerificationException($exception);
        }

        if ($exception instanceof UserSyncException) {
            return $this->handleUserSyncException($exception);
        }

        // Handle validation exceptions
        if ($exception instanceof ValidationException) {
            return $this->handleValidationException($exception);
        }

        // Handle HTTP exceptions
        if ($exception instanceof HttpException) {
            return $this->handleHttpException($exception);
        }

        // Handle generic exceptions
        return $this->handleGenericException($exception, $request);
    }

    /**
     * Handle Firebase exceptions
     *
     * @param FirebaseException $exception
     * @return JsonResponse
     */
    protected function handleFirebaseException(FirebaseException $exception): JsonResponse
    {
        Log::error('Firebase Exception', [
            'message' => $exception->getMessage(),
            'error_code' => $exception->getErrorCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);

        return response()->json([
            'success' => false,
            'message' => $exception->getMessage(),
            'error' => $exception->getErrorCode(),
        ], 500);
    }

    /**
     * Handle token verification exceptions
     *
     * @param TokenVerificationException $exception
     * @return JsonResponse
     */
    protected function handleTokenVerificationException(TokenVerificationException $exception): JsonResponse
    {
        Log::warning('Token Verification Exception', [
            'message' => $exception->getMessage(),
            'error_code' => $exception->getErrorCode(),
        ]);

        return response()->json([
            'success' => false,
            'message' => $exception->getMessage(),
            'error' => $exception->getErrorCode(),
        ], 401);
    }

    /**
     * Handle user sync exceptions
     *
     * @param UserSyncException $exception
     * @return JsonResponse
     */
    protected function handleUserSyncException(UserSyncException $exception): JsonResponse
    {
        Log::warning('User Sync Exception', [
            'message' => $exception->getMessage(),
            'error_code' => $exception->getErrorCode(),
        ]);

        return response()->json([
            'success' => false,
            'message' => $exception->getMessage(),
            'error' => $exception->getErrorCode(),
        ], 422);
    }

    /**
     * Handle validation exceptions
     *
     * @param ValidationException $exception
     * @return JsonResponse
     */
    protected function handleValidationException(ValidationException $exception): JsonResponse
    {
        Log::warning('Validation Exception', [
            'errors' => $exception->errors(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'error' => 'VALIDATION_ERROR',
            'errors' => $exception->errors(),
        ], 422);
    }

    /**
     * Handle HTTP exceptions
     *
     * @param HttpException $exception
     * @return JsonResponse
     */
    protected function handleHttpException(HttpException $exception): JsonResponse
    {
        $statusCode = $exception->getStatusCode();
        
        $errorMessages = [
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
        ];

        $message = $errorMessages[$statusCode] ?? 'HTTP Error';

        Log::warning('HTTP Exception', [
            'status_code' => $statusCode,
            'message' => $exception->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => $exception->getMessage() ?: $message,
            'error' => 'HTTP_ERROR_' . $statusCode,
        ], $statusCode);
    }

    /**
     * Handle generic exceptions
     *
     * @param Throwable $exception
     * @param Request $request
     * @return JsonResponse
     */
    protected function handleGenericException(Throwable $exception, Request $request): JsonResponse
    {
        Log::error('Unhandled Exception', [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Return different response based on environment
        if (config('app.debug')) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'error' => 'INTERNAL_SERVER_ERROR',
                'debug' => [
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => $exception->getTraceAsString(),
                ],
            ], 500);
        }

        return response()->json([
            'success' => false,
            'message' => 'An error occurred while processing your request',
            'error' => 'INTERNAL_SERVER_ERROR',
        ], 500);
    }
}

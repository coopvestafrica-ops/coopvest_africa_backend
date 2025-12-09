<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\TokenVerificationService;
use Illuminate\Support\Facades\Log;

class FirebaseAuth
{
    protected $tokenVerificationService;

    public function __construct(TokenVerificationService $tokenVerificationService)
    {
        $this->tokenVerificationService = $tokenVerificationService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip middleware if not enabled
        if (!config('firebase.middleware.auth.enabled')) {
            return $next($request);
        }

        // Get the authorization header
        $authHeader = $request->header('Authorization');

        if (!$authHeader) {
            return $this->unauthorizedResponse('Authorization header missing', 'MISSING_AUTH_HEADER');
        }

        // Extract the token from "Bearer <token>"
        $parts = explode(' ', $authHeader);
        if (count($parts) !== 2 || $parts[0] !== 'Bearer') {
            return $this->unauthorizedResponse('Invalid authorization header format', 'INVALID_AUTH_FORMAT');
        }

        $token = $parts[1];

        // Validate token format
        if (!$this->tokenVerificationService->isValidTokenFormat($token)) {
            return $this->unauthorizedResponse('Invalid token format', 'INVALID_TOKEN_FORMAT');
        }

        try {
            // Verify the token
            $verificationResult = $this->tokenVerificationService->verifyToken($token);

            if (!$verificationResult) {
                return $this->unauthorizedResponse('Invalid or expired token', 'INVALID_TOKEN');
            }

            // Store the verification result in the request
            $request->attributes->set('firebase_uid', $verificationResult['uid']);
            $request->attributes->set('firebase_email', $verificationResult['email']);
            $request->attributes->set('firebase_token', $verificationResult['token']);
            $request->attributes->set('authenticated_user', $verificationResult['user']);

            Log::info('Firebase token verified', [
                'uid' => $verificationResult['uid'],
                'user_id' => $verificationResult['user']->id,
            ]);

            return $next($request);
        } catch (\Exception $e) {
            Log::error('Firebase auth middleware error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            if (config('firebase.middleware.auth.throw_exceptions')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication service error',
                    'error' => 'AUTH_ERROR',
                ], 500);
            }

            return $next($request);
        }
    }

    /**
     * Return unauthorized response
     *
     * @param string $message
     * @param string $error
     * @return Response
     */
    protected function unauthorizedResponse(string $message, string $error): Response
    {
        Log::warning('Unauthorized request', [
            'message' => $message,
            'error' => $error,
        ]);

        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => $error,
        ], 401);
    }
}

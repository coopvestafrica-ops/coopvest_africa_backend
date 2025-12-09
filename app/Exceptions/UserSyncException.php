<?php

namespace App\Exceptions;

use Exception;

class UserSyncException extends Exception
{
    protected $errorCode;

    public function __construct(string $message = "", string $errorCode = "USER_SYNC_FAILED", int $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function render()
    {
        return response()->json([
            'success' => false,
            'message' => $this->message,
            'error' => $this->errorCode,
        ], 422);
    }
}

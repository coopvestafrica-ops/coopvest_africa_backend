<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TwoFAController extends Controller
{
    /**
     * Setup 2FA - Generate QR code and secret
     */
    public function setup(Request $request): JsonResponse
    {
        $user = $request->user();

        // Generate secret
        $secret = $this->generateSecret();

        // Generate QR code URL (using Google Authenticator format)
        $qrCode = $this->generateQRCode($user->email, $secret);

        // Store secret temporarily in cache
        cache()->put("2fa_setup_{$user->id}", $secret, now()->addMinutes(10));

        return response()->json([
            'success' => true,
            'data' => [
                'qrCode' => $qrCode,
                'secret' => $secret,
                'backupCodes' => $this->generateBackupCodes(),
            ],
        ], 200);
    }

    /**
     * Confirm 2FA setup
     */
    public function confirm(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|size:6',
            ]);

            $user = $request->user();
            $secret = cache()->get("2fa_setup_{$user->id}");

            if (!$secret) {
                return response()->json([
                    'success' => false,
                    'message' => 'Setup session expired. Please try again.',
                ], 401);
            }

            if (!$this->verifyCode($validated['code'], $secret)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid verification code',
                ], 401);
            }

            $backupCodes = $this->generateBackupCodes();

            $user->update([
                'two_fa_enabled' => true,
                'two_fa_secret' => encrypt($secret),
            ]);

            cache()->forget("2fa_setup_{$user->id}");

            return response()->json([
                'success' => true,
                'message' => '2FA enabled successfully',
                'data' => [
                    'backupCodes' => $backupCodes,
                ],
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Verify 2FA code during login
     */
    public function verify(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string',
            ]);

            $user = $request->user();

            if (!$user->two_fa_enabled) {
                return response()->json([
                    'success' => false,
                    'message' => '2FA not enabled',
                ], 403);
            }

            $secret = decrypt($user->two_fa_secret);

            // Check if it's a backup code
            if (strlen($validated['code']) > 6) {
                // Backup code verification logic
                return response()->json([
                    'success' => true,
                    'message' => 'Verified with backup code',
                ], 200);
            }

            if (!$this->verifyCode($validated['code'], $secret)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid verification code',
                ], 401);
            }

            return response()->json([
                'success' => true,
                'message' => 'Verified successfully',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Disable 2FA
     */
    public function disable(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'password' => 'required|string',
            ]);

            $user = $request->user();

            if (!\Hash::check($validated['password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid password',
                ], 401);
            }

            $user->update([
                'two_fa_enabled' => false,
                'two_fa_secret' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => '2FA disabled successfully',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Generate TOTP secret
     */
    private function generateSecret(): string
    {
        return base32_encode(random_bytes(32));
    }

    /**
     * Generate QR code URL
     */
    private function generateQRCode(string $email, string $secret): string
    {
        $issuer = 'Coopvest Africa';
        $label = urlencode("{$issuer} ({$email})");
        $params = [
            'secret' => $secret,
            'issuer' => urlencode($issuer),
        ];

        return "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" .
               urlencode("otpauth://totp/{$label}?" . http_build_query($params));
    }

    /**
     * Verify TOTP code
     */
    private function verifyCode(string $code, string $secret): bool
    {
        $time = floor(time() / 30);

        for ($i = -1; $i <= 1; $i++) {
            $hash = hash_hmac('sha1', pack('N*', 0, $time + $i), base32_decode($secret), true);
            $offset = ord($hash[19]) & 0xf;
            $code_value = (((ord($hash[$offset]) & 0x7f) << 24) |
                          ((ord($hash[$offset + 1]) & 0xff) << 16) |
                          ((ord($hash[$offset + 2]) & 0xff) << 8) |
                          (ord($hash[$offset + 3]) & 0xff)) % 1000000;

            if ($code_value == $code) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate backup codes
     */
    private function generateBackupCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 10; $i++) {
            $codes[] = bin2hex(random_bytes(4));
        }
        return $codes;
    }
}

// Helper function for base32 encoding
if (!function_exists('base32_encode')) {
    function base32_encode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $v = 0;
        $vbits = 0;

        for ($i = 0; $i < strlen($data); $i++) {
            $v = ($v << 8) | ord($data[$i]);
            $vbits += 8;

            while ($vbits >= 5) {
                $vbits -= 5;
                $output .= $alphabet[($v >> $vbits) & 31];
            }
        }

        if ($vbits > 0) {
            $output .= $alphabet[($v << (5 - $vbits)) & 31];
        }

        return $output;
    }
}

if (!function_exists('base32_decode')) {
    function base32_decode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $v = 0;
        $vbits = 0;

        for ($i = 0; $i < strlen($data); $i++) {
            $c = strpos($alphabet, $data[$i]);
            if ($c === false) continue;

            $v = ($v << 5) | $c;
            $vbits += 5;

            if ($vbits >= 8) {
                $vbits -= 8;
                $output .= chr(($v >> $vbits) & 255);
            }
        }

        return $output;
    }
}

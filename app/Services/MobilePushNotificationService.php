<?php

namespace App\Services;

use App\Models\MobileDeviceToken;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MobilePushNotificationService
{
    public function sendToUsers($users, string $title, string $body, array $data = []): array
    {
        $userIds = collect($users)
            ->filter()
            ->map(fn ($user) => $user instanceof User ? $user->id : (int) $user)
            ->filter()
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return $this->emptySummary('No users matched this notification.');
        }

        $tokens = MobileDeviceToken::whereIn('user_id', $userIds)->get();

        return $this->sendToTokens($tokens, $title, $body, $data);
    }

    public function sendToTokens($tokens, string $title, string $body, array $data = []): array
    {
        $tokens = collect($tokens)->filter();

        if ($tokens->isEmpty()) {
            return $this->emptySummary('No mobile device tokens found.');
        }

        if (!$this->isConfigured()) {
            Log::info('Firebase push skipped because Firebase is not configured.', [
                'token_count' => $tokens->count(),
                'title' => $title,
            ]);

            return $this->emptySummary('Firebase is not configured yet.', $tokens->count());
        }

        $accessToken = $this->accessToken();

        if (!$accessToken) {
            return $this->emptySummary('Firebase access token could not be generated.', $tokens->count());
        }

        $sent = 0;
        $failed = 0;

        foreach ($tokens as $tokenModel) {
            try {
                $response = Http::withToken($accessToken)
                    ->acceptJson()
                    ->post($this->messagesEndpoint(), [
                        'message' => [
                            'token' => $tokenModel->token,
                            'notification' => [
                                'title' => $title,
                                'body' => $body,
                            ],
                            'data' => $this->stringData($data),
                            'android' => [
                                'priority' => 'high',
                                'notification' => [
                                    'channel_id' => 'planning_services',
                                    'icon' => 'notification_icon',
                                    'color' => '#c1121f',
                                    'sound' => 'default',
                                ],
                            ],
                            'apns' => [
                                'payload' => [
                                    'aps' => [
                                        'sound' => 'default',
                                    ],
                                ],
                            ],
                        ],
                    ]);

                if ($response->successful()) {
                    $sent++;
                    $tokenModel->forceFill(['last_used_at' => now()])->save();
                    continue;
                }

                $failed++;
                $this->handleFailedToken($tokenModel, $response->json(), $response->status());
            } catch (\Throwable $exception) {
                $failed++;
                Log::warning('Mobile push notification failed.', [
                    'device_token_id' => $tokenModel->id ?? null,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return [
            'tokens' => $tokens->count(),
            'sent' => $sent,
            'failed' => $failed,
        ];
    }

    private function accessToken(): ?string
    {
        return Cache::remember('firebase_fcm_access_token', 3300, function () {
            $serviceAccount = $this->serviceAccount();

            if (!$serviceAccount) {
                return null;
            }

            try {
                $now = time();
                $jwt = $this->signedJwt([
                    'iss' => $serviceAccount['client_email'],
                    'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                    'aud' => 'https://oauth2.googleapis.com/token',
                    'iat' => $now,
                    'exp' => $now + 3600,
                ], $serviceAccount['private_key']);

                $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ]);

                if (!$response->successful()) {
                    Log::warning('Firebase OAuth token request failed.', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);

                    return null;
                }

                return $response->json('access_token');
            } catch (\Throwable $exception) {
                Log::warning('Firebase OAuth token generation failed.', [
                    'error' => $exception->getMessage(),
                ]);

                return null;
            }
        });
    }

    private function signedJwt(array $claims, string $privateKey): string
    {
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $segments = [
            $this->base64UrlEncode(json_encode($header)),
            $this->base64UrlEncode(json_encode($claims)),
        ];

        $input = implode('.', $segments);
        openssl_sign($input, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function serviceAccount(): ?array
    {
        $path = config('services.firebase.service_account');

        if (!$path || !is_file($path)) {
            return null;
        }

        $json = json_decode((string) file_get_contents($path), true);

        if (!is_array($json) || empty($json['client_email']) || empty($json['private_key'])) {
            Log::warning('Firebase service account file is invalid.', ['path' => $path]);
            return null;
        }

        return $json;
    }

    private function projectId(): ?string
    {
        return config('services.firebase.project_id') ?: ($this->serviceAccount()['project_id'] ?? null);
    }

    private function messagesEndpoint(): string
    {
        return 'https://fcm.googleapis.com/v1/projects/' . $this->projectId() . '/messages:send';
    }

    private function isConfigured(): bool
    {
        return (bool) $this->projectId() && (bool) $this->serviceAccount();
    }

    private function stringData(array $data): array
    {
        return collect($data)
            ->mapWithKeys(fn ($value, $key) => [(string) $key => (string) $value])
            ->all();
    }

    private function handleFailedToken(MobileDeviceToken $token, ?array $payload, int $status): void
    {
        $errorStatus = data_get($payload, 'error.status');

        if (in_array($errorStatus, ['UNREGISTERED', 'INVALID_ARGUMENT'], true)) {
            $token->delete();
        }

        Log::warning('Firebase push notification was rejected.', [
            'device_token_id' => $token->id,
            'status' => $status,
            'firebase_status' => $errorStatus,
            'message' => data_get($payload, 'error.message'),
        ]);
    }

    private function emptySummary(string $reason, int $tokens = 0): array
    {
        return [
            'tokens' => $tokens,
            'sent' => 0,
            'failed' => 0,
            'reason' => $reason,
        ];
    }
}

<?php

namespace App\Traits;

use Google_Client;
use Illuminate\Support\Facades\Http;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Log;

trait PushNotificationTrait
{
    /**
     * Save notification to DB and send via Firebase
     */
    public function sendAndSaveNotification($userId, $deviceToken, $title, $message, $type = 'general', $image = null)
    {
        $cleanTitle = strip_tags((string) $title);
        $cleanMessage = strip_tags((string) $message);

        // 1. Save to Database
        $notification = UserNotification::create([
            'user_id' => $userId,
            'title' => $cleanTitle,
            'message' => $cleanMessage,
            'image' => $image,
            'type' => $type,
            'device_token' => $deviceToken,
            'is_sent' => 0,
        ]);

        if (!$deviceToken) {
            return false;
        }

        // 2. Send via Firebase
        try {
            $credentialsFilePath = base_path(env('FIREBASE_CREDENTIALS', 'storage/app/firebase/nirmaan-nxt-firebase-adminsdk-fbsvc-c8990bd8b3.json'));

            $client = new Google_Client();
            $client->setAuthConfig($credentialsFilePath);
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

            $client->fetchAccessTokenWithAssertion();
            $token = $client->getAccessToken();
            $accessToken = $token['access_token'];

            $projectId = 'nirmaan-nxt'; // Project ID from your JSON
            $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

            $payload = [
                'message' => [
                    'token' => $deviceToken,
                    'notification' => [
                        'title' => $cleanTitle,
                        'body' => $cleanMessage,
                        'image' => $image ?? '',
                    ],
                    'data' => [
                        'type' => $type,
                    ]
                ]
            ];

            $response = Http::withToken($accessToken)->post($url, $payload);

            if ($response->successful()) {
                $notification->update(['is_sent' => 1]);
                return true;
            } else {
                Log::error('FCM Push Failed: ' . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            Log::error('FCM Exception: ' . $e->getMessage());
            return false;
        }
    }
}
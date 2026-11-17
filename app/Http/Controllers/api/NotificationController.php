<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\UserNotification;
use Carbon\Carbon;

class NotificationController extends Controller
{
    /**
     * Get Authenticated User Notification History (Unpaginated & Grouped)
     */
    public function getNotifications(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid token.'
            ], 401);
        }

        if ($user->status == 0 || $user->status == 2) {
            return response()->json([
                'status' => false,
                'message' => 'Action forbidden. Your account is inactive or blocked.'
            ], 403);
        }

        // Fetch all non-trashed notifications without pagination
        $notifications = UserNotification::where('user_id', $user->id)
            ->where('trash', 0)
            ->orderBy('created_at', 'desc')
            ->get();

        $unreadCount = $notifications->where('is_seen', 0)->count();

        // Group notifications by date header to match the mobile app UI design
        $groupedNotifications = [];

        foreach ($notifications as $notification) {
            $createdAt = $notification->created_at
                ? Carbon::parse($notification->created_at)->timezone('Asia/Kolkata')
                : Carbon::now('Asia/Kolkata');

            // Format section headers ("Today, 12 Aug, 2026")
            if ($createdAt->isToday()) {
                $dateHeader = 'Today, ' . $createdAt->format('d M, Y');
            } elseif ($createdAt->isYesterday()) {
                $dateHeader = 'Yesterday, ' . $createdAt->format('d M, Y');
            } else {
                $dateHeader = $createdAt->format('d M, Y');
            }

            $groupedNotifications[$dateHeader][] = $this->formatNotificationResponse($notification, $createdAt);
        }

        // Format as a list of sections for standard mobile SectionList/RecyclerView consumption
        $responseData = [];
        foreach ($groupedNotifications as $date => $items) {
            $responseData[] = [
                'dateHeader' => $date,
                'items' => $items
            ];
        }

        return response()->json([
            'status' => true,
            'message' => 'Notifications fetched successfully.',
            'data' => [
                'unreadCount' => $unreadCount,
                'notificationsGrouped' => $responseData
            ]
        ], 200);
    }

    /**
     * Mark Single Notification or All Notifications as Seen
     */
    public function markAsSeen(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid token.'
            ], 401);
        }

        if ($user->status == 0 || $user->status == 2) {
            return response()->json([
                'status' => false,
                'message' => 'Action forbidden. Your account is inactive or blocked.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'notificationId' => 'required_without:markAll|nullable|integer',
            'markAll' => 'required_without:notificationId|nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Bulk update if markAll is requested
        if ($request->boolean('markAll')) {
            $updatedCount = UserNotification::where('user_id', $user->id)
                ->where('trash', 0)
                ->where('is_seen', 0)
                ->update(['is_seen' => 1]);

            return response()->json([
                'status' => true,
                'message' => 'All notifications marked as seen.',
                'data' => [
                    'updatedCount' => $updatedCount,
                    'unreadCount' => 0
                ]
            ], 200);
        }

        // Single notification update
        $notification = UserNotification::where('id', $request->notificationId)
            ->where('user_id', $user->id)
            ->where('trash', 0)
            ->first();

        if (!$notification) {
            return response()->json([
                'status' => false,
                'message' => 'Notification not found.'
            ], 404);
        }

        if ((int) $notification->is_seen === 0) {
            $notification->is_seen = 1;
            $notification->save();
        }

        $unreadCount = UserNotification::where('user_id', $user->id)
            ->where('trash', 0)
            ->where('is_seen', 0)
            ->count();

        return response()->json([
            'status' => true,
            'message' => 'Notification marked as seen.',
            'data' => [
                'notificationId' => (int) $notification->id,
                'unreadCount' => $unreadCount
            ]
        ], 200);
    }

    /**
     * Format single notification payload consistently
     */
    private function formatNotificationResponse(UserNotification $notification, Carbon $createdAt): array
    {
        $imageUrl = null;
        if (!empty($notification->image)) {
            $imageUrl = filter_var($notification->image, FILTER_VALIDATE_URL)
                ? $notification->image
                : asset($notification->image);
        }

        // Calculate short time ago string (e.g. "5m ago", "12h ago")
        $now = Carbon::now('Asia/Kolkata');
        $diffInMinutes = (int) $createdAt->diffInMinutes($now);
        $diffInHours = (int) $createdAt->diffInHours($now);
        $diffInDays = (int) $createdAt->diffInDays($now);

        if ($diffInMinutes < 60) {
            $timeAgoStr = max($diffInMinutes, 1) . 'm ago';
        } elseif ($diffInHours < 24) {
            $timeAgoStr = $diffInHours . 'h ago';
        } else {
            $timeAgoStr = $diffInDays . 'd ago';
        }

        return [
            'id' => (int) $notification->id,
            'title' => $notification->title,
            'message' => $notification->message,
            'image' => $imageUrl,
            'type' => $notification->type,
            'isSeen' => (int) $notification->is_seen, // Maps to the red dot indicator in UI
            'isAdminSent' => (int) $notification->is_admin_sent,
            'createdAt' => $createdAt->format('Y-m-d H:i:s'),
            'timeAgo' => $timeAgoStr,
        ];
    }
}
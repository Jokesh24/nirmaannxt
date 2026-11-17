<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Banner;

class BannerController extends Controller
{
    /**
     * Get Active Banners
     * Retrieves all banners where status = 1, including their image, title, and type.
     */
    public function getBanners(Request $request)
    {
        // Fetch active banners and eager load the associated banner type
        $banners = Banner::with('bannerType')
            ->where('status', 1)
            ->orderBy('id', 'desc')
            ->get();

        // Map the collection to a strict, mobile-friendly camelCase array
        $formattedBanners = $banners->map(function ($banner) {
            return [
                'id' => $banner->id,
                'title' => $banner->banner_name, // Mapped to 'title' as requested
                'type' => $banner->bannerType ? $banner->bannerType->type_name : null,
                'image' => $banner->image ? asset('uploads/banners/' . $banner->image) : null,
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Banners retrieved successfully.',
            'data' => [
                'banners' => $formattedBanners
            ]
        ], 200);
    }
}
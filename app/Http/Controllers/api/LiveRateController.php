<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LiveRate;

class LiveRateController extends Controller
{
    /**
     * Get Live Market Rates
     * Retrieves all active live rates formatted for the mobile UI.
     */
    public function getLiveRates(Request $request)
    {
        $rates = LiveRate::where('status', 1)
            ->select('id', 'material_name', 'price', 'unit', 'price_change')
            ->orderBy('id', 'asc')
            ->get();

        $formattedRates = $rates->map(function ($rate) {
            return [
                'id' => $rate->id,
                'title' => strtoupper($rate->material_name),
                'price' => (float) $rate->price,
                'unit' => $rate->unit,
                'priceDisplay' => '₹' . number_format($rate->price, strpos((string) $rate->price, '.') !== false ? 2 : 0),
                'unitDisplay' => '/' . strtolower($rate->unit),
                'changeValue' => (float) $rate->price_change,
                'trend' => $rate->price_change < 0 ? 'down' : ($rate->price_change > 0 ? 'up' : 'neutral'),
                'changeDisplay' => ($rate->price_change > 0 ? '+' : '') . '₹' . abs($rate->price_change)
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Live rates fetched successfully.',
            'data' => [
                'liveRates' => $formattedRates
            ]
        ], 200);
    }
}
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LiveRate;
use Illuminate\Support\Facades\DB;

class LiveRateController extends Controller
{
    public function index()
    {
        $liveRates = LiveRate::orderBy('id', 'asc')->get();
        return view('admin.liverates.index', compact('liveRates'));
    }

    public function update_rates(Request $request)
    {
        // Validation now includes material names and units
        $request->validate([
            'rates' => 'nullable|array',
            'rates.*.id' => 'nullable|exists:live_rates,id',
            'rates.*.material_name' => 'required|string|max:100',
            'rates.*.unit' => 'required|string|max:20',
            'rates.*.price' => 'required|numeric|min:0',
            'rates.*.price_change' => 'required|numeric',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $submittedIds = [];

                if ($request->has('rates')) {
                    foreach ($request->rates as $rateData) {
                        if (!empty($rateData['id'])) {
                            // Update existing rate
                            $rate = LiveRate::find($rateData['id']);
                            if ($rate) {
                                $rate->update([
                                    'material_name' => $rateData['material_name'],
                                    'unit' => $rateData['unit'],
                                    'price' => $rateData['price'],
                                    'price_change' => $rateData['price_change'],
                                ]);
                                $submittedIds[] = $rate->id;
                            }
                        } else {
                            // Create new rate
                            $newRate = LiveRate::create([
                                'material_name' => $rateData['material_name'],
                                'unit' => $rateData['unit'],
                                'price' => $rateData['price'],
                                'price_change' => $rateData['price_change'],
                                'status' => 1
                            ]);
                            $submittedIds[] = $newRate->id;
                        }
                    }
                }

                // Delete any rates that were removed from the user interface
                LiveRate::whereNotIn('id', $submittedIds)->delete();
            });

            return redirect()->back()->with('success', 'Live market rates synchronized successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to synchronize rates: ' . $e->getMessage());
        }
    }

    public function api_get_rates()
    {
        $rates = LiveRate::where('status', 1)
            ->select('id', 'material_name', 'price', 'unit', 'price_change')
            ->orderBy('id', 'asc')
            ->get();

        $formattedRates = $rates->map(function ($rate) {
            return [
                'id' => $rate->id,
                'title' => strtoupper($rate->material_name),
                'price_display' => '₹' . number_format($rate->price, strpos($rate->price, '.') !== false ? 2 : 0),
                'unit_display' => '/' . strtolower($rate->unit),
                'change_value' => $rate->price_change,
                'trend' => $rate->price_change < 0 ? 'down' : ($rate->price_change > 0 ? 'up' : 'neutral'),
                'change_display' => ($rate->price_change > 0 ? '+' : '') . '₹' . abs($rate->price_change)
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedRates,
            'message' => 'Live rates fetched successfully'
        ], 200);
    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TermsAndCondition;
use App\Models\AboutNirmaanNxt;

class ContentController extends Controller
{
    /**
     * 1. Get Active Terms & Conditions
     * Fetches the latest published terms and conditions.
     */
    public function getTermsAndConditions(Request $request)
    {
        $terms = TermsAndCondition::where('status', 1)
            ->orderBy('id', 'desc')
            ->first();

        if (!$terms) {
            return response()->json([
                'status' => false,
                'message' => 'Terms and conditions content not found.',
                'data' => null
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Terms and conditions retrieved successfully.',
            'data' => [
                'termsAndConditions' => [
                    'id' => $terms->id,
                    'description' => $terms->description,
                    'updatedAt' => $terms->updated_at
                ]
            ]
        ], 200);
    }

    /**
     * 2. Get Active About Nirmaan NXT
     * Fetches the latest published About Nirmaan NXT content.
     */
    public function getAboutNirmaanNxt(Request $request)
    {
        $about = AboutNirmaanNxt::where('status', 1)
            ->orderBy('id', 'desc')
            ->first();

        if (!$about) {
            return response()->json([
                'status' => false,
                'message' => 'About Nirmaan NXT content not found.',
                'data' => null
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'About Nirmaan NXT content retrieved successfully.',
            'data' => [
                'aboutNirmaanNxt' => [
                    'id' => $about->id,
                    'description' => $about->description,
                    'updatedAt' => $about->updated_at
                ]
            ]
        ], 200);
    }
}
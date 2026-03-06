<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TokenVerificationController extends Controller
{
    public function verifyToken(Request $request)
    {
        $code = $request->input('coupan_code');

        $coupon = DB::table('coupancodes')
            ->where('coupan_code', $code)
            ->first();

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid coupon code'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $coupon
        ]);
    }
    public function getAllCoupons()
    {
        $coupons = DB::table('coupons')->get();

        return response()->json([
            "success" => true,
            "data" => $coupons
        ]);
    }
}

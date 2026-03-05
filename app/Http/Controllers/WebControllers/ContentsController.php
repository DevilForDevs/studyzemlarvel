<?php

namespace App\Http\Controllers\WebControllers;

use Exception;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ContentsController extends Controller
{
    public function coupons(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        // Join coupons with coupancodes for this user
        $coupons = DB::table('coupons')
            ->leftJoin('coupancodes', function ($join) use ($user) {
                $join->on('coupons.id', '=', 'coupancodes.coupanItemId')
                    ->where('coupancodes.userId', '=', $user->id);
            })
            ->select(
                'coupons.id',
                'coupons.title',
                'coupons.description',
                'coupons.price',
                'coupons.fordays',
                'coupancodes.coupan_code as coupanCode'
            )
            ->get();

        $whatsappUrl = DB::table('help_links')
            ->where('key_name', 'contact_whatsapp')
            ->value('url');

        return response()->json([
            'coupons' => $coupons,
            'contact_whatsapp' => $whatsappUrl
        ]);
    }

    public function createIvdCoupan(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Validate the incoming request
        $data = $request->validate([
            'coupanItemId' => 'required|integer',
            'fordays' => 'required|integer',
            'loggedsysid' => 'nullable|string|max:225',
        ]);

        // Generate a unique coupon code prefixed with 'ivd'
        $uniqueToken = 'ivd' . strtoupper(Str::random(8)); // e.g., ivdA1B2C3D4

        // Insert into the database
        $insertedId = DB::table('coupancodes')->insertGetId([
            'userId' => $user->id,
            'coupanItemId' => $data['coupanItemId'],
            'coupan_code' => $uniqueToken,
            'created_on' => now(),
            'fordays' => $data['fordays'],
            'loggedsysid' => $data['loggedsysid'] ?? null,
        ]);

        return response()->json([
            'message' => 'Coupan created successfully',
            'id' => $insertedId,
            'coupan_code' => $uniqueToken,
        ]);
    }
}

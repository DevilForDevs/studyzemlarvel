<?php

namespace App\Http\Controllers\WebControllers;

use Illuminate\Http\Request;
use Razorpay\Api\Api;
use Exception;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class PaymentController extends Controller
{
    public function createOrder(Request $request)
    {
        $productId = $request->product_id;

        $coupon = DB::table('coupons')->where('id', $productId)->first();

        if (!$coupon) {
            return response()->json(['error' => 'Invalid product'], 400);
        }

        $amount = (int) $coupon->price * 100;

        $keyId     = env('RAZORPAY_KEY');
        $keySecret = env('RAZORPAY_SECRET');

        // ← Add these debug logs
        error_log("Razorpay Key ID: " . ($keyId ?: 'EMPTY/MISSING'));
        error_log("Razorpay Secret: " . ($keySecret ? 'present (length ' . strlen($keySecret) . ')' : 'EMPTY/MISSING'));
        error_log("Amount being sent: " . $amount);

        $api = new Api($keyId, $keySecret);

        try {
            $order = $api->order->create([
                'receipt'  => 'receipt_' . $productId . '_' . time(),
                'amount'   => $amount,
                'currency' => 'INR',
            ]);

            return response()->json($order->toArray());
        } catch (\Razorpay\Api\Errors\BadRequestError $e) {
            // Catch Razorpay-specific error to see the exact message
            return response()->json([
                'error'   => $e->getMessage(),
                'code'    => $e->getCode(),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    public function verifyPayment(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }


        // Validate request
        $data = $request->validate([
            'razorpay_payment_id' => 'required|string',
            'razorpay_order_id' => 'required|string',
            'razorpay_signature' => 'required|string',
            'product_id' => 'required|integer',
            'loggedsysid' => 'nullable|string|max:225'
        ]);

        $paymentId = $data['razorpay_payment_id'];
        $orderId = $data['razorpay_order_id'];
        $signature = $data['razorpay_signature'];
        $productId = $data['product_id'];

        // Generate server signature
        $generatedSignature = hash_hmac(
            'sha256',
            $orderId . "|" . $paymentId,
            env('RAZORPAY_SECRET')
        );

        if ($generatedSignature !== $signature) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid payment signature'
            ]);
        }

        // Prevent duplicate payment
        $existingPayment = DB::table('payments')
            ->where('razorpay_payment_id', $paymentId)
            ->first();

        if ($existingPayment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment already processed'
            ]);
        }

        // Get coupon item details
        $couponItem = DB::table('coupons')
            ->where('id', $productId)
            ->first();

        if (!$couponItem) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid product'
            ]);
        }

        DB::beginTransaction();

        try {

            // Store payment
            DB::table('payments')->insert([
                'user_id' => $user->id,
                'product_id' => $productId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_order_id' => $orderId,
                'razorpay_signature' => $signature,
                'created_at' => now(),
            ]);

            // Generate coupon
            $couponCode = 'ivd' . strtoupper(Str::random(8));

            DB::table('coupancodes')->insert([
                'userId' => $user->id,
                'coupanItemId' => $productId,
                'coupan_code' => $couponCode,
                'created_on' => now(),
                'fordays' => $couponItem->fordays ?? 30,
                'loggedsysid' => $data['loggedsysid'] ?? null
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'coupan_code' => $couponCode
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed'
            ]);
        }
    }
}

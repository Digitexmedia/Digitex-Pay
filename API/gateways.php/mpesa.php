<?php

namespace App\Http\Controllers\Gateway\Mpesa;

use App\Constants\Status;
use App\Models\Deposit;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Gateway\PaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ProcessController extends Controller
{
    /*
     * M-Pesa Custom Gateway Integration
     */

    public static function process($deposit)
    {
        // Get credentials from your gateway settings
        $mpesaAcc = json_decode($deposit->gatewayCurrency()->gateway_parameter);
        $alias = $deposit->gateway->alias;

        // Prepare data for your custom API endpoint
        // Using the phone number from the user's profile or deposit details
        $send['phone'] = auth()->user()->mobile; // Ensure this is in 2547XXXXXXXX format
        $send['amount'] = round($deposit->final_amount);
        $send['reference'] = $deposit->trx;
        $send['description'] = 'Payment for Order ' . $deposit->trx;
        
        // This view should contain the button or auto-submit form to trigger the STK Push
        $send['view'] = 'user.payment.' . $alias;
        $send['api_url'] = 'https://mpesa-stk.giftedtech.co.ke/api/payMaka.php';

        return json_encode($send);
    }

    /**
     * Verification logic (IPN / Callback)
     * Your API uses a specific verification endpoint
     */
    public function ipn(Request $request)
    {
        // The custom API usually returns a 'reference' or 'trx' to track the payment
        $track = $request->reference ?? $request->trx;
        
        $deposit = Deposit::where('trx', $track)->orderBy('id', 'DESC')->first();
        
        if (!$deposit) {
            $notify[] = ['error', 'Invalid Transaction.'];
            return back()->withNotify($notify);
        }

        // Verify the transaction via your moded API's verification link
        $verifyUrl = 'https://mpesa-stk.giftedtech.co.ke/api/verify-transaction.php';
        
        $response = Http::asForm()->post($verifyUrl, [
            'reference' => $track,
        ]);

        if ($response->successful()) {
            $result = $response->json();

            // Check if payment was successful based on your API response structure
            if (isset($result['status']) && $result['status'] == 'success') {
                
                $amountPaid = $result['amount'];
                $requiredAmount = round($deposit->final_amount, 2);

                if ($amountPaid >= $requiredAmount && $deposit->status == Status::PAYMENT_INITIATE) {
                    PaymentController::userDataUpdate($deposit);
                    
                    $notify[] = ['success', 'M-Pesa payment received successfully'];
                    return redirect($deposit->success_url)->withNotify($notify);
                } else {
                    $notify[] = ['error', 'Amount mismatch or already processed.'];
                }
            } else {
                $errorMessage = $result['message'] ?? 'Transaction failed or pending.';
                $notify[] = ['error', $errorMessage];
            }
        } else {
            $notify[] = ['error', 'Unable to reach the M-Pesa verification server.'];
        }

        return redirect()->route('user.deposit.index')->withNotify($notify);
    }
}

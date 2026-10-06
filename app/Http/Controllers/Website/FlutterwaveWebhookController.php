<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Services\FlutterwavePaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FlutterwaveWebhookController extends Controller
{
    public function __invoke(Request $request, FlutterwavePaymentService $payments): Response
    {
        $secretHash = config('services.flutterwave.secret_hash');
        abort_unless($secretHash && hash_equals($secretHash, (string) $request->header('verif-hash')), 401);

        $txRef = (string) $request->input('data.tx_ref');
        $transactionId = (string) $request->input('data.id');
        abort_unless($txRef !== '', 422);

        $attempt = $payments->attempt($txRef);
        if ($request->input('data.status') === 'failed') {
            $payments->markFailed($attempt);
            return response('', 200);
        }
        abort_unless($transactionId !== '', 422);
        abort_unless($payments->verifyAndRecord($attempt, $transactionId), 422);

        return response('', 200);
    }
}

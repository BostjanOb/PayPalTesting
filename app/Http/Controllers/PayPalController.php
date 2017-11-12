<?php

namespace App\Http\Controllers;

use App\PayPal;
use App\PayPalPayment;
use Illuminate\Http\Request;

class PayPalController extends Controller
{
    /**
     * @var PayPalPayment
     */
    private $paypalPayment;

    public function __construct(PayPalPayment $paypalPayment)
    {
        $this->paypalPayment = $paypalPayment;
    }

    public function init()
    {
        $paypal = factory(PayPal::class)->create();

        $payment = $this->paypalPayment->create($paypal);

        $paypal->update([
            'payment_id'   => $payment->id,
            'state'        => $payment->state,
            'intent'       => $payment->intent,
            'payer'        => $payment->payer,
            'transactions' => $payment->transactions[0],
        ]);

        return redirect()->away($payment->getApprovalLink());
    }

    public function success(Request $request)
    {
        $paypal = PayPal::where('payment_id', $request->input('paymentId', ''))->firstOrFail();

        try {
            $payment = $this->paypalPayment->execute($paypal, $request->input('PayerID', ''));

            // Update paypal payment
            $paypal->update([
                'state'        => $payment->state,
                'intent'       => $payment->intent,
                'payer'        => $payment->payer,
                'transactions' => $payment->transactions[0],
            ]);
        } catch (\Exception $e) {
            $paypal->update([
                'state'     => 'failed',
                'exception' => (string)$e,
            ]);
        }

        return redirect()->route('paypal.view', ['paypal' => $paypal]);
    }

    public function cancel()
    {
        return 'user canceled payment!';
    }

    public function view(PayPal $paypal)
    {
        return $paypal;
    }
}

<?php

namespace App\Http\Controllers;

use App\PayPal;
use Illuminate\Http\Request;
use PayPal\Api\Amount;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction as PayPalTransaction;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;

class PayPalController extends Controller
{

    public function init()
    {
        $paypal = factory(PayPal::class)->create();

        $payment = new Payment();
        $payment->setIntent('sale');

        // set payer
        $payer = new Payer();
        $payer->setPaymentMethod('paypal');
        $payment->setPayer($payer);

        // set transactions
        $amount = new Amount();
        $amount->setCurrency('EUR')
            ->setTotal($paypal->price);

        $transactions = new PayPalTransaction();
        $transactions->setAmount($amount)
            ->setDescription('Paywall payment using Paywall.');
        $payment->setTransactions([$transactions]);

        // set redirect urls
        $redirects = new RedirectUrls();
        $redirects->setReturnUrl(route('paypal.success'))
            ->setCancelUrl(route('paypal.cancel'));
        $payment->setRedirectUrls($redirects);

        $payment->create($this->getApi());

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
            $payment = Payment::get($paypal->payment_id, $this->getApi());

            $execution = new PaymentExecution();
            $execution->setPayerId($request->input('PayerID', ''));

            $payment = $payment->execute($execution, $this->getApi());

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

    /**
     * @return ApiContext
     */
    private function getApi(): ApiContext
    {
        $api = new ApiContext(
            new OAuthTokenCredential(
                config('services.paypal.id'),
                config('services.paypal.secret')
            )
        );

        $api->setConfig([
            'mode'                   => 'sandbox',
            'http.ConnectionTimeOut' => 30,
            'log.LogEnabled'         => true,
            'log.FileName'           => storage_path('logs/PayPal.log'),
            'log.LogLevel'           => 'INFO',
            'validation.level'       => 'log',
            'cache.enabled'          => true,
        ]);

        return $api;
    }

}

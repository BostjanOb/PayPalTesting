<?php

namespace App;

use PayPal\Api\Amount;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction as PayPalTransaction;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;

class PayPalPayment
{
    public function create(PayPal $paypal): Payment
    {
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
            ->setDescription($paypal->description);
        $payment->setTransactions([$transactions]);

        // set redirect urls
        $redirects = new RedirectUrls();
        $redirects->setReturnUrl(route('paypal.success'))
            ->setCancelUrl(route('paypal.cancel'));
        $payment->setRedirectUrls($redirects);

        return $this->paymentCreate($payment);
    }

    public function execute(PayPal $paypal, $payerId): Payment
    {
        $payment = $this->paymentGet($paypal->payment_id);

        $execution = new PaymentExecution();
        $execution->setPayerId($payerId);

        return $this->paymentExecute($payment, $execution);
    }

    protected function paymentCreate(Payment $payment): Payment
    {
        return $payment->create($this->getApi());
    }

    protected function paymentGet($payment_id): Payment
    {
        return Payment::get($payment_id, $this->getApi());
    }

    protected function paymentExecute(Payment $payment, PaymentExecution $paymentExecution): Payment
    {
        return $payment->execute($paymentExecution, $this->getApi());
    }

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

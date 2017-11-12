<?php

namespace Tests\Feature;

use App\PayPal;
use App\PayPalPayment;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Exception\PayPalConnectionException;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PayPalControllerTest extends TestCase
{
    use RefreshDatabase;

    public static $createJson = '{"id":"PAY-5R836137AN138535ELHSHEZY", "intent":"sale", "state":"created", "payer":{"payment_method":"paypal"}, "transactions":[{"amount":{"total":"27.08","currency":"EUR"},"description":"Paywall payment using Paywall.", "related_resources":[]}],"create_time":"2017-10-16T08:48:38Z","links":[{"href":"https://api.sandbox.paypal.com/v1/payments/payment/PAY-5R836137AN138535ELHSHEZY","rel":"self","method":"GET"},{"href":"https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=EC-7X403151A5626881W","rel":"approval_url","method":"REDIRECT"},{"href":"https://api.sandbox.paypal.com/v1/payments/payment/PAY-5R836137AN138535ELHSHEZY/execute","rel":"execute","method":"POST"}]}';
    public static $getJson = '{"id":"PAY-5R836137AN138535ELHSHEZY","intent":"sale","state":"created","cart":"07845952KV437993U","payer":{"payment_method":"paypal","status":"VERIFIED","payer_info":{"email":"paypal-buyer@test.com","first_name":"test","last_name":"buyer","payer_id":"CUQ59ZE3AECT4","shipping_address":{"recipient_name":"test buyer","line1":"1 Main St","city":"San Jose","state":"CA","postal_code":"95131","country_code":"US"},"country_code":"US"}},"transactions":[{"amount":{"total":"27.08","currency":"EUR"},"payee":{"merchant_id":"X9ZD6NWTL4A52","email":"business@gmail.com"},"description":"Paywall payment using Paywall.","item_list":{"shipping_address":{"recipient_name":"test buyer","line1":"1 Main St","city":"San Jose","state":"CA","postal_code":"95131","country_code":"US"}},"related_resources":[]}],"redirect_urls":{"return_url":"http://paypaltesting.dev/paypal/success?paymentId=PAY-0F739631BR9388009LHSKY5Y","cancel_url":"http://paypaltesting.dev/paypal/cancel"},"create_time":"2017-10-16T12:56:22Z","update_time":"2017-10-16T12:58:20Z","links":[{"href":"https://api.sandbox.paypal.com/v1/payments/payment/PAY-5R836137AN138535ELHSHEZY","rel":"self","method":"GET"},{"href":"https://api.sandbox.paypal.com/v1/payments/payment/PAY-5R836137AN138535ELHSHEZY/execute","rel":"execute","method":"POST"},{"href":"https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=EC-07845952KV437993U","rel":"approval_url","method":"REDIRECT"}]}';
    public static $executeJson = '{"id":"PAY-5R836137AN138535ELHSHEZY","intent":"sale","state":"approved","cart":"2PY28424HC019031M","payer":{"payment_method":"paypal","status":"VERIFIED","payer_info":{"email":"paypal-buyer@test.com","first_name":"test","last_name":"buyer","payer_id":"CUQ59ZE3AECT4","shipping_address":{"recipient_name":"test buyer","line1":"1 Main St","city":"San Jose","state":"CA","postal_code":"95131","country_code":"US"},"country_code":"US"}},"transactions":[{"amount":{"total":"27.08","currency":"EUR","details":{}},"payee":{"merchant_id":"X9ZD6NWTL4A52","email":"business@gmail.com"},"description":"Paywall payment using Paywall.","item_list":{"shipping_address":{"recipient_name":"test buyer","line1":"1 Main St","city":"San Jose","state":"CA","postal_code":"95131","country_code":"US"}},"related_resources":[{"sale":{"id":"6DN21910U62662209","state":"completed","amount":{"total":"27.08","currency":"EUR","details":{"subtotal":"27.08"}},"payment_mode":"INSTANT_TRANSFER","protection_eligibility":"INELIGIBLE","transaction_fee":{"value":"1.41","currency":"EUR"},"parent_payment":"PAY-82N9876167746263MLHSLLAQ","create_time":"2017-10-16T13:36:03Z","update_time":"2017-10-16T13:36:03Z","links":[{"href":"https://api.sandbox.paypal.com/v1/payments/sale/6DN21910U62662209","rel":"self","method":"GET"},{"href":"https://api.sandbox.paypal.com/v1/payments/sale/6DN21910U62662209/refund","rel":"refund","method":"POST"},{"href":"https://api.sandbox.paypal.com/v1/payments/payment/PAY-5R836137AN138535ELHSHEZY","rel":"parent_payment","method":"GET"}]}}]}],"create_time":"2017-10-16T13:36:04Z","links":[{"href":"https://api.sandbox.paypal.com/v1/payments/payment/PAY-5R836137AN138535ELHSHEZY","rel":"self","method":"GET"}]}';

    /** @test */
    public function get_redirect_link()
    {
        $this->app->bind(PayPalPayment::class, function () {
            return new class() extends PayPalPayment
            {
                protected function paymentCreate(Payment $payment): Payment
                {
                    return $payment->fromJson(PaypalControllerTest::$createJson);
                }
            };
        });

        $this->get('/paypal')
            ->assertRedirect('https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=EC-7X403151A5626881W');

        $this->assertDatabaseHas('paypal', [
            'payment_id' => 'PAY-5R836137AN138535ELHSHEZY',
            'intent'     => 'sale',
            'state'      => 'created',
        ]);
    }

    /** @test */
    public function mark_payment_approved_and_redirect()
    {
        $this->app->bind(PayPalPayment::class, function () {
            return new class() extends PayPalPayment
            {
                protected function paymentGet($paymentId): Payment
                {
                    return (new Payment())->fromJson(PaypalControllerTest::$getJson);
                }

                protected function paymentExecute(Payment $payment, PaymentExecution $paymentExecution): Payment
                {
                    return $payment->fromJson(PaypalControllerTest::$executeJson);
                }
            };
        });

        $paypal = factory(PayPal::class)->create([
            'payment_id' => 'PAY-5R836137AN138535ELHSHEZY',
            'intent'     => 'sale',
            'state'      => 'created',
        ]);

        $this->get('/paypal/success?paymentId=PAY-5R836137AN138535ELHSHEZY&PayerID=CUQ59ZE3AECT4')
            ->assertRedirect('/paypal/view/' . $paypal->id);

        $this->assertDatabaseHas('paypal', [
            'id'    => $paypal->id,
            'state' => 'approved',
        ]);
    }

    /** @test */
    public function handle_when_payment_fails()
    {
        $this->app->bind(PayPalPayment::class, function () {
            return new class() extends PayPalPayment
            {
                protected function paymentGet($paymentId): Payment
                {
                    throw new PayPalConnectionException(
                        'https://api.sandbox.paypal.com/v1/payments/payment/PAY-1VE41016XM301461RLHS2UXY123',
                        "Got Http response code 404 when accessing https://api.sandbox.paypal.com/v1/payments/payment/PAY-1VE41016XM301461RLHS2UXY123.",
                        404
                    );
                }
            };
        });

        $paypal = factory(PayPal::class)->create([
            'payment_id' => 'PAY-5R836137AN138535ELHSHEZY',
            'intent'     => 'sale',
            'state'      => 'created',
        ]);

        $this->get('/paypal/success?paymentId=PAY-5R836137AN138535ELHSHEZY&PayerID=CUQ59ZE3AECT4')
            ->assertRedirect('/paypal/view/' . $paypal->id);

        $this->assertDatabaseHas('paypal', [
            'id'    => $paypal->id,
            'state' => 'failed',
        ]);
    }
}

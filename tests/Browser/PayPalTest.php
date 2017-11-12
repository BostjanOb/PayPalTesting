<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class PayPalTest extends DuskTestCase
{
    use DatabaseMigrations;

    /** @test */
    public function successful_payment_show_complete_page()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/paypal')
                ->waitFor('iframe[name=injectedUl]', 30)
                ->assertSee('Log In to PayPal');

            $browser->driver->switchTo()->frame('injectedUl');

            $browser->type('login_email', env('PAYPAL_USER_EMAIL'))
                ->type('login_password', env('PAYPAL_USER_PASSWORD'))
                ->click('#btnLogin')
                ->waitUntilMissing('iframe[name=injectedUl]', 30);

            $browser->driver->switchTo()->defaultContent();

            $browser->waitForText('Ship to', 30)
                ->assertSee('Pay with')
                ->click('#confirmButtonTop')
                ->waitForLocation('/paypal/view/1', 20)
                ->assertSee('approved');
        });

        $this->assertDatabaseHas('paypal', [
            'id'    => 1,
            'state' => 'approved',
        ]);
    }
}

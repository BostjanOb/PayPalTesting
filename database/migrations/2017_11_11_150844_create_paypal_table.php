<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaypalTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('paypal', function (Blueprint $table) {
            $table->increments('id');

            $table->string('payment_id', 60)->nullable()->index();

            $table->decimal('price', 10, 2);
            $table->string('description');

            $table->string('state', 50)->nullable();
            $table->string('intent', 50)->nullable();
            $table->text('payer')->nullable();
            $table->text('transactions')->nullable();
            $table->text('exception')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('paypal');
    }
}

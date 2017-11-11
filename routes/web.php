<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('paypal', 'PayPalController@init')->name('paypal');
Route::get('paypal/success', 'PayPalController@success')->name('paypal.success');
Route::get('paypal/cancel', 'PayPalController@cancel')->name('paypal.cancel');
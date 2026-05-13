<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */

 
Route::namespace('Api')->name('api.')->group(function () {

    Route::namespace('Auth')->group(function () {
        Route::post('login', 'LoginController@login');
        Route::post('register', 'RegisterController@register');
        Route::post('social-login', 'LoginController@socialLogin');

        Route::namespace('Web3')->prefix('web3')->name('web3.')->group(function () {
            Route::controller("MetamaskController")->prefix('metamask-login')->group(function () {
                Route::any('message', 'message');
                Route::post('verify', 'verify');
            });
        });

        Route::controller('ForgotPasswordController')->group(function () {
            Route::post('password/email', 'sendResetCodeEmail')->name('password.email');
            Route::post('password/verify-code', 'verifyCode')->name('password.verify.code');
            Route::post('password/reset', 'reset')->name('password.update');
        });
    });

    Route::controller('AppController')->group(function () {
        Route::get('general-setting', 'generalSetting');
        Route::get('get-countries', 'getCountries');
        Route::get('onboarding', 'onboarding');
        Route::get('language/{code}', 'language');
        Route::get('blogs', 'blogs');
        Route::get('blog/details/{id}', 'blogDetails');
        Route::get('faqs', 'faqs');
        Route::get('policy-pages', 'policyPages');

        Route::get('market-overview', 'marketOverview');
        Route::get('market-list', 'marketList');
        Route::get('crypto-list', 'cryptoList');
        Route::get('currencies', 'currencies');
    });

    Route::controller("TradeController")->prefix('trade')->group(function () {
        Route::get('order/book/{symbol?}', 'orderBook')->name('trade.order.book');
        Route::get('pairs', 'pairs')->name('trade.pairs');
        Route::get('pair/add-to-favorite', 'addToFavorite');
        Route::get('history/{symbol}', 'history')->name('trade.history');
        Route::get('order/list/{symbol?}', 'orderList')->name('trade.order.list');
        Route::get('currency', 'currency');
        Route::get('{symbol?}', 'trade')->name('trade');
    });

    Route::middleware('auth:sanctum')->group(function () {

        //authorization
        Route::controller('AuthorizationController')->group(function () {
            Route::get('authorization', 'authorization')->name('authorization');
            Route::get('resend-verify/{type}', 'sendVerifyCode')->name('send.verify.code');
            Route::post('verify-email', 'emailVerification')->name('verify.email');
            Route::post('verify-mobile', 'mobileVerification')->name('verify.mobile');
            Route::post('verify-g2fa', 'g2faVerification')->name('go2fa.verify');
        });

        Route::get('user-info', 'UserController@userInfo');


        Route::middleware(['check.status'])->group(function () {
            Route::post('user-data-submit', 'UserController@userDataSubmit')->name('data.submit');

            Route::middleware('registration.complete')->group(function () {
                Route::get('dashboard', function () {
                    return auth()->user();
                });

                Route::controller('UserController')->group(function () {

                    Route::get('dashboard', 'dashboard');

                    //KYC
                    Route::get('kyc-form', 'kycForm')->name('kyc.form');
                    Route::post('kyc-submit', 'kycSubmit')->name('kyc.submit');

                    //Report
                    Route::any('deposit/history', 'depositHistory')->name('deposit.history');
                    Route::get('transactions', 'transactions')->name('transactions');

                    Route::get('referrals', 'referrals');

                    Route::get('twofactor', 'show2faPage');
                    Route::post('twofactor/enable', 'create2fa');
                    Route::post('twofactor/disable', 'disable2fa');

                    Route::get('pair/add/to/favorite/{pairSym}', 'addToFavorite')->name('add.pair.to.favorite');

                    Route::get('notifications', 'notifications');

                    Route::post('validate/password', 'validatePassword');
                });

                
                //Profile setting
                Route::controller('UserController')->group(function () {
                    Route::post('profile-setting', 'submitProfile');
                    Route::post('change-password', 'submitPassword');
                });

                Route::controller('OrderController')->group(function () {
                    Route::prefix('order')->group(function () {
                        Route::get('open', 'open');
                        Route::get('completed', 'completed');
                        Route::get('canceled', 'canceled');
                        Route::post('cancel/{id}', 'cancel');
                        Route::post('update/{id}', 'update');
                        Route::get('history', 'history');
                        Route::post('save/{symbol}', 'save')->name('save');
                    });
                    Route::get('trade-history', 'tradeHistory')->name('trade.history');
                });

                //wallet
                Route::controller('WalletController')->name('wallet.')->prefix('wallet')->group(function () {
                    Route::get('list/{type?}', 'list')->name('list');
                    Route::post('transfer', 'transfer')->name('transfer');
                    Route::post('transfer/to/wallet', 'transferToWallet')->name('transfer.to.other.wallet');
                    Route::get('{type}/{currencySymbol}', 'view')->name('view');
                });

                // Withdraw
                Route::controller('WithdrawController')->group(function () {
                    Route::get('withdraw-method', 'withdrawMethod')->name('withdraw.method')->middleware('kyc');
                    Route::post('withdraw-request', 'withdrawStore')->name('withdraw.money')->middleware('kyc');
                    Route::post('withdraw-request/confirm', 'withdrawSubmit')->name('withdraw.submit')->middleware('kyc');
                    Route::get('withdraw/history', 'withdrawLog')->name('withdraw.history');
                });

                // Payment
                Route::controller('PaymentController')->group(function () {
                    Route::get('deposit/methods', 'methods')->name('deposit');
                    Route::post('deposit/insert', 'depositInsert')->name('deposit.insert');
                    Route::get('deposit/confirm', 'depositConfirm')->name('deposit.confirm');
                    Route::get('deposit/manual', 'manualDepositConfirm')->name('deposit.manual.confirm');
                    Route::post('deposit/manual', 'manualDepositUpdate')->name('deposit.manual.update');
                });
            });
        });

        Route::get('logout', 'Auth\LoginController@logout');
    });
});

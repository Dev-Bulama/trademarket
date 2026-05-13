<?php

namespace App\Http\Controllers\User;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Lib\FormProcessor;
use App\Lib\GoogleAuthenticator;
use App\Models\CoinPair;
use App\Models\Currency;
use App\Models\FavoritePair;
use App\Models\Form;
use App\Models\GatewayCurrency;
use App\Models\Order;
use App\Models\Referral;
use App\Models\Trade;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WithdrawMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function home()
    {
        $pageTitle = 'My Dashboard';
        $user      = auth()->user();
        $pairs     = CoinPair::whereHas('marketData')
            ->select('id', 'market_id', 'coin_id')
            ->with('market:id,name,currency_id', 'coin:id,name,symbol', 'market.currency:id,name,symbol', 'marketData:id,pair_id,price,percent_change_1h,percent_change_24h,html_classes,market_cap')
            ->get();

        $wallets    = $this->wallet();
        $currencies = Currency::rankOrdering()->select('name', 'id', 'symbol')->active()->get();

        $order                     = Order::where('user_id', $user->id);
        $widget['open_order']      = (clone $order)->open()->count();
        $widget['completed_order'] = (clone $order)->completed()->count();
        $widget['canceled_order']  = (clone $order)->canceled()->count();
        $widget['total_trade']     = Trade::where('trader_id', $user->id)->count();

        $recentOrders       = $order->with('pair.coin')->orderBy('id', 'DESC')->take(10)->get();
        $recentTransactions = Transaction::where('user_id', $user->id)->orderBy('id', 'DESC')->take(10)->get();
        $estimatedBalance   = Wallet::where('user_id', $user->id)->join('currencies', 'wallets.currency_id', 'currencies.id')->spot()->sum(DB::raw('currencies.rate * wallets.balance'));

        $gateways = GatewayCurrency::whereHas('method', function ($gate) {
            $gate->where('status', Status::ENABLE);
        })->with('method:id,code,crypto')->get();
        $withdrawMethods = WithdrawMethod::active()->get();

        return view($this->activeTemplate . 'user.dashboard', compact('pageTitle', 'user', 'pairs', 'wallets', 'currencies', 'widget', 'recentOrders', 'recentTransactions', 'estimatedBalance', 'gateways', 'withdrawMethods'));
    }
    public function depositHistory(Request $request)
    {
        $pageTitle = 'Deposit History';
        $deposits  = auth()->user()->deposits()->searchable(['trx', 'currency:symbol'])->with(['gateway', 'wallet.currency'])->orderBy('id', 'desc')->paginate(getPaginate());
        return view($this->activeTemplate . 'user.deposit_history', compact('pageTitle', 'deposits'));
    }

    public function show2faForm()
    {
        $ga        = new GoogleAuthenticator();
        $user      = auth()->user();
        $secret    = $ga->createSecret();
        $qrCodeUrl = $ga->getQRCodeGoogleUrl($user->username . '@' . gs('site_name'), $secret);
        $pageTitle = 'Security';
        return view($this->activeTemplate . 'user.twofactor', compact('pageTitle', 'secret', 'qrCodeUrl'));
    }

    public function create2fa(Request $request)
    {
        $user = auth()->user();
        $this->validate($request, [
            'key'  => 'required',
            'code' => 'required',
        ]);
        $response = verifyG2fa($user, $request->code, $request->key);
        if ($response) {
            $user->tsc = $request->key;
            $user->ts  = 1;
            $user->save();
            $notify[] = ['success', 'Google authenticator activated successfully'];
            return back()->withNotify($notify);
        } else {
            $notify[] = ['error', 'Wrong verification code'];
            return back()->withNotify($notify);
        }
    }

    public function disable2fa(Request $request)
    {
        $this->validate($request, [
            'code' => 'required',
        ]);

        $user     = auth()->user();
        $response = verifyG2fa($user, $request->code);
        if ($response) {
            $user->tsc = null;
            $user->ts  = 0;
            $user->save();
            $notify[] = ['success', 'Two factor authenticator deactivated successfully'];
        } else {
            $notify[] = ['error', 'Wrong verification code'];
        }
        return back()->withNotify($notify);
    }

    public function transactions()
    {
        $pageTitle    = 'Transactions';
        $remarks      = Transaction::distinct('remark')->orderBy('remark')->get('remark');
        $replaceValue = ['wallet_type' => ['spot' => Status::WALLET_TYPE_SPOT, 'funding' => Status::WALLET_TYPE_FUNDING]];
        $query        = Transaction::where('user_id', auth()->id())->searchable(['trx'])->combineColumnValue($replaceValue)->filter(['trx_type', 'remark', 'wallet.currency:symbol', 'wallet:wallet_type']);
        $transactions = $query->orderBy('id', 'desc')->with('wallet.currency')->paginate(getPaginate());
        $currencies   = Currency::active()->rankOrdering()->get();

        return view($this->activeTemplate . 'user.transactions', compact('pageTitle', 'transactions', 'remarks', 'currencies'));
    }

    public function kycForm()
    {
        if (auth()->user()->kv == 2) {
            $notify[] = ['error', 'Your KYC is under review'];
            return to_route('user.home')->withNotify($notify);
        }
        if (auth()->user()->kv == 1) {
            $notify[] = ['error', 'You are already KYC verified'];
            return to_route('user.home')->withNotify($notify);
        }
        $pageTitle = 'KYC Form';
        $form      = Form::where('act', 'kyc')->first();
        return view($this->activeTemplate . 'user.kyc.form', compact('pageTitle', 'form'));
    }

    public function kycData()
    {
        $user      = auth()->user();
        $pageTitle = 'KYC Data';
        return view($this->activeTemplate . 'user.kyc.info', compact('pageTitle', 'user'));
    }

    public function kycSubmit(Request $request)
    {
        $form           = Form::where('act', 'kyc')->first();
        $formData       = $form->form_data;
        $formProcessor  = new FormProcessor();
        $validationRule = $formProcessor->valueValidation($formData);
        $request->validate($validationRule);
        $userData       = $formProcessor->processFormData($request, $formData);
        $user           = auth()->user();
        $user->kyc_data = $userData;
        $user->kv       = 2;
        $user->save();

        $notify[] = ['success', 'KYC data submitted successfully'];
        return to_route('user.home')->withNotify($notify);
    }

    public function attachmentDownload($fileHash)
    {
        $filePath  = decrypt($fileHash);
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $general   = gs();
        $title     = slug($general->site_name) . '- attachments.' . $extension;
        $mimetype  = mime_content_type($filePath);
        header('Content-Disposition: attachment; filename="' . $title);
        header("Content-Type: " . $mimetype);
        return readfile($filePath);
    }

    public function userData()
    {
        $user = auth()->user();
        if ($user->profile_complete == 1) {
            return to_route('user.home');
        }

        if (!$user->mobile) {
            $info       = json_decode(json_encode(getIpInfo()), true);
            $mobileCode = @implode(',', $info['code']);
            $countries  = json_decode(file_get_contents(resource_path('views/partials/country.json')));
        } else {
            $mobileCode = '';
            $countries  = [];
        }

        $pageTitle = 'User Data';
        return view($this->activeTemplate . 'user.user_data', compact('pageTitle', 'user', 'mobileCode', 'countries'));
    }

    public function userDataSubmit(Request $request)
    {
        $user = auth()->user();
        if ($user->profile_complete == Status::YES) {
            return to_route('user.home');
        }

        $validationRule = [
            'firstname' => 'required',
            'lastname'  => 'required',
        ];

        if (!$user->email) {
            $validationRule['email'] = 'required|string|email|unique:users';
        }

        if (!$user->mobile) {
            $countryData  = (array) json_decode(file_get_contents(resource_path('views/partials/country.json')));
            $countryCodes = implode(',', array_keys($countryData));
            $mobileCodes  = implode(',', array_column($countryData, 'dial_code'));
            $countries    = implode(',', array_column($countryData, 'country'));

            $validationRule['country_code'] = 'required|in:' . $countryCodes;
            $validationRule['mobile_code']  = 'required|in:' . $mobileCodes;
            $validationRule['mobile']       = 'required|regex:/^([0-9]*)$/';
            $validationRule['country']      = 'required|in:' . $countries;
        }

        $request->validate($validationRule);

        $user->firstname = $request->firstname;
        $user->lastname  = $request->lastname;
        $user->address   = [
            'country' => @$user->address->country,
            'address' => $request->address,
            'state'   => $request->state,
            'zip'     => $request->zip,
            'city'    => $request->city,
        ];

        $user->profile_complete = Status::YES;
        $general                = gs();

        if (!$user->email) {
            $user->email = strtolower($request->email);
            $user->ev    = $general->ev ? Status::NO : Status::YES;
        }

        if (!$user->mobile) {
            $user->country_code = $request->country_code;
            $user->mobile       = $request->mobile_code . $request->mobile;
            $user->sv           = $general->sv ? Status::NO : Status::YES;
        }

        $user->save();

        $notify[] = ['success', 'Registration process completed successfully'];
        return to_route('user.home')->withNotify($notify);
    }

    public function wallet($skip = 0)
    {

        $wallets = Wallet::where('user_id', auth()->id())
            ->skip($skip)
            ->spot()
            ->take(3)
            ->with('currency:id,name,symbol,image')
            ->select('id', 'balance', 'currency_id')
            ->orderBy('balance', 'desc')
            ->get();

        if (!request()->ajax()) {
            return $wallets;
        }

        return response()->json([
            'success' => true,
            'wallets' => $wallets,
        ]);
    }

    public function addToFavorite($symbol)
    {
        $pair = CoinPair::activeMarket()->activeCoin()->where('symbol', $symbol)->first();
        if (!$pair) {
            return response()->json([
                'success' => false,
                'message' => "Pair not found",
            ]);
        }
        $favoritePair = FavoritePair::where('user_id', auth()->id())->where('pair_id', $pair->id)->first();

        if ($favoritePair) {
            $favoritePair->delete();
            return response()->json([
                'success' => true,
                'deleted' => true,
                'message' => "This pair removed to your favorite list",
            ]);
        }

        $favoritePair          = new FavoritePair();
        $favoritePair->user_id = auth()->id();
        $favoritePair->pair_id = $pair->id;
        $favoritePair->save();

        return response()->json([
            'success' => true,
            'message' => "Pair added to favorite list",
        ]);
    }

    public function referrals()
    {
        $pageTitle = 'My Referrals';
        $user      = auth()->user();
        $maxLevel  = Referral::max('level');
        return view($this->activeTemplate . 'user.referrals', compact('pageTitle', 'user', 'maxLevel'));
    }

    public function allCurrency()
    {
        $query = Currency::active();

        if (request()->type == Status::CRYPTO_CURRENCY) {
            $query->where('type', Status::CRYPTO_CURRENCY)->rankOrdering();
        }

        if (request()->type == Status::FIAT_CURRENCY) {
            $query->where('type', Status::FIAT_CURRENCY)->orderBy('id', 'desc');
        }

        if (request()->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . request()->search . '%')->orWhere('symbol', 'like', '%' . request()->search . '%');
            });
        }

        $currencies = $query->paginate(getPaginate());

        return response()->json([
            'success'    => true,
            'currencies' => $currencies,
            'more'       => $currencies->hasMorePages(),
        ]);
    }

    public function checkUser(Request $request)
    {
        $exist['data'] = false;
        $exist['type'] = null;
        if ($request->email) {
            $exist['data'] = User::where('email', $request->email)->exists();
            $exist['type'] = 'email';
        }
        if ($request->mobile) {
            $exist['data'] = User::where('mobile', $request->mobile)->exists();
            $exist['type'] = 'mobile';
        }
        if ($request->username) {
            $exist['data'] = User::where('username', $request->username)->exists();
            $exist['type'] = 'username';
        }
        return response($exist);
    }
}

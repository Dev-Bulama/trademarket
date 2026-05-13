<?php

namespace App\Http\Controllers\Api;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Lib\FormProcessor;
use App\Lib\GoogleAuthenticator;
use App\Models\CoinPair;
use App\Models\Currency;
use App\Models\FavoritePair;
use App\Models\Form;
use App\Models\GeneralSetting;
use App\Models\NotificationLog;
use App\Models\Referral;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function userDataSubmit(Request $request)
    {
        $user = auth()->user();
        if ($user->profile_complete == 1) {
            $notify[] = 'You\'ve already completed your profile';
            return response()->json([
                'remark'  => 'already_completed',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
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

        $validator = Validator::make($request->all(), $validationRule);

        if ($validator->fails()) {
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $validator->errors()->all()],
            ]);
        }

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

        $notify[] = 'Profile completed successfully';
        return response()->json([
            'remark'  => 'profile_completed',
            'status'  => 'success',
            'message' => ['success' => $notify],
            'data'    => [
                'user' => $user,
            ],
        ]);
    }

    public function userInfo()
    {
        $wallets = Wallet::where('user_id', auth()->id())
            ->with('currency:id,name,symbol,image')
            ->select('id', 'balance', 'currency_id')
            ->orderBy('balance', 'desc');

        $spotWallets    = (clone $wallets)->spot()->get();
        $fundingWallets = (clone $wallets)->funding()->get();

        $notify[] = 'User information';
        return response()->json([
            'remark'  => 'user_info',
            'status'  => 'success',
            'message' => ['success' => $notify],
            'data'    => [
                'user'            => auth()->user(),
                'spot_wallets'    => $spotWallets,
                'funding_wallets' => $fundingWallets,
            ],
        ]);
    }

    public function dashboard()
    {
        $user             = auth()->user();
        $currencies       = Currency::rankOrdering()->select('name', 'id', 'symbol')->active()->get();
        $estimatedBalance = Wallet::where('user_id', $user->id)->join('currencies', 'wallets.currency_id', 'currencies.id')->spot()->sum(DB::raw('currencies.rate * wallets.balance'));

        $notify[] = 'User dashboard';
        return response()->json([
            'remark'  => 'user_dashboard',
            'status'  => 'success',
            'message' => ['success' => $notify],
            'data'    => [
                'user'              => $user,
                'currencies'        => $currencies,
                'estimated_balance' => $estimatedBalance,
            ],
        ]);
    }

    public function kycForm()
    {
        if (auth()->user()->kv == 2) {
            $notify[] = 'Your KYC is under review';
            return response()->json([
                'remark'  => 'under_review',
                'status'  => 'error',
                'message' => ['error' => $notify],
                'data'    => [
                    'kyc_data' => auth()->user()->kyc_data,
                    'path'     => getFilePath('verify'),
                ],
            ]);
        }
        if (auth()->user()->kv == 1) {
            $notify[] = 'You are already KYC verified';
            return response()->json([
                'remark'  => 'already_verified',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }
        $form     = Form::where('act', 'kyc')->first();
        $notify[] = 'KYC field is below';
        return response()->json([
            'remark'  => 'kyc_form',
            'status'  => 'success',
            'message' => ['success' => $notify],
            'data'    => [
                'form' => $form->form_data,
            ],
        ]);
    }

    public function kycSubmit(Request $request)
    {
        $form           = Form::where('act', 'kyc')->first();
        $formData       = $form->form_data;
        $formProcessor  = new FormProcessor();
        $validationRule = $formProcessor->valueValidation($formData);

        $validator = Validator::make($request->all(), $validationRule);

        if ($validator->fails()) {
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $validator->errors()->all()],
            ]);
        }

        $userData       = $formProcessor->processFormData($request, $formData);
        $user           = auth()->user();
        $user->kyc_data = $userData;
        $user->kv       = 2;
        $user->save();

        $notify[] = 'KYC data submitted successfully';
        return response()->json([
            'remark'  => 'kyc_submitted',
            'status'  => 'success',
            'message' => ['success' => $notify],
        ]);

    }

    public function depositHistory(Request $request)
    {
        $deposits = auth()->user()->deposits();
        if ($request->search) {
            $deposits = $deposits->where('trx', $request->search);
        }

        $deposits = $deposits->with(['gateway', 'wallet'])->orderBy('id', 'desc')->apiQuery();
        $notify[] = 'Deposit data';

        return response()->json([
            'remark'  => 'deposits',
            'status'  => 'success',
            'message' => ['success' => $notify],
            'data'    => [
                'deposits' => $deposits,
            ],
        ]);
    }

    public function transactions()
    {
        $remarks      = Transaction::distinct('remark')->orderBy('remark')->get('remark');
        $replaceValue = ['wallet_type' => ['spot' => Status::WALLET_TYPE_SPOT, 'funding' => Status::WALLET_TYPE_FUNDING]];
        $query        = Transaction::where('user_id', auth()->id())->searchable(['trx'])->combineColumnValue($replaceValue)->filter(['trx_type', 'remark', 'wallet.currency:symbol', 'wallet:wallet_type']);
        $transactions = $query->with('wallet.currency')->apiQuery();

        $notify[] = 'Transactions data';
        return response()->json([
            'remark'  => 'transactions',
            'status'  => 'success',
            'message' => ['success' => $notify],
            'data'    => [
                'transactions' => $transactions,
                'remarks'      => $remarks,
            ],
        ]);
    }

    public function submitProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firstname' => 'required',
            'lastname'  => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $validator->errors()->all()],
            ]);
        }

        $user = auth()->user();

        $user->firstname = $request->firstname;
        $user->lastname  = $request->lastname;
        $user->address   = [
            'country' => @$user->address->country,
            'address' => $request->address,
            'state'   => $request->state,
            'zip'     => $request->zip,
            'city'    => $request->city,
        ];

        if ($request->hasFile('image')) {
            try {
                $old         = @$user->image;
                $user->image = fileUploader($request->image, getFilePath('userProfile'), getFileSize('userProfile'), $old);
            } catch (\Exception $exp) {
                $notify[] = ['error', 'Couldn\'t upload your image'];
                return back()->withNotify($notify);
            }
        }

        $user->save();

        $notify[] = 'Profile updated successfully';
        return response()->json([
            'remark'  => 'profile_updated',
            'status'  => 'success',
            'message' => ['success' => $notify],
        ]);
    }

    public function submitPassword(Request $request)
    {
        $passwordValidation = Password::min(6);
        $general            = GeneralSetting::first();
        if ($general->secure_password) {
            $passwordValidation = $passwordValidation->mixedCase()->numbers()->symbols()->uncompromised();
        }

        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'password'         => ['required', 'confirmed', $passwordValidation],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $validator->errors()->all()],
            ]);
        }

        $user = auth()->user();
        if (Hash::check($request->current_password, $user->password)) {
            $password       = Hash::make($request->password);
            $user->password = $password;
            $user->save();
            $notify[] = 'Password changed successfully';
            return response()->json([
                'remark'  => 'password_changed',
                'status'  => 'success',
                'message' => ['success' => $notify],
            ]);
        } else {
            $notify[] = 'The password doesn\'t match!';
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }
    }

    public function referrals()
    {
        $maxLevel = Referral::max('level');

        $relations = '';
        for ($label = 1; $label <= $maxLevel; $label++) {
            $relations .= 'allReferrals';
            $relations .= $label < $maxLevel ? '.' : '';
        }

        $user = auth()->user()->load($relations);

        $notify[] = 'My referrals list';
        return response()->json([
            'remark'  => 'referral_list',
            'status'  => 'success',
            'message' => ['success' => $notify],
            'data'    => [
                'referrals' => $user,
            ],
        ]);
    }

    public function show2faPage()
    {
        $general   = gs();
        $ga        = new GoogleAuthenticator();
        $user      = auth()->user();
        $secret    = $ga->createSecret();
        $qrCodeUrl = $ga->getQRCodeGoogleUrl($user->username . '@' . $general->site_name, $secret);

        $notify[] = 'Two factor';

        return response()->json([
            'remark'  => 'two_factor',
            'status'  => 'success',
            'message' => ['success' => $notify],
            'data'    => [
                'qr_code_url' => $qrCodeUrl,
                'secret'      => $secret,
            ],
        ]);
    }

    public function create2fa(Request $request)
    {
        $user      = auth()->user();
        $validator = Validator::make($request->all(), [
            'key'  => 'required',
            'code' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $validator->errors()->all()],
            ]);
        }

        $response = verifyG2fa($user, $request->code, $request->key);
        if ($response) {
            $user->tsc = $request->key;
            $user->ts  = 1;
            $user->save();

            $notify[] = 'Google authenticator activated successfully';

            return response()->json([
                'remark'  => '2fa_activated',
                'status'  => 'success',
                'message' => ['success' => $notify],
            ]);
        } else {
            $notify[] = 'Wrong verification code';

            return response()->json([
                'remark'  => 'wrong_code',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }
    }

    public function disable2fa(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $validator->errors()->all()],
            ]);
        }

        $user     = auth()->user();
        $response = verifyG2fa($user, $request->code);
        if ($response) {
            $user->tsc = null;
            $user->ts  = 0;
            $user->save();

            $notify[] = 'Two factor authenticator deactivated successfully';

            return response()->json([
                'remark'  => '2fa_deactivated',
                'status'  => 'success',
                'message' => ['success' => $notify],
            ]);
        } else {
            $notify[] = 'Wrong verification code';

            return response()->json([
                'remark'  => 'wrong_code',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }
    }

    public function addToFavorite($symbol)
    {
        $pair = CoinPair::activeMarket()->activeCoin()->where('symbol', $symbol)->first();
        if (!$pair) {
            $notify[] = 'Pair not found';
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);

        }

        $favoritePair = FavoritePair::where('user_id', auth()->id())->where('pair_id', $pair->id)->first();

        if ($favoritePair) {
            $favoritePair->delete();

            $notify[] = 'This pair removed to your favorites';
            return response()->json([
                'remark'  => 'pair_removed',
                'status'  => 'success',
                'message' => ['success' => $notify],
            ]);

        }

        $favoritePair          = new FavoritePair();
        $favoritePair->user_id = auth()->id();
        $favoritePair->pair_id = $pair->id;
        $favoritePair->save();

        $notify[] = 'Pair added to favorite list';
        return response()->json([
            'remark'  => 'pair_added',
            'status'  => 'success',
            'message' => ['success' => $notify],
        ]);

    }

    public function notifications()
    {
        $notifications = NotificationLog::where('user_id', auth()->id())->apiQuery();

        $notify[] = 'Notifications Logs';
        return response()->json([
            'remark'  => 'notifications_logs',
            'status'  => 'success',
            'message' => ['success' => $notify],
            'data'    => [
                'notifications' => $notifications,
            ],
        ]);
    }

    public function validatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required',
        ]);

        if($validator->fails()){
            return response()->json([
                'remark'=>'validation_error',
                'status'=>'error',
                'message'=>['error'=>$validator->errors()->all()],
            ]);
        }

        $user = auth()->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => ['Provided password does not correct']]
            ]);
        }

        return response()->json([
            'remark'  => 'valid_password',
            'status'  => 'success',
            'message' => ['success' => ['Provided password is correct']]
        ]);

    }

}

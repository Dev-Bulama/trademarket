<?php

use Carbon\Carbon;
use App\Lib\Captcha;
use App\Models\Wallet;
use App\Notify\Notify;
use App\Lib\ClientInfo;
use App\Lib\CurlRequest;
use App\Lib\FileManager;
use App\Models\CoinPair;
use App\Models\Currency;
use App\Models\Frontend;
use App\Models\Referral;
use App\Constants\Status;
use App\Models\Extension;
use App\Models\MarketData;
use App\Models\Transaction;
use Illuminate\Support\Str;
use App\Models\GeneralSetting;
use App\Events\MarketDataEvent;
use App\Lib\GoogleAuthenticator;
use App\Models\P2P\TradeFeedBack;
use App\Models\CurrencyDataProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;


function systemDetails()
{
    $system['name']          = 'vinance';
    $system['version']       = '1.5';
    $system['build_version'] = '4.4.11';
    return $system;
}

function slug($string)
{
    return Illuminate\Support\Str::slug($string);
}

function verificationCode($length)
{
    if ($length == 0) return 0;
    $min = pow(10, $length - 1);
    $max = (int) ($min - 1) . '9';
    return random_int($min, $max);
}

function getNumber($length = 8)
{
    $characters       = '1234567890';
    $charactersLength = strlen($characters);
    $randomString     = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function activeTemplate($asset = false)
{
    $general  = gs();
    $template = session('template') ?? $general->active_template;
    if ($asset) return 'assets/templates/' . $template . '/';
    return 'templates.' . $template . '.';
}

function activeTemplateName()
{
    $general  = gs();
    $template = session('template') ?? $general->active_template;
    return $template;
}

function siteLogo($type = null)
{
    $name = $type ? "/logo_$type.png" : '/logo.png';
    return getImage(getFilePath('logoIcon') . $name);
}

function siteFavicon()
{
    return getImage(getFilePath('logoIcon') . '/favicon.png');
}

function loadReCaptcha()
{
    return Captcha::reCaptcha();
}

function loadCustomCaptcha($width = '100%', $height = 46, $bgColor = '#003')
{
    return Captcha::customCaptcha($width, $height, $bgColor);
}

function verifyCaptcha()
{
    return Captcha::verify();
}

function loadExtension($key)
{
    $extension = Extension::where('act', $key)->where('status', Status::ENABLE)->first();
    return $extension ? $extension->generateScript() : '';
}

function getTrx($length = 12)
{
    $characters       = 'ABCDEFGHJKMNOPQRSTUVWXYZ123456789';
    $charactersLength = strlen($characters);
    $randomString     = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function getAmount($amount, $length = null)
{
    if (!$length) $length = gs('allow_decimal_after_number');
    $amount            = round($amount ?? 0, $length);
    return $amount + 0;
}

function showAmount($amount, $decimal = null, $separate = true, $exceptZeros = false)
{
    if (!$decimal) if (!$decimal) $decimal = gs('allow_decimal_after_number');
    $separator                          = '';
    if ($separate) {
        $separator = ',';
    }
    $printAmount = number_format($amount, $decimal, '.', $separator);
    if ($exceptZeros) {
        $exp = explode('.', $printAmount);
        if ($exp[1] * 1 == 0) {
            $printAmount = $exp[0];
        } else {
            $printAmount = rtrim($printAmount, '0');
        }
    }
    return $printAmount;
}

function removeElement($array, $value)
{
    return array_diff($array, (is_array($value) ? $value : array($value)));
}

function cryptoQR($wallet)
{
    return "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=$wallet&choe=UTF-8";
}

function keyToTitle($text)
{
    return ucfirst(preg_replace("/[^A-Za-z0-9 ]/", ' ', $text));
}

function titleToKey($text)
{
    return strtolower(str_replace(' ', '_', $text));
}

function strLimit($title = null, $length = 10)
{
    return Str::limit($title, $length);
}

function getIpInfo()
{
    $ipInfo = ClientInfo::ipInfo();
    return $ipInfo;
}

function osBrowser()
{
    $osBrowser = ClientInfo::osBrowser();
    return $osBrowser;
}

function getTemplates()
{
    $param['website'] = @$_SERVER['HTTP_HOST'] . @$_SERVER['REQUEST_URI'] . ' - ' . env("APP_URL");
    $url              = 'https://license.viserlab.com/updates/templates/' . systemDetails()['name'];
    $response         = CurlRequest::curlPostContent($url, $param);
    if ($response) {
        return $response;
    } else {
        return null;
    }
}

function getPageSections($arr = false)
{
    $jsonUrl  = resource_path('views/') . str_replace('.', '/', activeTemplate()) . 'sections.json';
    $sections = json_decode(file_get_contents($jsonUrl));
    if ($arr) {
        $sections = json_decode(file_get_contents($jsonUrl), true);
        ksort($sections);
    }
    return $sections;
}

function getImage($image, $size = null, $isAvator = false)
{
    $clean = '';
    
    if (file_exists($image) && is_file($image)) {
        return asset($image) . $clean;
    }
    if ($isAvator) {
        return asset('assets/images/extra_images/avator.jpg');
    }
    if ($size) {
        return route('placeholder.image', $size);
    }
    return asset('assets/images/default.png');
}

function notify($user, $templateName, $shortCodes = null, $sendVia = null, $createLog = true)
{
    $general = gs();

    $globalShortCodes = [
        'site_name'       => $general->site_name,
        'site_currency'   => $general->cur_text,
        'currency_symbol' => $general->cur_sym,
    ];

    if (gettype($user) == 'array') {
        $user = (object) $user;
    }

    $shortCodes = array_merge($shortCodes ?? [], $globalShortCodes);

    $notify               = new Notify($sendVia);
    $notify->templateName = $templateName;
    $notify->shortCodes   = $shortCodes;
    $notify->user         = $user;
    $notify->createLog    = $createLog;
    $notify->userColumn   = isset($user->id) ? $user->getForeignKey() : 'user_id';
    $notify->send();
}

function getPaginate($paginate = 20)
{
    return $paginate;
}

function paginateLinks($data)
{
    return $data->appends(request()->all())->links();
}

function menuActive($routeName, $type = null, $param = null)
{
    if ($type == 3) $class = 'side-menu--open';
    elseif ($type == 2) $class = 'sidebar-submenu__open';
    else   $class              = 'active';

    if (is_array($routeName)) {
        foreach ($routeName as $key => $value) {
            if (request()->routeIs($value)) return $class;
        }
    } elseif (request()->routeIs($routeName)) {
        if ($param) {
            $routeParam = array_values(@request()->route()->parameters ?? []);
            if (strtolower(@$routeParam[0]) == strtolower($param)) return $class;
            else return;
        }
        return $class;
    }
}

function fileUploader($file, $location, $size = null, $old = null, $thumb = null,$filename = null)
{
    $fileManager        = new FileManager($file);
    $fileManager->path  = $location;
    $fileManager->size  = $size;
    $fileManager->old   = $old;
    $fileManager->thumb = $thumb;
    $fileManager->filename = $filename;
    $fileManager->upload();
    return $fileManager->filename;
}

function fileManager()
{
    return new FileManager();
}

function getFilePath($key)
{
    return fileManager()->$key()->path;
}

function getFileSize($key)
{
    return fileManager()->$key()->size;
}

function getFileExt($key)
{
    return fileManager()->$key()->extensions;
}

function diffForHumans($date)
{
    $lang = session()->get('lang');
    Carbon::setlocale($lang);
    return Carbon::parse($date)->diffForHumans();
}

function showDateTime($date, $format = 'Y-m-d h:i A')
{
    $lang = session()->get('lang');
    Carbon::setlocale($lang);
    return Carbon::parse($date)->translatedFormat($format);
}

function getContent($dataKeys, $singleQuery = false, $limit = null, $orderById = false)
{

    $templateName = activeTemplateName();

    if ($singleQuery) {
        $content = Frontend::where('tempname', $templateName)->where('data_keys', $dataKeys)->orderBy('id', 'desc')->first();
    } else {
        $article = Frontend::where('tempname', $templateName);
        $article->when($limit != null, function ($q) use ($limit) {
            return $q->limit($limit);
        });
        if ($orderById) {
            $content = $article->where('data_keys', $dataKeys)->orderBy('id')->get();
        } else {
            $content = $article->where('data_keys', $dataKeys)->orderBy('id', 'desc')->get();
        }
    }
    return $content;
}


function gatewayRedirectUrl($type = false)
{
    if ($type) {
        return 'user.deposit.history';
    } else {
        return 'user.home';
    }
}

function verifyG2fa($user, $code, $secret = null)
{
    $authenticator = new GoogleAuthenticator();
    if (!$secret) {
        $secret = $user->tsc;
    }
    $oneCode  = $authenticator->getCode($secret);
    $userCode = $code;
    if ($oneCode == $userCode) {
        $user->tv = 1;
        $user->save();
        return true;
    } else {
        return false;
    }
}

function urlPath($routeName, $routeParam = null)
{
    if ($routeParam == null) {
        $url = route($routeName);
    } else {
        $url = route($routeName, $routeParam);
    }
    $basePath = route('home');
    $path     = str_replace($basePath, '', $url);
    return $path;
}

function showMobileNumber($number)
{
    $length = strlen($number);
    return substr_replace($number, '***', 2, $length - 4);
}

function showEmailAddress($email)
{
    $endPosition = strpos($email, '@') - 1;
    return substr_replace($email, '***', 1, $endPosition);
}

function getRealIP()
{
    $ip = $_SERVER["REMOTE_ADDR"];
    //Deep detect ip
    if (filter_var(@$_SERVER['HTTP_FORWARDED'], FILTER_VALIDATE_IP)) {
        $ip = $_SERVER['HTTP_FORWARDED'];
    }
    if (filter_var(@$_SERVER['HTTP_FORWARDED_FOR'], FILTER_VALIDATE_IP)) {
        $ip = $_SERVER['HTTP_FORWARDED_FOR'];
    }
    if (filter_var(@$_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP)) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    if (filter_var(@$_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    if (filter_var(@$_SERVER['HTTP_X_REAL_IP'], FILTER_VALIDATE_IP)) {
        $ip = $_SERVER['HTTP_X_REAL_IP'];
    }
    if (filter_var(@$_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP)) {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    if ($ip == '::1') {
        $ip = '127.0.0.1';
    }

    return $ip;
}

function appendQuery($key, $value)
{
    return request()->fullUrlWithQuery([$key => $value]);
}

function dateSort($a, $b)
{
    return strtotime($a) - strtotime($b);
}

function dateSorting($arr)
{
    usort($arr, "dateSort");
    return $arr;
}

function gs($key = null)
{
    $general = Cache::get('GeneralSetting');
    if (!$general) {
        $general = GeneralSetting::first();
        Cache::put('GeneralSetting', $general);
    }
    if ($key) return @$general->$key;
    return $general;
}

function isImage($string)
{
    $allowedExtensions = array('jpg', 'jpeg', 'png', 'gif');
    $fileExtension     = pathinfo($string, PATHINFO_EXTENSION);
    if (in_array($fileExtension, $allowedExtensions)) {
        return true;
    } else {
        return false;
    }
}
function isHtml($string)
{
    if (preg_match('/<.*?>/', $string)) {
        return true;
    } else {
        return false;
    }
}

function highLightedString($string, $className = 'text--base'): string
{
    $string = __($string);
    $string = str_replace("{{", '<span class="' . $className . '">', $string);
    $string = str_replace("}}", '</span>', $string);
    return $string;
}

function copyRightText(): string
{
    $text = '&copy; ' . date('Y') . ' <a href="' . route('home') . '" class="text--base"> ' . trans(gs('site_name')) . '
</a>. ' . trans('All Rights Reserved') . '';
    return $text;
}

function defaultCurrencyDataProvider($newObject = true): object
{
    $provider = CurrencyDataProvider::active()->where('is_default', Status::YES)->first();
    
    if (!$provider) throw new Exception('Currency data provider not found');
    if (!$newObject) return $provider;

    $alias = "App\\Lib\\CurrencyDataProvider\\" . $provider->alias;
    $newObject           = new $alias;
    $newObject->provider = $provider;

    return $newObject;
}

function upOrDown($newNumber, $oldNumber)
{
    $newNumber = getAmount($newNumber);
    $oldNumber = getAmount($oldNumber);
    if (substr($newNumber, 0, 1) == '-' || $newNumber < $oldNumber) return 'down';
    if ($newNumber > $oldNumber) return 'up';
    return 0;
}

function createWallet()
{
    $currencies = Currency::active()
        ->leftJoin('wallets', function ($q) {
            $q->on('currencies.id', '=', 'wallets.currency_id')->where('user_id', auth()->id());
        })
        ->whereNull('wallets.currency_id')
        ->select('currencies.*')
        ->get();

    $wallets     = [];
    $now         = now();
    $userId      = auth()->id();
    $walletTypes = gs('wallet_types');

    foreach ($currencies as $currency) {
        foreach ($walletTypes as $walletType) {
            $wallets[] = [
                'user_id'     => $userId,
                'currency_id' => $currency->id,
                'wallet_type' => $walletType->type_value,
                'created_at'  => $now,
                'updated_at'  => $now
            ];
        }
    }
    if (count($wallets)) Wallet::insert($wallets);
}

function checkWalletConfiguration($type, $option, $walletType = null)
{
    if (!$walletType) $walletType = gs('wallet_types');
    return @$walletType->$type->configuration->$option->status ? true : false;
}

function levelCommission($user, $amount, $commissionType, $trx, $currencyId)
{
    $meUser       = $user;
    $i            = 1;
    $level        = Referral::where('commission_type', $commissionType)->count();
    $transactions = [];
    $now          = now();

    while ($i <= $level) {
        $me    = $meUser;
        $refer = @$me->referrer;

        if (!$refer || $refer == "") {
            break;
        }
        $commission = Referral::where('commission_type', $commissionType)->where('level', $i)->first();
        $wallet     = Wallet::where('user_id', $refer->id)->where('currency_id', $currencyId)->first();

        if (!$commission || !$wallet) {
            break;
        }

        $com = ($amount * $commission->percent) / 100;

        $wallet->balance += $com;
        $wallet->save();

        $transactions[] = [
            'user_id'      => $refer->id,
            'wallet_id'    => $wallet->id,
            'amount'       => $com,
            'post_balance' => $refer->balance,
            'charge'       => 0,
            'trx_type'     => '+',
            'details'      => 'level ' . $i . ' Referral Commission From ' . $user->username,
            'trx'          => $trx,
            'remark'       => 'referral_commission',
            'created_at'   => $now,
        ];

        if ($commissionType == 'deposit_commission') {
            $comType = 'Deposit';
        } elseif ($commissionType == 'trade_commission') {
            $comType = 'Trade Commission';
        }

        notify($refer, 'REFERRAL_COMMISSION', [
            'amount'       => showAmount($com),
            'post_balance' => showAmount($refer->balance),
            'trx'          => $trx,
            'level'        => ordinal($i),
            'type'         => $comType,
            'currency'     => @$wallet->currency->symbol
        ]);

        $meUser = $refer;
        $i++;
    }

    if (!empty($transactions)) {
        Transaction::insert($transactions);
    }
}

function ordinal($number)
{
    $ends = array('th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th');
    if ((($number % 100) >= 11) && (($number % 100) <= 13)) {
        return $number . 'th';
    } else {
        return $number .
            $ends[$number % 10];
    }
}
function userTableEmptyMessage($message = 'data')
{
    return '<tr>
                <td  class = "text-muted text-center" colspan = "100%">
                <div class = "empty-thumb text-center p-5">
                <img src   = "' . asset('assets/images/extra_images/empty.png') . '" />
                <p   class = "fs-14">' . trans('No ' . $message . ' found') . '</p>
                    </div>
                </td>
            </tr>';
}
function currencyWiseOrderQuery($query, $currency)
{
    if ($currency->type == Status::CRYPTO_CURRENCY) {
        $query = $query->where(function ($q) use ($currency) {
            $q->where('market_currency_id', $currency->id)->orWhere('coin_id', $currency->id);
        });
    } else {
        $query = $query->where('market_currency_id', $currency->id);
    }
    return $query;
}

function orderCancelAmount($order)
{
    $amount = $order->amount - $order->filled_amount;

    if ($order->order_side == Status::BUY_SIDE_ORDER) {
        $duePercentage    = ($amount / $order->amount) * 100;
        $chargeBackAmount = ($order->charge / 100) * $duePercentage;
        $amount           = ($amount * $order->rate);
    } else {
        $chargeBackAmount = 0;
    }
    return [
        'amount'             => $amount,
        'charge_back_amount' => $chargeBackAmount,
    ];
}

function returnBack($message, $type = "error", $withInput = false)
{
    $notify[] = [$type, $message];

    if ($withInput) {
        return back()->withNotify($notify)->withInput();
    } else {
        return back()->withNotify($notify);
    }
}

function wsData($data)
{
    try {
        $data       = json_decode($data, true);
        $symbol     = str_replace('-', '_', $data['product_id']);
        $coinPair   = CoinPair::where('symbol', $symbol)->first();
        $marketData = MarketData::where('pair_id', $coinPair->id)->first();

        if ($marketData) {

            $htmlClasses = [
                'price_change'       => upOrDown(@$data['price'], $marketData->price),
                'percent_change_1h'  => upOrDown($marketData->percent_change_1h, $marketData->percent_change_1h),
                'percent_change_24h' => upOrDown($marketData->percent_change_24h, $marketData->percent_change_24h),
                'percent_change_7d'  => upOrDown($marketData->percent_change_7d, $marketData->percent_change_7d),
            ];

            $marketData->price        = @$data['price'];
            $marketData->html_classes = $htmlClasses;
            $marketData->save();

            $newData = json_encode([
                'symbol'             => $symbol,
                'price'              => @$data['price'],
                'percent_change_1h'  => @$data['open_24h'],
                'percent_change_24h' => @$data['open_24h'],
                'html_classes'       => @$data['open_24h'],
                'id'                 => $marketData->id,
                'market_cap'         => @$data['open_24h'],
                'html_classes'       => $marketData->html_classes,
                'last_price'         => @$data['price'],
            ]);
            event(new MarketDataEvent($newData));
        }
    } catch (Exception $ex) {
        info(["exception is " => $ex->getMessage()]);
    }
}

function firstTwoCharacter(string $string): string
{
    $words = explode(' ', $string);
    return isset($words[1]) ? substr($words[0], 0, 1) . substr($words[1], 0, 1) : substr($words[0], 0, 1);
}

function jsonResponse(mixed $message = null, $status = false, array $data = [])
{
    $response = [
        'success' => $status,
        'message' => $message,
    ];
    if ($data) $response['data'] = $data;
    return response()->json($response);
}

function userFeedback($userId)
{
    $feedbackQuery = TradeFeedBack::where('user_id', $userId);

    $feedback['positive']            = (clone  $feedbackQuery)->where('type', Status::P2P_TRADE_FEEDBACK_POSITIVE)->count();
    $feedback['negative']            = (clone  $feedbackQuery)->where('type', Status::P2P_TRADE_FEEDBACK_NEGATIVE)->count();
    $feedback['total']               = (clone  $feedbackQuery)->count();
    $feedback['positive_percentage'] = @$feedback['total'] > 0 ?  ($feedback['positive'] / $feedback['total'] * 100) : 0;
    $feedback['negative_percentage'] = @$feedback['total'] > 0 ?  ($feedback['negative'] / $feedback['total'] * 100) : 0;

    return (object) $feedback;
}

function configBroadcasting()
{
    $pusherCredentials = gs('pusher_config');
    Config::set([
        'broadcasting.connections.pusher.key'             => $pusherCredentials->pusher_app_key,
        'broadcasting.connections.pusher.secret'          => $pusherCredentials->pusher_app_secret,
        'broadcasting.connections.pusher.app_id'          => $pusherCredentials->pusher_app_id,
        'broadcasting.connections.pusher.options.cluster' => $pusherCredentials->pusher_app_cluster,
    ]);
}
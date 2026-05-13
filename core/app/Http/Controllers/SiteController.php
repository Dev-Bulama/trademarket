<?php

namespace App\Http\Controllers;

use App\Constants\Status;
use App\Models\AdminNotification;
use App\Models\CoinPair;
use App\Models\Currency;
use App\Models\Frontend;
use App\Models\Language;
use App\Models\Page;
use App\Models\Subscriber;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Validator;

class SiteController extends Controller
{
    public function index()
    {
        $reference = @$_GET['reference'];
        if ($reference) {
            session()->put('reference', $reference);
        }
        $pageTitle = 'Home';
        $sections  = Page::where('tempname', $this->activeTemplate)->where('slug', '/')->first();
        return view($this->activeTemplate . 'home', compact('pageTitle', 'sections'));
    }

    public function pages($slug)
    {
        $page      = Page::where('tempname', $this->activeTemplate)->where('slug', $slug)->firstOrFail();
        $pageTitle = $page->name;
        $sections  = $page->secs;
        return view($this->activeTemplate . 'pages', compact('pageTitle', 'sections'));
    }

    public function contact()
    {
        $pageTitle = "Contact Us";
        $user      = auth()->user();
        return view($this->activeTemplate . 'contact', compact('pageTitle', 'user'));
    }

    public function about()
    {
        $pageTitle = "About Us";
        $sections  = Page::where('tempname', $this->activeTemplate)->where('slug', 'about-us')->firstOrFail();
        return view($this->activeTemplate . 'about', compact('pageTitle', 'sections'));
    }

    public function contactSubmit(Request $request)
    {
        $request->validate([
            'name'    => 'required',
            'email'   => 'required',
            'subject' => 'required|string|max:255',
            'message' => 'required',
        ]);

        if (!verifyCaptcha()) {
            $notify[] = ['error', 'Invalid captcha provided'];
            return back()->withNotify($notify);
        }
        $request->session()->regenerateToken();

        $random = getNumber();

        $ticket           = new SupportTicket();
        $ticket->user_id  = auth()->id() ?? 0;
        $ticket->name     = $request->name;
        $ticket->email    = $request->email;
        $ticket->priority = Status::PRIORITY_MEDIUM;

        $ticket->ticket     = $random;
        $ticket->subject    = $request->subject;
        $ticket->last_reply = Carbon::now();
        $ticket->status     = Status::TICKET_OPEN;
        $ticket->save();

        $adminNotification            = new AdminNotification();
        $adminNotification->user_id   = auth()->user() ? auth()->user()->id : 0;
        $adminNotification->title     = 'A new contact message has been submitted';
        $adminNotification->click_url = urlPath('admin.ticket.view', $ticket->id);
        $adminNotification->save();

        $message                    = new SupportMessage();
        $message->support_ticket_id = $ticket->id;
        $message->message           = $request->message;
        $message->save();

        $notify[] = ['success', 'Ticket created successfully!'];

        return to_route('ticket.view', [$ticket->ticket])->withNotify($notify);
    }

    public function policyPages($slug, $id)
    {
        $policy    = Frontend::where('id', $id)->where('data_keys', 'policy_pages.element')->firstOrFail();
        $pageTitle = $policy->data_values->title;
        return view($this->activeTemplate . 'policy', compact('policy', 'pageTitle'));
    }

    public function changeLanguage($lang = null)
    {
        $language = Language::where('code', $lang)->first();
        if (!$language) {
            $lang = 'en';
        }

        session()->put('lang', $lang);
        session()->put('lang_flag', $language->flag);
        return back();
    }

    public function blogDetails($slug, $id)
    {
        $blog = Frontend::where('id', $id)->firstOrFail();

        $pageTitle = "Blog Details";
        $blogs     = Frontend::where('data_keys', 'blog.element')->where('id', '!=', $blog->id)->latest()->take(5)->get();

        //seo content
        $seoContents['title']              = $blog->data_values->title;
        $seoContents['social_title']       = $blog->data_values->title;
        $seoContents['description']        = strLimit(@$blog->data_values->description, 150);
        $seoContents['social_description'] = strLimit(@$blog->data_values->description, 150);
        $seoContents['image']              = getImage('assets/images/frontend/blog/' . @$blog->data_values->blog_image, '820x440');
        $seoContents['image_size']         = '820x440';

        return view($this->activeTemplate . 'blog_details', compact('blog', 'pageTitle', 'blogs', 'seoContents'));
    }

    public function cookieAccept()
    {
        Cookie::queue('gdpr_cookie', gs('site_name'), 43200);
    }

    public function cookiePolicy()
    {
        $pageTitle = 'Cookie Policy';
        $cookie    = Frontend::where('data_keys', 'cookie.data')->first();
        return view($this->activeTemplate . 'cookie', compact('pageTitle', 'cookie'));
    }

    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:subscribers,email',
        ], [
            'email.unique' => "You have already subscribed",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error'   => $validator->errors()->all(),
                'success' => false,
            ]);
        }

        $subscribe        = new Subscriber();
        $subscribe->email = $request->email;
        $subscribe->save();

        return response()->json([
            'message' => "Thank you for subscribing us",
            'success' => true,
        ]);
    }

    public function placeholderImage($size = null)
    {
        $imgWidth  = explode('x', $size)[0];
        $imgHeight = explode('x', $size)[1];
        $text      = $imgWidth . '×' . $imgHeight;
        $fontFile  = realpath('assets/font/RobotoMono-Regular.ttf');
        $fontSize  = round(($imgWidth - 50) / 8);
        if ($fontSize <= 9) {
            $fontSize = 9;
        }
        if ($imgHeight < 100 && $fontSize > 30) {
            $fontSize = 30;
        }

        $image     = imagecreatetruecolor($imgWidth, $imgHeight);
        $colorFill = imagecolorallocate($image, 100, 100, 100);
        $bgFill    = imagecolorallocate($image, 175, 175, 175);
        imagefill($image, 0, 0, $bgFill);
        $textBox    = imagettfbbox($fontSize, 0, $fontFile, $text);
        $textWidth  = abs($textBox[4] - $textBox[0]);
        $textHeight = abs($textBox[5] - $textBox[1]);
        $textX      = ($imgWidth - $textWidth) / 2;
        $textY      = ($imgHeight + $textHeight) / 2;
        header('Content-Type: image/jpeg');
        imagettftext($image, $fontSize, 0, $textX, $textY, $colorFill, $fontFile, $text);
        imagejpeg($image);
        imagedestroy($image);
    }

    public function maintenance()
    {
        $pageTitle = 'Maintenance Mode';
        if (gs('maintenance_mode') == Status::DISABLE) {
            return to_route('home');
        }
        $maintenance = Frontend::where('data_keys', 'maintenance.data')->first();
        return view($this->activeTemplate . 'maintenance', compact('pageTitle', 'maintenance'));
    }

    public function pusherAuthentication($socketId, $channelName)
    {
        $general = gs();
        $pusherSecret = @$general->pusher_config->pusher_app_secret;
        $str          = $socketId . ":" . $channelName;
        $hash         = hash_hmac('sha256', $str, $pusherSecret);

        return response()->json([
            'success' => true,
            'message' => "Pusher authentication successfully",
            'auth'    => @$general->pusher_config->pusher_app_key . ":" . $hash,
        ]);
    }

    public function market()
    {
        $pageTitle = 'Market List';
        $sections  = Page::where('tempname', $this->activeTemplate)->where('slug', 'market')->first();
        return view($this->activeTemplate . 'market_list', compact('pageTitle', 'sections'));
    }
    public function crypto()
    {
        $pageTitle = 'Cryptocurrency';
        $sections  = Page::where('tempname', $this->activeTemplate)->where('slug', 'crypto-currency')->first();
        return view($this->activeTemplate . 'crypto_currency', compact('pageTitle', 'sections'));
    }

    public function marketList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:all,crypto,fiat',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->all(),
            ]);
        }

        $query = CoinPair::searchable(['symbol'])->select('id', 'market_id', 'coin_id', 'symbol');

        if ($request->type != 'all') {
            $query->whereHas('market', function ($q) use ($request) {
                $q->whereHas('currency', function ($c) use ($request) {
                    if ($request->type == 'crypto') {
                        return $c->crypto();
                    }
                    $c->fiat();
                });
            });
        }

        $query = $query->with('market:id,name,currency_id', 'coin:id,name,symbol,image', 'market.currency:id,name,symbol,image', 'marketData')
            ->withCount('trade as total_trade')
            ->orderBy('total_trade', 'desc');

        $total = (clone $query)->count();
        $pairs = (clone $query)->skip($request->skip ?? 0)
            ->take($request->limit ?? 20)
            ->get();

        return response()->json([
            'success' => true,
            'pairs'   => $pairs,
            'total'   => $total,
        ]);
    }

    public function cryptoCurrencyList(Request $request)
    {
        $query = Currency::active()->crypto()->with('marketData')->rankOrdering()
            ->searchable(['name', 'symbol']);

        $total      = (clone $query)->count();
        $currencies = (clone $query)->skip($request->skip ?? 0)
            ->take($request->limit ?? 20)
            ->get();

        return response()->json([
            'success'    => true,
            'currencies' => $currencies,
            'total'      => $total,
        ]);
    }

    public function pwaConfiguration()
    {
        $gs = gs();
        $json = [
            "name"             => $gs->site_name,
            "sign"             => $gs->site_name,
            "start_url"        => route('trade'),
            "display"          => "standalone",
            "background_color" => "#5900b3",
            "theme_color"      => "black",
            "description"      => $gs->site_name . " PWA",
            "icons"            => [
                [
                    "src"   => getImage(getFilePath('logoIcon') . '/pwa_favicon.png'),
                    "sizes" => "192x192",
                    "type"  => "image/png",
                ],
                [
                    "src"   => getImage(getFilePath('logoIcon') . '/pwa_thumb.png'),
                    "sizes" => "512x512",
                    "type"  => "image/png",
                ],
            ],
        ];

        return response()->json($json);
    }
}

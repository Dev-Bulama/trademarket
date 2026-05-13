<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomPage;
use App\Models\FrontendSetting;
use Illuminate\Http\Request;

class LandingPageController extends Controller
{
    public function index()
    {
        $pageTitle = 'Custom Landing Page';
        $setting   = FrontendSetting::instance();
        $page      = CustomPage::first();
        return view('admin.landing_page.index', compact('pageTitle', 'setting', 'page'));
    }

    public function preview()
    {
        $page = CustomPage::first();
        abort_if(!$page, 404, 'No custom page saved yet.');
        return view('landing_page_render', compact('page'));
    }

    public function save(Request $request)
    {
        $request->validate([
            'html_content'     => 'nullable|string',
            'frontend_disabled' => 'required|in:0,1',
        ]);

        // Upsert the single custom page
        $page = CustomPage::firstOrNew([]);
        $page->title        = 'Custom Landing Page';
        $page->html_content = $request->html_content;
        $page->status       = 1;
        $page->save();

        // Save toggle + link the page
        $setting = FrontendSetting::instance();
        $setting->frontend_disabled = $request->frontend_disabled;
        $setting->active_page_id   = $page->id;
        $setting->save();

        $notify[] = ['success', 'Landing page settings saved successfully.'];
        return back()->withNotify($notify);
    }
}

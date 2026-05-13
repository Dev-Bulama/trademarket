<?php

namespace App\Http\Middleware;

use App\Models\FrontendSetting;
use App\Models\CustomPage;
use Closure;
use Illuminate\Http\Request;

class LandingPageOverride
{
    public function handle(Request $request, Closure $next)
    {
        if (
            $request->is('/') &&
            $request->isMethod('GET') &&
            FrontendSetting::isOverrideActive()
        ) {
            $page = CustomPage::first();
            if ($page) {
                return response(view('landing_page_render', compact('page')));
            }
        }

        return $next($request);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FrontendSetting extends Model
{
    protected $fillable = ['frontend_disabled', 'active_page_id'];

    public function activePage()
    {
        return $this->belongsTo(CustomPage::class, 'active_page_id');
    }

    public static function instance(): self
    {
        $setting = static::first();
        if (!$setting) {
            $setting = static::create(['frontend_disabled' => 0, 'active_page_id' => null]);
        }
        return $setting;
    }

    public static function isOverrideActive(): bool
    {
        $setting = \Cache::remember('frontend_setting', 60, fn () => static::with('activePage')->first());
        return $setting && $setting->frontend_disabled == 1 && $setting->active_page_id !== null;
    }

    public static function getActivePage(): ?CustomPage
    {
        $setting = \Cache::remember('frontend_setting', 60, fn () => static::with('activePage')->first());
        return $setting?->activePage;
    }

    protected static function boot()
    {
        parent::boot();
        static::saved(fn () => \Cache::forget('frontend_setting'));
        static::deleted(fn () => \Cache::forget('frontend_setting'));
    }
}

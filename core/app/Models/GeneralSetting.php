<?php

namespace App\Models;

use App\Traits\GlobalStatus;
use Illuminate\Database\Eloquent\Model;

class GeneralSetting extends Model
{
    use GlobalStatus;

    protected $casts = [
        'mail_config'           => 'object',
        'sms_config'            => 'object',
        'global_shortcodes'     => 'object',
        'socialite_credentials' => 'object',
        'wallet_types'          => 'object',
        'pusher_config'         => 'object',
    ];

    protected $hidden = ['email_from', 'email_template', 'sms_body', 'mail_config', 'global_shortcodes', 'sms_config', 'system_info', 'trading_view_widget'];

    public function scopeSiteName($query, $pageTitle)
    {
        $pageTitle = empty($pageTitle) ? '' : ' - ' . $pageTitle;
        return $this->site_name . $pageTitle;
    }

    protected static function boot()
    {
        parent::boot();
        static::saved(function () {
            \Cache::forget('GeneralSetting');
        });
    }
}

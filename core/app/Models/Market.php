<?php

namespace App\Models;

use App\Traits\GlobalStatus;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Model;

class Market extends Model
{
    use GlobalStatus, Searchable;

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }
    public function pairs()
    {
        return $this->hasMany(CoinPair::class, 'market_id');
    }
}

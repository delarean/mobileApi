<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bid extends Model
{
    protected $table = 'bids';

    protected $guarded = ['id'];

    public function services()
    {
        return $this->belongsToMany('App\Models\Service',
            'bids_choose',
            'bids_id',
            'service_id');
    }

    public function user()
    {

        return $this->belongsTo('App\Models\User');

    }

    public function product()
    {
        return $this->belongsTo('App\Models\Product','products_id');
    }

    public function bidResponses()
    {
        return $this->hasMany('App\Models\BidResponse','bids_id','id');
    }
}

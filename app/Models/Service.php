<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $table = 'services';

    protected $guarded = ['id'];

    public function bids()
    {
        return $this->belongsToMany('App\Models\Bid',
            'bids_choose',
            'service_id',
            'bids_id');
    }
}

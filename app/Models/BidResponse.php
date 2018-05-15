<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BidResponse extends Model
{
    protected $table = 'bids_response';

    protected $guarded = ['id'];

    public function bid()
    {
        return $this->belongsTo('App\Models\Bid','bids_id','id');
    }

    public function branch()
    {
        return $this->belongsTo('App\Models\Branch','users_branches_id');
    }
}

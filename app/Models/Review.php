<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{

    protected $table = 'reviews';

    protected $guarded = ['id'];

    public function branch()
    {
        return $this->belongsTo('App\Models\Branch','users_branches_id');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User','user_id');
    }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserOpt extends Model
{

    protected $table = 'users_optional';

    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo('App\Models\User','user_id');
    }

    public function logo()
    {
        return $this->belongsTo('App\Models\Image','image_id');
    }

}

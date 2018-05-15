<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhoneSalt extends Model
{

    protected $table = 'phone_salt';

    protected $guarded = ['id'];

    public function user()
    {
        return $this->hasOne('App\Models\User','phone_id','id');
    }
}

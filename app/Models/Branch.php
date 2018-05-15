<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $guarded = ['id'];

    protected $table = 'users_branches';

    public function user()
    {
        return $this->belongsTo('App\Models\User','user_id');
    }

    public function bidResponses()
    {
        return $this->hasMany('App\Models\BidResponse','users_branches_id');
    }

    public function images()
    {
        return $this->belongsToMany('App\Models\Image',
            'users_branches_img',
            'users_branches_id',
            'image_id');
    }

    public function reviews()
    {
        return $this->hasMany('App\Models\Review','users_branches_id');
    }
}

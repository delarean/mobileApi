<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{

    protected $table = 'sales';

    protected $guarded = ['id'];

    public function images()
    {
        return $this->belongsToMany('App\Models\Image',
            'sales_images',
            'sale_id',
            'image_id');
    }

    public function cities()
    {
        return $this->belongsToMany('App\Models\City',
            'sales_cities',
            'sale_id',
            'city_id');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User','user_id');
    }

}

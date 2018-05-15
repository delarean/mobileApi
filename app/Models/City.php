<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{

    protected $table = 'cities';

    protected $guarded = ['id'];

    public function sales()
    {
        return $this->belongsToMany('App\Models\Sale',
            'sales_cities',
            'city_id',
            'sale_id');
    }

}

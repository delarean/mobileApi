<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderCondition extends Model
{

    protected $guarded = ['id'];

    protected $table = 'orders_conditions';

    public $timestamps = false;

    public function order()
    {
        return $this->belongsTo('App\Models\Order','orders_id');
    }

    public function services()
    {
        return $this->belongsToMany('App\Models\Service',
            'orders_conditions_services',
            'orders_conditions_id',
            'services_id');
    }

}

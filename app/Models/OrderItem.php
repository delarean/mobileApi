<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{

    protected $guarded = ['id'];

    protected $table = 'orders_items';

    public $timestamps = false;

    public function relatedServices()
    {
        return $this->belongsToMany('App\Models\RelatedService',
            'orders_services',
            'orders_items_id',
            'related_services_id');
    }

    public function order()
    {
        return $this->belongsTo('App\Models\Order','orders_id');
    }

}

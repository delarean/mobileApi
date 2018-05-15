<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RelatedService extends Model
{

    protected $table = 'related_services';

    protected $guarded = ['id'];

    public function products()
    {
        return $this->belongsToMany('App\Models\Product',
            'products_related_services',
            'related_services_id',
            'product_id');
    }

    public function ordersItems()
    {
        return $this->belongsToMany('App\Models\OrderItem',
            'orders_services',
            'related_services_id',
            'orders_items_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';

    protected $guarded = ['id'];

    public function bids()
    {
        return $this->hasMany('App\Models\Bid','products_id');
    }

    public function characters()
    {
        return $this->belongsToMany('App\Models\Character',
            'products_characters',
            'product_id',
            'character_id');
    }

    public function relatedServices()
    {
        return $this->belongsToMany('App\Models\RelatedService',
            'products_related_services',
            'product_id',
            'related_services_id');
    }

    public function category()
    {
        return $this->belongsTo('App\Models\ProductCategory','category_id');
    }

    public function images()
    {
        return $this->belongsToMany('App\Models\Image',
            'products_img',
            'product_id',
            'image_id');
    }

    public function orders()
    {
        return $this->belongsToMany('App\Models\Order',
            'orders_items',
            'product_id',
            'orders_id');
    }
}

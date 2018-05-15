<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    protected $table = 'images';

    protected $guarded = ['id'];

    public function userOpt()
    {
        return $this->hasOne('App\Models\UserOpt','image_id');
    }

    public function branches()
    {
        return $this->belongsToMany('App\Models\Branch',
            'users_branches_img',
            'image_id',
            'users_branches_id');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User','owner_id');
    }

    public function sales()
    {
        return $this->belongsToMany('App\Models\Sale',
            'sales_images',
            'image_id',
            'sale_id');
    }

    public function products()
    {
        return $this->belongsToMany('App\Models\Product',
            'products_img',
            'image_id',
            'product_id');
    }

    public function news()
    {
        return $this->belongsToMany('App\Models\News',
            'news_images',
            'image_id',
            'news_id');
    }

}

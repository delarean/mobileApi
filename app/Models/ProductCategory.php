<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    protected $table = 'products_category';

    protected $guarded = ['id'];


    public function products()
    {
        return $this->hasMany('App\Models\Product','category_id');
    }
}

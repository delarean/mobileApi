<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Character extends Model
{
    protected $table = 'characters';

    protected $guarded = ['id'];

    public function products()
    {
        return $this->belongsToMany('App\Models\Character',
            'products_characters',
            'character_id',
            'product_id');
    }
}

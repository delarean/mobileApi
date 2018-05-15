<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class News extends Model
{

    protected $table = 'news';

    protected $guarded = ['id'];

    public function images()
    {
        return $this->belongsToMany('App\Models\Image',
            'news_images',
            'news_id',
            'image_id');
    }

}

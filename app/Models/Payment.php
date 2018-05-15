<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{

    protected $table = 'payment_method';

    protected $guarded = ['id'];

    public $timestamps = false;

}

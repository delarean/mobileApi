<?php

namespace App\Models;

use App\Classes\ApiError;
use Illuminate\Database\Eloquent\Model;
use phpDocumentor\Reflection\Types\Null_;

class User extends Model
{


    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    protected $guarded = ['id'];

    protected $table = 'users';

    public function phoneSalt()
    {
        return $this->belongsTo('App\Models\PhoneSalt','phone_id');
    }

    public function userOpt()
    {
        return $this->hasOne('App\Models\UserOpt','user_id');
    }

    public function city()
    {
        return $this->belongsTo('App\Models\City','city_id');
    }

    public function bids()
    {
        return $this->hasMany('App\Models\Bid','user_id');
    }

    public function branches()
    {
        return $this->hasMany('App\Models\Branch','user_id');
    }

    public function images()
    {
        return $this->hasMany('App\Models\Image','owner_id');
    }

    public function reviews()
    {
        return $this->hasMany('App\Models\Review','user_id');
    }

    public function isOwner(Model $model,$model_id,array $params = []){

        $bild = $model::where('user_id',$this->id)
            ->where('id',$model_id);

        if(isset($params)){
            foreach ($params as $param){

                if($param['operand'] === NULL)
                    $bild = $bild->where($param['name'],$param['value']);
                else
                    $bild = $bild->where($param['name'],$param['operand'],$param['value']);
            }
        }

        return $bild->exists();


    }

    public function sales()
    {
        return $this->hasMany('App\Models\Sale','user_id');
    }

    public function orders()
    {
        return $this->hasMany('App\Models\Order','user_id');
    }

}

<?php

namespace App\Models;

use App\Classes\ApiError;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

class Order extends Model
{

    protected $guarded = ['id'];

    protected $table = 'orders';

    public function user()
    {
        return $this->belongsTo('App\Models\User','user_id');
    }

    public function products()
    {
        return $this->belongsToMany('App\Models\Product',
            'orders_items',
            'orders_id',
            'product_id')
            ->withPivot('quantity');
    }

    public function items()
    {
        return $this->hasMany('App\Models\OrderItem','orders_id');
    }

    public function conditions()
    {
        return $this->hasMany('App\Models\OrderCondition','orders_id');
    }

    public function addResponse($usr_br_id,$quant,$quant_type,$comment,$price)
    {

        if($this->status !== 2)
            return new ApiError(341,
                NULL,
                "Нельзя ответить на заказ",
                "Нельзя ответить на заказ, статус - ".$this->status);

        if($this->is_accepted !== 1)
            return new ApiError(341,
                NULL,
                "Нельзя ответить на заказ",
                "Нельзя ответить на заказ, is_accepted - ".$this->is_accepted);

        $order_id = $this->id;

        $order_resp = new OrderResponse;

        $order_resp->users_branches_id = $usr_br_id;
        $order_resp->price = $price;
        $order_resp->quantity = $quant;
        $order_resp->quantity_type = $quant_type;
        $order_resp->comment = $comment;
        $order_resp->status = 0;
        $order_resp->orders_id = $order_id;

        try{
            $order_resp->save();
        }
        catch (QueryException $ex){
            return new ApiError(310);
        }

        return true;

    }


}

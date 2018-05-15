<?php

namespace App\Http\Controllers\Orders;

use App\Classes\ApiError;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class OrdersUserController extends Controller
{

    public function getUserOrdersList(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'status' => 'integer|nullable|between:0,6',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $user = $this->getCurrentUserModel($request->input('auth_token'));

        $order_bld = $user->orders();

        if($request->has('status') && $request->filled('status')){

            $status = $request->input('status');
            $order_bld->where('status',$status);

        }

        $orders = $order_bld->cursor();

        $response = [];

        foreach ($orders as $order){

            $order_in_arr = [
                'id' => $order->id,
                'status' => $order->status,
                'payment_id' => $order->payment_id,
                'delivery_id' => $order->delivery_id,
                'terms' => isset($order->terms) ? strtotime($order->terms) : NULL,
                'user_id' => $order->user_id,
                'is_review' => $order->is_review,
                'type' => $order->type,
                'created_at' => isset($order->created_at) ? strtotime($order->created_at) : NULL,
                'updated_at' => isset($order->updated_at) ? strtotime($order->updated_at) : NULL,
            ];

            array_push($response,$order_in_arr);

        }

        return response()->json([

            'response' => $response,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

}

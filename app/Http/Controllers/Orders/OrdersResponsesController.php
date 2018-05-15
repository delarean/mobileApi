<?php

namespace App\Http\Controllers\Orders;

use App\Classes\ApiError;
use App\Models\Order;
use App\Traits\OrderResponseTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class OrdersResponsesController extends Controller
{

    use OrderResponseTrait;

    public function addOrderResponse(Request $request)
    {

        $user = $this->getCurrentUserModel($request->input('auth_token'));

        if($user->user_type !== 2){
            $err = new ApiError(308);
            return $err->json();
        }

        $validator = Validator::make($request->all(), [
            'orders_id' => [
                'required',
                'integer',
                'exists:orders,id',
            ],
            'users_branches_id' => [
                'required','integer',
                Rule::exists('users_branches','id')
                    ->where(function ($query) use($user){
                    $query->where('user_id',$user->id);
                }),
                ],
            'quantity' => 'required|integer',
            'quantity_type' => 'required|integer|between:1,3',
            'comment' => 'required|max:255',
            'price' => 'required|numeric'
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $order_id = $request->input('orders_id');
        $usr_br_id = $request->input('users_branches_id');
        $quant = $request->input('quantity');
        $quant_type = $request->input('quantity_type');
        $comment = $request->input('comment');
        $price = $request->input('price');

        $order = Order::find($order_id);

        $resp = $order->addResponse($usr_br_id,$quant,$quant_type,$comment,$price);

        if($resp instanceof ApiError)
            return $resp->json();

        unset($resp);

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

}

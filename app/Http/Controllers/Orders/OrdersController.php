<?php

namespace App\Http\Controllers\Orders;

use App\Classes\ApiError;
use App\Models\Order;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Traits\OrderTrait;

class OrdersController extends Controller
{
    use OrderTrait;
	
//Добавляет товар
    public function addOrderProduct(Request $request)
    {

        $user = $this->getCurrentUserModel($request->input('auth_token'));

        //Проверяем ,является ли покупателем

        if($user->user_type !== 1){
            $err = new ApiError(308);
            return $err->json();
        }

        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer|exists:products,id',
            'orders_id' => [
                'integer',
                Rule::exists('orders','id')->where(function ($query) use($user){
                    $query->where('user_id',$user->id);
                }),
                ],
            'quantity' => 'required|integer',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $ord_id = $request->input('orders_id');
        $prod_id = $request->input('product_id');
        $quant = $request->input('quantity');

        $response = $this->saveOrder($user,$ord_id,$prod_id,$quant);

        if($response instanceof ApiError)
            return $response->json();

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

//Список товаров заказа
    public function getOrderProductsList(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'orders_id' => [
                'required',
                'integer',
                'exists:orders,id',
            ]
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $response = [];

        $ord_id = $request->input('orders_id');

        $order = Order::find($ord_id);

        $prods = $order->products;

        if(!$prods->isEmpty()){

            foreach ($prods as $prod){

                $product_in_arr = [
                    'product_id' => $prod->id,
                    'quantity' => $prod->pivot->quantity,
                    'name' => $prod->name,
                    'description' => $prod->description,
                    'average_price' => $prod->average_price,
                    'category_id' => $prod->category_id,
                    'category_name' => $prod->category->name,
                ];

                array_push($response,$product_in_arr);

            }

        }

        return response()->json([

            'response' => $response,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

//Список заказов
    public function getOrdersList(Request $request)
    {
        $user = $this->getCurrentUserModel($request->input('auth_token'));

        if($user->user_type !== 2){
            $err = new ApiError(308);
            return $err;
        }

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

        $branchs = $user->branches;

        $city_ids = [];

        foreach ($branchs as $branch)
            $city_ids[] = $branch->city_id;


        $order_bld = Order::whereIn('city_id',$city_ids);

        if($request->has('status') && $request->filled('status')){

            $status = $request->input('status');
            $order_bld->where('status',$status);

        }
        else{
            $order_bld->where('status','<>',0);
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

//удаляет товар из заказа
    public function deleteOrderProduct(Request $request)
    {

        $user = $this->getCurrentUserModel($request->input('auth_token'));

        //Проверяем ,является ли покупателем

        if($user->user_type !== 1){
            $err = new ApiError(308);
            return $err->json();
        }

        $validator = Validator::make($request->all(), [
            'orders_id' => [
                'required',
                'integer',
                Rule::exists('orders','id')->where(function ($query) use($user){
                    $query->where('user_id',$user->id);
                }),
            ],
            'product_id' => [
                'required','integer',
                Rule::exists('orders_items','product_id')
                    ->where(function ($query) use($request){
                    $query->where('orders_id',$request->input('orders_id'));
                }),
            ],
            'quantity' => 'integer|filled',
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
        $prod_id = $request->input('product_id');

        if($request->has('quantity')) {
            $quant = $request->input('quantity');

            $resp = $this->changeProductQuantity('-', $quant, $order_id, $prod_id);

        }
        else{
            $resp = $this->deleteProduct($order_id,$prod_id);
        }

        if($resp instanceof ApiError){
            return $resp->json();
        }

        unset($resp);

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

//отказ исполнителю
    public function cancelOrder(Request $request)
    {

        $user = $this->getCurrentUserModel($request->input('auth_token'));

        //Проверяем ,является ли покупателем

        if($user->user_type !== 1){
            $err = new ApiError(308);
            return $err->json();
        }

        $validator = Validator::make($request->all(), [
            'orders_id' => [
                'required',
                'integer',
                Rule::exists('orders','id')->where(function ($query) use($user){
                    $query->where('user_id',$user->id);
                }),
            ],
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

        $order = Order::find($order_id);

        $order->status = 6;

        try{
            $order->save();
        }
        catch (QueryException $ex){
            $err = new ApiError(310);
            return $err->json();
        }

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }
//получаем конкретный заказ
    public function getOrder(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'orders_id' => [
                'required',
                'integer',
                'exists:orders,id',
            ],
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

        $order = Order::find($order_id);

        $response = [
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

        return response()->json([

            'response' => $response,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

//формируем заказ
    public function makeOrderFormed(Request $request)
    {

        $user = $this->getCurrentUserModel($request->input('auth_token'));

        //Проверяем ,является ли покупателем

        if($user->user_type !== 1){
            $err = new ApiError(308);
            return $err->json();
        }

        $validator = Validator::make($request->all(), [
            'payment_id' => [
                'required',
                'integer',
                'exists:payment_method,id',
            ],
            'delivery_id' => 'required|integer|exists:delivery_method,id',

            'terms' => [
                'required',
                'integer',
                'min:'.time(),
                ],

            'type' => 'required|integer|between:0,1',

            'orders_id' => [
                'required_if:type,0',
                'integer',
                Rule::exists('orders','id')->where(function ($query) use($user){
                    $query->where('user_id',$user->id)
                          ->where('status',0)
                          ->orWhere('status',6);
                }),
            ],

            'taxes_type_id' => 'required_if:type,1|integer|exists:taxes_type,id',

            'bussines_type_id' => 'required_if:type,1|integer|exists:bussines_type,id',

            'is_alcho' => 'required_if:type,1|integer|between:0,1',

            'services_id' => 'required_if:type,1|array',

            'services_id.*' => 'required_with:services_id|integer|exists:services,id',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $paym_id = $request->input('payment_id');
        $delivery_id = $request->input('delivery_id');
        $terms = $request->input('terms');
        $type = $request->input('type');

        //Оформляем заказ без товара
        if($type === 0){

            $order_id = $request->input('orders_id');
        }
        else{
            $tax_id = $request->input('taxes_type_id');
            $bussines_id = $request->input('bussines_type_id');
            $is_alcho = $request->input('is_alcho');

            if($request->has('services_id'))
            $services_ids = $request->input('services_id');
            else
                $services_ids = NULL;
            $resp = $this->formOrderWithoutProduct($tax_id,$bussines_id,$is_alcho,$services_ids,$user->id);

            if($resp instanceof ApiError)
                return $resp->json();

            $order_id = $resp;

            unset($resp);

        }



        $resp = $this->formOrder($paym_id,$delivery_id,$terms,$order_id,$user);

        if($resp instanceof ApiError)
            return $resp->json();



        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }


}

<?php

namespace App\Http\Controllers\Orders;

use App\Classes\ApiError;
use App\Models\Order;
use App\Traits\OrderTrait;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class OrdersRelatedServicesController extends Controller
{
    use OrderTrait;

    public function addRelatedServices(Request $request)
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
            'related_services_id' => 'required|array',
            'related_services_id.*' => [
                'required',
                'integer',
                Rule::exists('products_related_services','related_services_id')
                    ->where(function ($query) use($request){
                    $query->where('product_id',$request->input('product_id'));
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

        $prod_id = $request->input('product_id');

        //Проверяем есть ли товар в заказе
        $ord_id = $request->input('orders_id');
        if(!isset($ord_id)){

            //Проверяем ,есть ли заказ - корзина
            $not_formed_order_bld = $user
                ->orders()
                ->where('status',0);

            if($not_formed_order_bld->exists()){

                $order = $not_formed_order_bld->first();

            }
            else{
                //Нет заказа ,создаём новый
                $resp = $this->makeNewOrder($user->id);

                if($resp instanceof ApiError)
                    return $resp->json();

                $order = $resp;

                unset($resp);
            }

        }
        else{

            //проверка прав пользователя на данный заказ
            $usr_order_bld = $user->orders()->where('id',$ord_id);

            if(!$usr_order_bld->exists()){
                $err = new ApiError(308);
                return $err->json();
            }

            $order = $usr_order_bld->first();

        }

        $resp = $this->isProductInOrder($order,$prod_id);

        if($resp === false){
            unset($resp);

            $resp = $this->addProductToOrder($prod_id,1,$order);

            if($resp instanceof ApiError)
                return $resp->json();

            unset($resp);

        }

        $rel_serv_ids = $request->input('related_services_id');

        $resp = $this->addRelatedServiceToOrder($order,$rel_serv_ids,$prod_id);

        if($resp instanceof ApiError)
            return $resp->json();

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function deleteRelatedService(Request $request)
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
                'required',
                'integer',
                Rule::exists('orders_items','product_id')
                    ->where(function ($query) use($request){
                        $query->where('orders_id',$request->input('orders_id'));
                    }),
                ],
            'related_services_id' =>[
                'required',
                'integer',
                Rule::exists('orders_services','related_services_id')
                    ->where(function ($query) use($request){
                        $query->where('orders_id',$request->input('orders_id'));
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

        $prod_id = $request->input('product_id');
        $rel_serv_id = $request->input('related_services_id');
        $order_id = $request->input('orders_id');

        //Проверка, что данная услуга прикреплена к товару в заказе
        $resp = $this->isOrdersProdHasRelServ($prod_id,$rel_serv_id,$order_id);

        if($resp === false){
            $err = new ApiError(341,
                NULL,
                'Услуга не прикреплена к товару',
                'Услуга не прикреплена к товару');
            return $err->json();
        }

        $item = $resp;

        unset($resp);

        //Удаляем услугу

        $rel_serv_ids = [$rel_serv_id];

        $resp = $this->deleteOrderItemsRelatedServices($item,$rel_serv_ids);

        if($resp instanceof ApiError)
            return $resp->json();

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function getProductsRelatedServices(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'orders_id' => [
                'required',
                'integer',
                'exists:orders,id'
            ],
            'product_id' => [
                'required',
                'integer',
                Rule::exists('orders_items','product_id')
                    ->where(function ($query) use($request){
                        $query->where('orders_id',$request->input('orders_id'));
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

        $prod_id = $request->input('product_id');
        $order_id = $request->input('orders_id');

        $resp = $this->getProductRelServList($prod_id,$order_id);

        if($resp instanceof ApiError)
            return $resp->json();

        return response()->json([

            'response' => $resp,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

}

<?php
namespace App\Traits;

use App\Classes\ApiError;
use App\Models\Order;
use App\Models\OrderCondition;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

trait OrderTrait
{

    public function saveOrder($user,$orders_id = NULL,$prod_id,$quant){

        //Если не передан orders_id
        if(!isset($orders_id)){

            //Сначала нужно понять есть ли неоф. заказ
            $not_formed_order_bld = $user->orders()->where('status',0);

            if($not_formed_order_bld->exists()){
                //Существует ,добавляем только товар

                $order = $not_formed_order_bld->first();


            }
            else{
                //Не существует ,создаём новый
                $resp = $this->makeNewOrder($user->id);

                if($resp instanceof ApiError){
                    return $resp;
                }

                $order = $resp;
                unset($resp);
            }

        }
        else{

            //проверка прав пользователя на данный заказ
            $usr_order_bld = $user->orders()->where('id',$orders_id);

            if(!$usr_order_bld->exists()){
                $err = new ApiError(308);
                return $err;
            }

            $order = $usr_order_bld->first();

        }

        //Добавляем товар
        $resp = $this->addProductToOrder($prod_id,$quant,$order);

        if($resp instanceof ApiError){
            return $resp;
        }

        return true;

    }

    public function makeNewOrder($user_id)
    {

        $city_id = User::find($user_id)->city_id;
        //Не существует ,создаём новый
                $order = new Order;

                $order->user_id = $user_id;
                $order->status = 0;
                $order->is_accepted = 2;
                $order->type = 0;
                $order->city_id = $city_id;


        try{
            $order->save();
        }
        catch (QueryException $ex){
            $err = new ApiError(310);
            return $err;
        }

        return $order;
    }

    public function addProductToOrder($prod_id,$quant,$order)
    {

        try{
            DB::transaction(function () use($prod_id,&$quant,$order){

                $is_upd = false;

                $resp = $this->isProductInOrder($order,$prod_id);

                if($resp !== false) {
                    $prev_quant = $resp;
                    unset($resp);
                    $quant += $prev_quant;
                    $order->products()->updateExistingPivot($prod_id, ['quantity' => $quant]);
                    $is_upd = true;
                }

                if(!$is_upd)
                    $order->products()->attach($prod_id,['quantity' => $quant]);
            });
        }
        catch (QueryException $ex){
            $err = new ApiError(310);
            return $err;
        }

        return true;
    }

    /*
     * Возвращает false или количество товара
     * */
    public function isProductInOrder($order,$prod_id)
    {

        $ord_prods = $order->products();

        $piv_bld = $ord_prods->wherePivot('product_id',$prod_id);

        if($piv_bld->exists()){
            $piv = $piv_bld->first();
            $prev_quant = $piv->pivot->quantity;

            return $prev_quant;
        }

        return false;

    }

    public function addRelatedServiceToOrder($order,array $rel_serv_ids,$prod_id)
    {

        $ord_itm_bld = $order->items()->where('product_id',$prod_id);

        if(!$ord_itm_bld->exists()){
            $err = new ApiError(341,
                NULL,
                'Нет товара в заказе',
                'Нет товара в заказе');
            return $err;
        }

        $ord_itm = $ord_itm_bld->first();

        try {
            DB::transaction(function () use($rel_serv_ids,$ord_itm,$order){

                foreach ($rel_serv_ids as $rel_serv_id) {

                    //Проверка ,привязана ли уже к товару услуга в данном заказе
                    $rel_servcs_bld = $ord_itm->relatedServices()
                        ->where('related_services.id',$rel_serv_id);

                    if($rel_servcs_bld->exists())
                        continue;

                    $ord_itm->relatedServices()->attach($rel_serv_id,['orders_id' => $order->id]);
                }

            });

        }
        catch (QueryException $ex){
            $err = new ApiError(310);
            return $err;
        }

        return true;

    }



    /*
     * Возвращает false ,если не находит , модель OrderItem, если находит
     * */
    public function isOrdersProdHasRelServ($prod_id,$rel_serv_id,$order_id)
    {

        $order = Order::find($order_id);

        if(!isset($order)){
            $err = new ApiError(411,
                NULL,
                "Нет заказа",
                'Нет заказа с указанным id');
            return $err;
        }

        $items_bld = $order->items()->where('product_id',$prod_id);

        if(!$items_bld->exists()){
            $err = new ApiError(412,
                NULL,
                "Нет такого продукта в заказе",
                'Нет продукта с указанным id в данном заказе');
            return $err;
        }

        $item = $items_bld->first();

        $rel_service_bld = $item
            ->relatedServices()
            ->where('related_services_id',$rel_serv_id);

        if(!$rel_service_bld->exists()){
            return false;
        }

        return $item;

    }

    public function deleteOrderItemsRelatedServices($item,array $rel_serv_ids)
    {

        try{
            DB::transaction(function () use($item,$rel_serv_ids){

                foreach ($rel_serv_ids as $rel_serv_id)
            $item->relatedServices()->detach($rel_serv_id);

            });
        }
        catch (QueryException $ex){
            $err = new ApiError(310);
            return $err;
        }

        return true;

    }

    public function getProductRelServList($product_id,$order_id)
    {

        $order = Order::find($order_id);

        $items = $order->items()->where('product_id',$product_id)->first();

        $rel_servcs_arr  = [];

        $rel_servcs = $items->relatedServices;

        if(!$rel_servcs->isEmpty()){

            foreach ($rel_servcs as $rel_servc){

                $rel_servc_in_arr = [

                 'id' =>  $rel_servc->id,
                'name' => $rel_servc->name,
                 'description' => $rel_servc->description,

                ];

                array_push($rel_servcs_arr,$rel_servc_in_arr);

            }

        }

        return $rel_servcs_arr;

    }

    /*
     * Возвращает кол-во товара ,которое осталось или 0 ,если товар был удалён
     * */
    public function changeProductQuantity($operator,$quant,$order_id,$prod_id)
    {

        $ord_itm_bld = OrderItem::where('orders_id',$order_id)
            ->where('product_id',$prod_id);

        if(!$ord_itm_bld->exists()){
            return new ApiError(451,NULL,
                'Товар не найден',
                'Товар не найден');
        }

        $order_item = $ord_itm_bld->first();

        $prev_quant = $order_item->quantity;

        if(gettype($prev_quant) !== 'integer' || $prev_quant === 0)
            return new ApiError(452,
                NULL,
                'Ошибка сервера',
                'Неверное количество в базе данных');

        switch ($operator){

            case '+':
                $new_quant = $prev_quant + $quant;
                break;
            case '-':
                $new_quant = $prev_quant - $quant;
                break;

            default:
                return new ApiError(453,
                NULL,
                'Ошибка сервера',
                'Передан неправильный оператор');

        }

        if($new_quant <= 0){
        //Тут удаляем товар
            $resp = $this->deleteProduct($order_id,$prod_id);

            if($resp instanceof ApiError)
                return $resp;

            return 0;
        }


        $order_item->quantity = $new_quant;

        try{
            $order_item->save();
        }
        catch (QueryException $ex){
            return new ApiError(310);
        }

        return $new_quant;

    }

    public function deleteProduct($order_id,$prod_id)
    {

        $ord_itm_bld = OrderItem::where('orders_id',$order_id)
            ->where('product_id',$prod_id);

        if(!$ord_itm_bld->exists()){
            return new ApiError(451,NULL,
                'Товар не найден',
                'Товар не найден');
        }

        $resp = $this->getProductRelServList($prod_id,$order_id);

        if($resp instanceof ApiError)
            return $resp;

        $prod_rel_services = $resp;

        unset($resp);

        $rel_serv_ids = [];

        foreach ($prod_rel_services as $prod_rel_service)
            array_push($rel_serv_ids,$prod_rel_service['id']);

        $item = $ord_itm_bld->first();



        try {

            if (isset($rel_serv_ids)) {

                $resp = $this->deleteOrderItemsRelatedServices($item, $rel_serv_ids);

                if ($resp instanceof ApiError)
                    return $resp;

                unset($resp);
            }

            $order = Order::find($order_id);

            $order->items()
                ->find($item->id)
                ->delete();
        }
        catch (QueryException $ex){
            return new ApiError(310);
        }

        return true;

    }

    public function formOrder($paym_id,$delivery_id,$terms,$order_id)
    {

        $order = Order::find($order_id);

        $order->payment_id = $paym_id;
        $order->delivery_id = $delivery_id;
        $order->terms = date("Y-m-d H:i:s",$terms);
        $order->status = 1;

        try{
            $order->save();
        }
        catch (QueryException $ex){
            return new ApiError(310);
        }

        return true;
    }

    public function formOrderWithoutProduct($tax_id,$bussines_id,$is_alcho,array $services_ids=NULL,$user_id)
    {
        $resp = $this->makeNewOrder($user_id);

        if($resp instanceof ApiError)
            return $resp;

        $order = $resp;

        unset($resp);

        $ord_cond = new OrderCondition;

        $ord_cond->orders_id = $order->id;
        $ord_cond->taxes_type_id = $tax_id;
        $ord_cond->bussines_type_id = $bussines_id;
        $ord_cond->is_alcho = $is_alcho;

        try{

        $ord_cond->save();

        }
            catch (QueryException $ex){
                return new ApiError(310);
            }

        if(isset($services_ids)){

            foreach ($services_ids as $service_id){
                try{
                $ord_cond->services()->attach($service_id);
                    }
                    catch (QueryException $ex){
                        return new ApiError(310);
                    }
            }
        }

        return $order->id;

    }

}
<?php

namespace App\Http\Controllers\Bids;

use App\Classes\ApiError;
use App\Models\Bid;
use App\Models\BidResponse;
use App\Models\BidsChoose;
use App\Models\PhoneSalt;
use App\Models\Product;
use App\Models\Service;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BidsController extends Controller
{
    public function addBid(Request $request)
    {

        $required_params = ['terms','description','service_id'];

        $not_valid_param = $this->checkRequiredParams($required_params);

        if(isset($not_valid_param)){
            $err = new ApiError(305,$not_valid_param);
            return $err->json();
        }

        $service_id = $request->input('service_id');

        if(!Service::where('id',$service_id)->exists()){
            $err = new ApiError(341,
                NULL,
                'Выберите услугу',
                'Нет услуги c id - '.$service_id);
            return $err->json();
        }

        $bid = new Bid;

        if($request->exists('product_id') && $request->filled('product_id')){

            $prod_id = $request->input('product_id');

            if(!Product::where('id',$prod_id)->exists()){
                $err = new ApiError(342,
                    NULL,
                    'Товар отсутсвует',
                    'Нет товара с id - '.$prod_id);
                return $err->json();
            }

            $bid->products_id = $prod_id;
        }

        $user_id = $this->getCurrentUser($request->input('auth_token'))->id;


        $terms = $request->input('terms');

        if($terms <= time()){
            $err = new ApiError(341,
                NULL,
                'срок заявки должен быть позже текущего времени',
                'срок заявки должен быть позже текущего времени');
            return $err->json();
        }


        $bid->description = $request->input('description');
        $bid->terms = date("Y-m-d H:i:s",$terms);
        $bid->user_id = $user_id;

        $bids_choose = new BidsChoose;

        $bids_choose->service_id = $service_id;

        DB::transaction(function () use ($bid,$bids_choose){

            $bid->save();
            $bids_choose->bids_id = $bid->id;
            $bids_choose->save();


        });


        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);


    }

    public function getBidsServices(Request $request)
    {

        $required_params = ['bids_id'];

        $not_valid_param = $this->checkRequiredParams($required_params);

        if(isset($not_valid_param)){
            $err = new ApiError(305,$not_valid_param);
            return $err->json();
        }

        $bid_id = $request->input('bids_id');

        $bids_bld = Bid::where('id',$bid_id);

        if(!$bids_bld->exists()){
            $err = new ApiError(342,
                NULL,
                NULL,
                'Заявка не найдена');
            return $err->json();
        }

        $bid = $bids_bld->first();

        $services_bld = $bid->services();

        if($request->exists('type') && $request->filled('type')){

            $type = $request->input('type');

            $services_bld = $services_bld
                            ->where('type',$type);

        }

        $services = $services_bld
            ->get([
                'services.id','services.name','services.description',
                'services.type', 'services.verification'])
            ->all();

        foreach ($services as $service){
            unset($service['pivot']);
        }


        return response()->json([

            'response' => $services,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function cancelBid(Request $request)
    {

        $required_params = ['bid_id'];

        $not_valid_param = $this->checkRequiredParams($required_params);

        if(isset($not_valid_param)){
            $err = new ApiError(305,$not_valid_param);
            return $err->json();
        }

        $bid_id = $request->input('bid_id');

        $user_id = $this->getCurrentUser($request->input('auth_token'))->id;

        $bid = Bid::where('user_id',$user_id)
                ->where('id',$bid_id);

        if(!$bid->exists()){
            $err = new ApiError(341,
                NULL,
                'Заявка не найдена',
                'нет такой заявки id - '.$bid_id);
            return $err->json();
        }

        $bid = $bid->first();

        $bid->status = 5;
        $bid->save();

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function editBid(Request $request)
    {
        $required_params = ['bid_id'];

        $not_valid_param = $this->checkRequiredParams($required_params);

        if(isset($not_valid_param)){
            $err = new ApiError(305,$not_valid_param);
            return $err->json();
        }

        $user_id = $this->getCurrentUser($request->input('auth_token'))->id;

        $bid_id = $request->input('bid_id');

        $bid_bld = Bid::where('id',$bid_id);

        if(!$bid_bld->exists()){

            $err = new ApiError(341,
                NULL,
                'Заявка не найдена',
                'нет такой заявки');
            return $err->json();

        }

        $bid_bld = Bid::where('id',$bid_id)
            ->where('user_id',$user_id);

        if(!$bid_bld->exists()){

            $err = new ApiError(308);
            return $err->json();

        }

        $bid = $bid_bld->first();

        unset($bid_bld);

        if($request->has('terms') && $request->filled('terms')){

            $bid->terms = date("Y-m-d H:i:s",$request->input('terms'));

        }
        elseif ($request->has('status') && $request->filled('status')){

            if($bid->is_accepted !== 1){

                $err = new ApiError(344,
                    NULL,
                    'Ваша заявка пока не прошла модерацию',
                    'Заявка не прошла модерацию, статус не может быть изменён');
                return $err->json();

            }

            $bid->status = $request->input('status');

        }
        elseif ($request->has('description') && $request->filled('description')){

            $bid->description = $request->input('description');

        }
        elseif ($request->has('products_id') && $request->filled('products_id')){

            $prod_id = $request->input('products_id');

            if(!Product::where('id',$prod_id)->exists()){
                $err = new ApiError(343,
                    NULL,
                    'Возникла ошибка ,мы просим прощения =)',
                    'Нет такого товара - '.$prod_id);
                return $err->json();
            }

            $bid->products_id = $prod_id;

        }
        else {
            $err = new ApiError(342,
                NULL,
                'Возникла ошибка ,мы просим прощения =)',
                'Один из необязательных параметров должен быть передан');
            return $err->json();
        }

        $bid->save();

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);


    }

    public function moderateBid(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'bid_id' => 'required|integer|exists:bids,id',
            'is_accepted' => 'required|integer|between:0,1',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $bid_bld = Bid::where('id',$request->input('bid_id'))
            ->where('is_accepted',2);

        if(!$bid_bld->exists()){
            $err = new ApiError(341,
                NULL,
                NULL,
                'Заявка не найдена');

            return $err->json();
        }

        $bid = $bid_bld->first();

        $bid->is_accepted = $request->input('is_accepted');

        $bid->save();

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function repeatBid(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'bid_id' => 'required|integer|exists:bids,id',
            'terms' => 'required|integer',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

         //Проверка прав
        $user = $this->getCurrentUser($request->input('auth_token'));

        if($user->user_type !== 1){
            $err = new ApiError(308,
                NULL,
                "Только покупатель может выполнить это действие");
            return $err->json();
        }

        $bid_bld = $user->bids()
            ->where('id',$request->input('bid_id'))
            ->where(function ($query) {
                $query->where('status', 3)
                    ->orWhere('status', 5)
                    ->orWhere('status', 4);
            });

        if(!$bid_bld->exists()){
            $err = new ApiError(308);
            return $err->json();
        }

        $bid = $bid_bld->first();

        if($bid->is_accepted === 0 && ($bid->status === 4 || $bid->status === 3)){
            $err = new ApiError(343,
                NULL,
                'Заявка не принята модератором',
                'Заявка не принята модератором');
            return $err->json();
        }

        $newBid = new Bid;

        $terms = $request->input('terms');

        if($terms <= time()){
            $err = new ApiError(341,
                NULL,
                'срок заявки должен быть позже текущего времени',
                'срок заявки должен быть позже текущего времени');
            return $err->json();
        }

        $newBid->description = $bid->description;
        $newBid->user_id = $bid->user_id;



            $newBid->is_accepted = $bid->is_accepted === 1 ? 1
                : 2;

        if(isset($bid->product))
        $newBid->products_id = $bid->product->id;

        $newBid->terms = date("Y-m-d H:i:s",$terms);

        $service_id = $bid->services()->first()->id;


        if($bid->status === 4){

            $newBid->status = 2;

            $chosen_resp_bld = $bid->bidResponses()->where('status',1);

            if(!$chosen_resp_bld->exists()){
                $err = new ApiError(342,
                    NULL,
                    'Не выбран исполнитель',
                    'Не выбран исполнитель');
                return $err->json();
            }

            $chosen_resp = $chosen_resp_bld->first();

            $newResp = new BidResponse;

            $newResp->users_branches_id = $chosen_resp->users_branches_id;
            $newResp->price = $chosen_resp->price;
            $newResp->quantity = $chosen_resp->quantity;
            $newResp->quantity_type = $chosen_resp->quantity_type;
            $newResp->comment = $chosen_resp->comment;
            $newResp->status = 1;

        }
        elseif($bid->status === 3){
            $newBid->status = 1;

            $newResp = false;
        }
        elseif($bid->status === 5){

            $newBid->status = $bid->is_accepted === 1 ? 1 : 0;

            $newResp = false;

        }

        try{
            DB::transaction(function () use ($newBid,$newResp,$service_id){

                $newBid->save();

                if($newResp !== false) {
                    $newResp->bids_id = $newBid->id;
                    $newResp->save();
                }

                $newBid->services()->attach($service_id);

            });

        }
        catch (QueryException $ex){
            $err = new ApiError(310);
            return $err->json();
        }

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }
}

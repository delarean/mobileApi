<?php

namespace App\Http\Controllers\Bids;

use App\Classes\ApiError;
use App\Models\Bid;
use App\Models\BidResponse;
use App\Models\Branch;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BidsResponseController extends Controller
{

    private $default_logo_href = '/storage/3XzuBTO3seg5BChpZgf0ISf6JshIqkdFPuTwlzda.png';

    public function addResponse(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'bid_id' => 'required|integer|exists:bids,id',
            'users_branches_id' => 'required|integer|exists:users_branches,id',
            'price' => 'required|numeric',
            'quantity' => 'required|integer',
            'quantity_type' => 'required|integer|between:1,3',
            'comment' => 'required',
            ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $users_br_id = $request->input('users_branches_id');

        $user = $this->getCurrentUserModel($request->input('auth_token'));

        //Проверяем принадлежность филиала пользователю
        $brch_bld = Branch::where('id',$users_br_id)
            ->where('user_id',$user->id);

        if(!$brch_bld->exists()){
            $err = new ApiError(308);
            return $err->json();
        }

        $bid_id = $request->input('bid_id');

        $bid_status = Bid::where('id',$bid_id)
            ->get(['status'])
            ->first()
            ->status;

        if($bid_status !== 1){

            $err = new ApiError(342,
                NULL,
                'Нельзя ответить на данную заявку',
                'нельзя ответить на заявку, статус заявки - '.$bid_status);
            return $err->json();

        }

        //Проверка ,отвечал ли уже текущий пользователь
        $user_branches = $user->branches;

        foreach ($user_branches as $user_branch){

            $is_responsed = BidResponse::where('users_branches_id',$user_branch->id)
                ->where('bids_id',$bid_id)
                ->exists();

            if($is_responsed){

                $err = new ApiError(343,
                    NULL,
                    'Вы уже отвечали на данную заявку',
                    'пользователь уже отвечал на заявку');
                return $err->json();

            }

        }


        if(!$user->is_service){

            $err = new ApiError(341,
                NULL,
                'Для ответа на эту услугу надо включить опцию - Оказываю услугу',
                'не может оказывать услугу is_service = 0');
            return $err->json();

        }

        $bid_response = new BidResponse;

        $bid_response->users_branches_id = $users_br_id;
        $bid_response->price = $request->input('price');
        $bid_response->bids_id = $bid_id;
        $bid_response->quantity = $request->input('quantity');
        $bid_response->quantity_type = $request->input('quantity_type');
        $bid_response->comment = $request->input('comment');

        $bid_response->save();

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function editResponse(Request $request)
    {


        $validator = Validator::make($request->all(), [
            'response_id' => 'required|integer|exists:bids_response,id',
            'users_branches_id' => [
                'required_without:price,quantity,quantity_type,comment,status',
                'integer','exists:bids_response,users_branches_id'
            ],
            'price' => [
                'required_without:users_branches_id,quantity,quantity_type,comment,status',
                'numeric'
            ],
            'quantity' => [
                'required_without:users_branches_id,price,quantity_type,comment,status',
                'integer'
            ],
            'quantity_type' => [
                'required_without:users_branches_id,price,quantity,comment,status',
                'integer',
                'between:1,3',
            ],
            'comment' => 'required_without:users_branches_id,price,quantity,quantity_type,status',
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

        $user_branches = $user->branches();


        $bidResp_bld = BidResponse::where('id',$request->input('response_id'));

        $user_brnch_new = $request->input('users_branches_id');

        $resp_branch_id = $bidResp_bld->get(['users_branches_id'])->first()->users_branches_id;
        $is_user_brnch = $user_branches->where('id',$resp_branch_id)->exists();
        $is_user_brnch_new = $user_branches->where('id',$user_brnch_new)->exists();

        if(!$is_user_brnch){

            $err = new ApiError(308,NULL,
                'Этот ответ не ваш');
            return $err->json();

        }

        if(!$is_user_brnch_new){

            $err = new ApiError(308,NULL,
                'Вы не можете поменять на данный филиал ,он вам не принадлежит');
            return $err->json();

        }


        $bidResp = $bidResp_bld->first();



        if ($request->has('users_branches_id'))
            $bidResp->users_branches_id = $user_brnch_new;

        if ($request->has('price'))
            $bidResp->price = $request->input('price');

        if ($request->has('quantity'))
            $bidResp->quantity = $request->input('quantity');

        if ($request->has('quantity_type'))
            $bidResp->quantity_type = $request->input('quantity_type');

        if ($request->has('comment'))
            $bidResp->comment = $request->input('comment');

        $bidResp->save();

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function getBidResponsesList(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'bid_id' => 'required|integer|exists:bids,id',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $bid_id = $request->input('bid_id');

        $bid_resps_bld = BidResponse::where('bids_id',$bid_id);

        $resp_choosen = BidResponse::where('bids_id',$bid_id)->where('status',1);

        $bid_resps_bld = $bid_resps_bld->where('status','<>',2);

        if($resp_choosen->exists()){
            $bid_resps_bld = $resp_choosen;
       }

        $bid_responses = $bid_resps_bld
                    ->get([
                        'id','users_branches_id','price',
                        'quantity','quantity_type','comment',
                        'status',
                        ])
                    ->toArray();

        foreach ($bid_responses as &$bid_response){

            $bid_usr_brnch = BidResponse::find($bid_response['id'])
                ->branch()
                ->first();

            //Получение логотипа
            $user = $bid_usr_brnch->user()->first();


            $bid_response['logo_id'] = 0;
            $bid_response['logo_href'] = $this->default_logo_href;
            $logo_bld = $user->userOpt()->first();

            //ЮР лицо
            if($user->orgform === 2 && $logo_bld->exists()) {

                $logo = $logo_bld->logo;
                $bid_response['logo_id'] = $logo->id;
                $bid_response['logo_href'] = $logo->href;

                }


            $bid_response['address'] = $bid_usr_brnch->address;
            $bid_response['address_lat'] = $bid_usr_brnch->address_lat;
            $bid_response['address_lon'] = $bid_usr_brnch->address_lon;
            $bid_response['users_branches_name'] = $bid_usr_brnch->name;

            $reviews_coll = $bid_usr_brnch->reviews()
                ->where('is_accepted',1)->get();

            if(!$reviews_coll->isEmpty()){

                $bid_response['reviews_count'] = $reviews_coll->count();
                $bid_response['reviews_rating'] = $reviews_coll->avg('stars');

            }
            else {

                $bid_response['reviews_count'] = 0;
                $bid_response['reviews_rating'] = 0;

            }

        }

        return response()->json([

            'response' => $bid_responses,

        ],200,[],JSON_UNESCAPED_UNICODE);


    }

    //Выбор исполнителя в зявке

    public function selectBidResponse(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'bid_id' => 'required|integer|exists:bids,id',
            'response_id' => 'required|integer|exists:bids_response,id',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        //Проверка пользователя на принадлежность заявки
        $user = $this->getCurrentUser($request->input('auth_token'));

        $bid_id = $request->input('bid_id');

        $bid_bld = Bid::where('user_id',$user->id)
            ->where('id',$bid_id);

        $is_owner = $bid_bld
                ->exists();

        if(!$is_owner){

            $err = new ApiError(308,
                NULL,
                "Вы не можете выбирать исполнителя в данной заявке");

            return $err->json();

        }

        $bid_resp = BidResponse::find($request->input('response_id'));

        $bid = $bid_bld->first();
        $bid->status = 2;
        $bid_resp->status = 1;

        try {
            DB::transaction(function () use ($bid, $bid_resp) {
                $bid->save();
                $bid_resp->save();
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

    public function getBidResponse(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'bid_response_id' => 'required|integer|exists:bids_response,id',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $bid_resp_id = $request->input('bid_response_id');

        $bid_resp = BidResponse::find($bid_resp_id);

        $usr_branch = $bid_resp->branch()->first();

        $reponse = [];

        $reponse['users_branches_id'] = $usr_branch->id;
        $reponse['users_branches_name'] = $usr_branch->name;
        $reponse['open_hours_from'] = $this->timeToApiResponse($usr_branch->open_hours_from);
        $reponse['open_hours_to'] = $this->timeToApiResponse($usr_branch->open_hours_to);
        $reponse['users_branches_type'] = $usr_branch->type;
        $reponse['users_branches_user_id'] = $usr_branch->user_id;
        $reponse['address'] = $usr_branch->address;
        $reponse['address_lat'] = $usr_branch->address_lat;
        $reponse['address_lon'] = $usr_branch->address_lon;

        $usr_br_imgs = $usr_branch
            ->images
            ->toArray();

        $images_arr = [];

        foreach ($usr_br_imgs as $usr_br_img){
            $image_arr = [];

            $image_arr['id'] = $usr_br_img['id'];
            $image_arr['href'] = $usr_br_img['href'];

            array_push($images_arr,$image_arr);

        }

        $user = $this->getCurrentUserModel($request->input('auth_token'));

        //ЮР лицо
        if($user->orgform === 2){

            $usr_opt = $user->userOpt()->first();
            $reponse['company_description'] = $usr_opt->description;

            $logo = $usr_opt->logo;
            $reponse['logo_id'] = $logo->id;
            $reponse['logo_href'] = $logo->href;

        }
        //Физ лицо
        elseif ($user->orgform === 1){

            $reponse['logo_id'] = 0;
            $reponse['logo_href'] = $this->default_logo_href;
            $reponse['company_description'] = 'Нет описания';

        }

        $reponse['user_branch_images'] = $images_arr;

        $reviews_coll = $usr_branch->reviews()
            ->where('is_accepted',1)->get();

        if(!$reviews_coll->isEmpty()){

            $reponse['reviews_count'] = $reviews_coll->count();
            $reponse['reviews_rating'] = $reviews_coll->avg('stars');

        }
        else {

            $reponse['reviews_count'] = 0;
            $reponse['reviews_rating'] = 0;

        }


        $reponse['price'] = $bid_resp->price;
        $reponse['quantity'] = $bid_resp->quantity;
        $reponse['quantity_type'] = $bid_resp->quantity_type;
        $reponse['comment'] = $bid_resp->comment;
        $reponse['status'] = $bid_resp->status;
        $reponse['awards'] = 3;

        return response()->json([

            'response' => $reponse,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function refuseBidResponse(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bid_id' => 'required|integer|exists:bids,id',
            'response_id' => 'required|integer|exists:bids_response,id',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        //Проверка пользователя на принадлежность заявки
        $user = $this->getCurrentUser($request->input('auth_token'));

        $bid_id = $request->input('bid_id');

        $bid_bld = Bid::where('user_id',$user->id)
            ->where('id',$bid_id);

        $is_owner = $bid_bld
            ->exists();

        if(!$is_owner){

            $err = new ApiError(308,
                NULL,
                "Вы не можете выбирать исполнителя в данной заявке");

            return $err->json();

        }

        $bid_resp = BidResponse::find($request->input('response_id'));
        $bid_resp->status = 2;
        $is_saved = $bid_resp->save();

        if(!$is_saved){

            $err = new ApiError(310);

            return $err->json();

        }

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function deleteBidResponse(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'response_id' => 'required|integer|exists:bids_response,id',
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
        $bid_resp_id = $request->input('response_id');

        $bid_resp = BidResponse::find($bid_resp_id);

        $branch_id = $bid_resp->branch->id;

        $bid_resp_bld = $user->branches()
            ->find($branch_id)
            ->bidResponses()
            ->where('id',$bid_resp_id);

        $bid_resp = $bid_resp_bld->first();

        if(!$bid_resp_bld->exists()){

            $err = new ApiError(308);

            return $err->json();

        }

        if($bid_resp->status === 1){

            $bid_status = $bid_resp->bid->status;

            if($bid_status === 2 || $bid_status === 3){

                $err = new ApiError(341,
                    NULL,
                    'Вы не можете удалить предложение на данном этапе',
                    'Нельзя удалить предложение при данном статусе bid_status = '
                    .$bid_status.' ,будучи исполнителем');
                return $err->json();

            }

        }

        $is_deleted = $bid_resp->delete();

        if(!$is_deleted){
            $err = new ApiError(310);
            return $err->json();
        }

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function getBidResponseContacts(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'response_id' => 'required|integer|exists:bids_response,id',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $bid_resp = BidResponse::find($request->input('response_id'));

        $branch = $bid_resp->branch()->first();

        $resp = [];

        $resp['address'] = $branch->address;

        $user = $branch->user()->first();


        $resp['email'] = $user->email;

        $resp['phone'] = $user->phoneSalt->phone;

        return response()->json([

            'response' => $resp,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function fixBidResponse(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'response_id' => 'required|integer|exists:bids_response,id',
            'bid_id' => 'required|integer|exists:bids,id',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $bid_id = $request->input('bid_id');
        $resp_id = $request->input('response_id');

        //проверка прав пользователя

        $user = $this->getCurrentUserModel($request->input('auth_token'));

        $bid_bld = $user->bids()
            ->where('id',$bid_id);

        if(!$bid_bld->exists()){
            $err = new ApiError(308);
            return $err->json();
        }

        //проверка на наличие данного ответа у заявки
        $bid = $bid_bld->first();

        $bid_resp_bld = $bid->bidResponses()
            ->where('id',$resp_id);

        if(!$bid_resp_bld->exists()){
            $err = new ApiError(341,
                NULL,
                'Предложение не найдено у данной заявки',
                'Предложение не найдено у данной заявки');
            return $err->json();
        }

        $bid_resp = $bid_resp_bld->first();

        $usr_br_id = $bid_resp->users_branches_id;


        //проверка на наличие других исполнителей
        $is_choosed_resp = $bid_resp_bld->where('status',1)
            ->where('users_branches_id','<>',$usr_br_id)
            ->exists();

        if($is_choosed_resp){
            $err = new ApiError(342,
                NULL,
                'Уже выбран другой исполнитель',
                'Уже выбран другой исполнитель');
            return $err->json();
        }


        //закрепляем
        $bid->status = 4;
        $bid_resp->status = 1;

        try {
            DB::transaction(function () use ($bid, $bid_resp) {
                $bid->save();
                $bid_resp->save();
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

    public function unfixBidResponse(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'response_id' => 'required|integer|exists:bids_response,id',
            'bid_id' => 'required|integer|exists:bids,id',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $bid_id = $request->input('bid_id');
        $resp_id = $request->input('response_id');

        //проверка прав пользователя

        $user = $this->getCurrentUserModel($request->input('auth_token'));

        $bid_bld = $user->bids()
            ->where('id',$bid_id);

        if(!$bid_bld->exists()){
            $err = new ApiError(308);
            return $err->json();
        }

        //проверка на наличие данного ответа у заявки
        $bid = $bid_bld->first();

        $bid_resp_bld = $bid->bidResponses()->where('id',$resp_id);

        if(!$bid_resp_bld->exists()){
            $err = new ApiError(342,
                NULL,
                'Предложение не найдено у данной заявки',
                'Предложение не найдено у данной заявки');
            return $err->json();
        }


        $bid_resp = $bid_resp_bld->first();

        if($bid->status !== 4 || $bid_resp->status !== 1){
            $err = new ApiError(341,
                NULL,
                ' Нельзя открепить - не является исполнителем/не прикреплён',
                ' Нельзя открепить - не является исполнителем/не прикреплён');
            return $err->json();
        }

        //открепляем
        $bid->status = 3;

        try {
            DB::transaction(function () use ($bid, $bid_resp) {
                $bid->save();
                $bid_resp->save();
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

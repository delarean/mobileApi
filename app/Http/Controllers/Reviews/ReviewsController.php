<?php

namespace App\Http\Controllers\Reviews;

use App\Classes\ApiError;
use App\Models\BidResponse;
use App\Models\BidReview;
use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderResponse;
use App\Models\OrderReview;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReviewsController extends Controller
{

    public function addReview(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'users_branches_id' => 'required|integer|exists:users_branches,id',
            'stars' => 'required|integer|between:1,5',
            'description' => 'required',
            'bid_id' => 'required_without:order_id|integer|exists:bids,id',
            'order_id' => 'required_without:bid_id|integer|exists:orders,id',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $br_id = $request->input('users_branches_id');

        //Проверка прав

        $user = $this->getCurrentUserModel($request->input('auth_token'));

        if($request->has('bid_id')){


            $review_type = 2;

            $bid_id = $request->input('bid_id');

            $bid_review_bld = BidReview::where('bid_id',$bid_id);



            $review_id = $bid_review_bld->exists() ? $bid_review_bld->first()->review_id
                : NULL;

            $bid_bld = $user->bids()->where('id',$bid_id)
                            ->where('status',3)
                            ->orWhere('status',4);

            if(!$bid_bld->exists()){

                $err = new ApiError(308,
                    NULL,
                    "Нельзя оставить отзыв(нет прав/заказ не выполнен)");
                return $err->json();

            }

            //Проверка ,является ли исполнителем
            $bid_resp_bld = BidResponse::where('users_branches_id',$br_id)
                        ->where('bids_id',$bid_id)
                        ->where('status',1);

            if(!$bid_resp_bld->exists()){

                $err = new ApiError(308,
                    NULL,
                    "Нельзя оставить отзыв(нет предложения/филиал не исполнитель)");
                return $err->json();

            }


            $pivot = new BidReview;

            $pivot->bid_id = $bid_id;

        }
        elseif ($request->has('order_id')){

            $review_type = 1;

        $order_id = $request->input('order_id');

            $order_review_bld = OrderReview::where('order_id',$order_id);

            $review_id = $order_review_bld->exists() ? $order_review_bld->first()->review_id
                : NULL;

        $order_bld = $user->orders()->where('id',$order_id)
                            ->where('status',3);

        if(!$order_bld->exists()){

            $err = new ApiError(308);
            return $err->json();

        }

        //Проверка ,является ли исполнителем
            $order_resp_bld = OrderResponse::where('users_branches_id',$br_id)
                ->where('orders_id',$order_id)
                ->where('status',1);

            if(!$order_resp_bld->exists()){

                $err = new ApiError(308,
                    NULL,
                    "Нельзя оставить отзыв(нет предложения/филиал не исполнитель)");
                return $err->json();

            }

            $pivot = new OrderReview;

            $pivot->order_id = $order_id;
    }

        $review_bld = Review::where('id',$review_id)
            ->where('is_accepted','<>',0);

        if($review_bld->exists()){

            $err = new ApiError(341,
                NULL,
                "Вы уже оставили отзыв",
                "Отзыв уже оставлен");

            return $err->json();

        }

        $review = new Review;

        $review->users_branches_id = $br_id;
        $review->user_id = $user->id;
        $review->stars = $request->input('stars');
        $review->description = $request->input('description');
        $review->type = $review_type;

        try {

            DB::transaction(function () use ($pivot,$review){

                $review->save();

                $pivot->review_id = $review->id;

                $pivot->save();

            });

        } catch (QueryException $ex){
            $err = new ApiError(310);
            return $err->json();
        }

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);


    }

    public function getReviewsList(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'users_branches_id' => 'required|integer|exists:users_branches,id',
            'type' => 'integer|between:1,2',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $branch_id = $request->input('users_branches_id');

        $review_bld = Branch::find($branch_id)
            ->reviews();


        if($request->has('type')){

            $type = $request->input('type');

            $review_bld = $review_bld->where('type',$type)
                ->where('is_accepted',1);

        }

        $reviews_arr = $review_bld->get([
            'id','stars','user_id',
            'description','created_at','updated_at',
        ])
            ->toArray();

        foreach ($reviews_arr as &$review){

            $user = User::find($review['user_id']);

            $review['user_name'] = $user->name;
            $review['user_city_name'] = $user->city->name;
            $review['created_at'] = strtotime($review['created_at']);
            $review['updated_at'] = strtotime($review['updated_at']);

        }

        return response()->json([

            'response' => $reviews_arr,

        ],200,[],JSON_UNESCAPED_UNICODE);


    }

    public function getReview(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'review_id' => 'required|integer|exists:reviews,id',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $review_bld = Review::where('id',$request->input('review_id'));

        $reviews_arr = $review_bld->get([
            'id','stars','user_id',
            'description','created_at','updated_at',
        ])
            ->first()
            ->toArray();



            $user = User::find($reviews_arr['user_id']);

        $reviews_arr['user_name'] = $user->name;
        $reviews_arr['user_city_name'] = $user->city->name;
        $reviews_arr['created_at'] = strtotime($reviews_arr['created_at']);
        $reviews_arr['updated_at'] = strtotime($reviews_arr['updated_at']);

        return response()->json([

            'response' => $reviews_arr,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function editReview(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'review_id' => 'required|integer|exists:reviews,id',
            'stars' => 'required_without_all:description|integer|between:1,5',
            'description' => 'required_without_all:stars',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        //Проверка прав пользователя
        $user = $this->getCurrentUserModel($request->input('auth_token'));

        $review_id = $request->input('review_id');

        $review_bld = $user->reviews()->where('id',$review_id);

        if(!$review_bld->exists()){

            $err = new ApiError(308);

            return $err->json();

        }

        $review = $review_bld->first();

        if($request->has('stars'))
            $review->stars = $request->input('stars');

        if($request->has('description'))
            $review->description = $request->input('description');

        $is_saved = $review->save();

        if(!$is_saved){
            $err = new ApiError(310);
            return $err->json();
        }


        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function moderateReview(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'review_id' => 'required|integer|exists:reviews,id',
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

        $review_bld = Review::where('id',$request->input('review_id'))
            ->where('is_accepted',2);

        if(!$review_bld->exists()){
            $err = new ApiError(341,
                NULL,
                NULL,
                'Отзыв не найден');

            return $err->json();
        }

        $review = $review_bld->first();

        $review->is_accepted = $request->input('is_accepted');

        $review->save();

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

}

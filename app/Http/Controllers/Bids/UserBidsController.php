<?php

namespace App\Http\Controllers\Bids;

use App\Classes\ApiError;
use App\Models\Bid;
use App\Models\BidReview;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UserBidsController extends Controller
{

    public function getUserBidsList(Request $request)
    {

        $user = $this->getCurrentUserModel($request->input('auth_token'));

        $bids_bld = $user->bids();

        if($request->has('bids_status') && $request->filled('bids_status')){

            $bids_bld = $bids_bld->where('status',$request->input('bids_status'));

        }

        $bids_arr = $bids_bld
            ->get([
                'id','terms','created_at',
                'updated_at','status','is_accepted',
                'description',
            ])
            ->toArray();


        foreach ($bids_arr as &$bid){

            if(isset($bid['created_at']))
                $bid['created_at'] = strtotime($bid['created_at']);

            if(isset($bid['updated_at']))
                $bid['updated_at'] = strtotime($bid['updated_at']);

            $bid['terms'] = strtotime($bid['terms']);

            $service_bld = Bid::where('id',$bid['id'])
                ->first()
                ->services;

            $service = $service_bld->first();


                $bid['service_id'] = isset($service) ? $service->id : NULL;
                $bid['service_name'] = isset($service) ? $service->name : NULL;
        }

        unset($bid);

        return response()->json([

            'response' => $bids_arr,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function getBid(Request $request)
    {

        $required_params = ['bid_id'];

        $not_valid_param = $this->checkRequiredParams($required_params);

        if(isset($not_valid_param)){
            $err = new ApiError(305,$not_valid_param);
            return $err->json();
        }


        $bid_id = $request->input('bid_id');

        if(gettype(+$bid_id) !== "integer"){
            $err = new ApiError(307,'bid_id');
            return $err->json();
        }

        $bid_bld = Bid::where('id',$bid_id);

        if(!$bid_bld->exists()){
            $err = new ApiError(341,
                NULL,
                'Заявка не найдена',
                'нет такой заявки');
            return $err->json();
        }

        $bid = $bid_bld
            ->first();

        $services_arr = $bid
            ->services
            ->first()
            ->toArray();

        $product_bld = $bid
            ->product;

        $bids_arr = $bid_bld
            ->get([
                'id','terms', 'products_id',
                 'description','created_at',
                'updated_at','status','is_accepted'
                ])
            ->first()
            ->toArray();

        $bids_arr['services_id'] = $services_arr['id'];
        $bids_arr['services_name'] = $services_arr['name'];
        $bids_arr['product_name'] = isset($product_bld) ? $product_bld->name
            : NULL;


        $bids_arr['created_at'] = strtotime($bids_arr['created_at']);
        $bids_arr['terms'] = strtotime($bids_arr['terms']);
        $bids_arr['updated_at'] = strtotime($bids_arr['updated_at']);

        $bid = $bid_bld->first();

        if($bid->status === 3 || $bid->status === 4){

            $bid_resp_executer = $bid->bidResponses()
                ->where('status',1)
                ->first();

                $bids_arr['chosen_response_id'] = $bid_resp_executer->id;

            $branch_reviews_bld = $bid_resp_executer
                ->branch()
                ->first()
                ->reviews()
                ->where('is_accepted','<>',0);

            $is_review = 1;

            if($branch_reviews_bld->exists()){

                $br_reviews = $branch_reviews_bld->cursor();

                foreach ($br_reviews as $br_review){

                    $has_review = BidReview::where('review_id',$br_review->id)
                        ->where('bid_id',$bid_id)
                        ->exists();

                    if($has_review)
                        $is_review = !$has_review;

                }

            }

            $bids_arr['is_review'] = $is_review ? 1 : 0;


        }


        return response()->json([

            'response' => $bids_arr,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

}

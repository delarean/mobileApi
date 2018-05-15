<?php

namespace App\Http\Controllers\Service;

use App\Classes\ApiError;
use App\Models\BidsChoose;
use App\Models\Service;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ServiceController extends Controller
{
    public function addService(Request $request)
    {

        $required_params = ['service_name','description','type'];

        $not_valid_param = $this->checkRequiredParams($required_params);

        if(isset($not_valid_param)){
            $err = new ApiError(305,$not_valid_param);
            return $err->json();
        }

        $service = new Service;

        $service->name = $request->input('service_name');
        $service->description = $request->input('description');
        $service->type = $request->input('type');

        if($request->exists('verification') && $request->filled('verification')){
            $service->verification = $request->input('verification');
        }

        $service->save();

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function getServicesList(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'type' => 'integer|between:0,1|filled',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $services = new Service;

        if($request->has('type')){

            $services = $services->where('type',$request->input('type'));

        }

            $services_arr = $services->get(['id','name','description','type','verification'])->all();

        return response()->json([

            'response' => $services_arr,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function deleteService(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'service_id' => 'required|integer|exists:services,id',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $service_id = $request->input('service_id');

        $bids_choose = BidsChoose::where('service_id',$service_id)->cursor();

        $service = Service::find($service_id);

        try {

        DB::transaction(function () use($bids_choose,$service){



        foreach ($bids_choose as $bid_choose){


            $bid_choose->delete();

        }

                $service->delete();

        });

        }
            catch (QueryException $ex) {
                $err = new ApiError(310);
                return $err->json();
            }


        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function getService(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'service_id' => 'required|integer|exists:services,id',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $serv = Service::find($request->input('service_id'));

        $resp = [
            'id' => $serv->id,
            'name' => $serv->name,
            'description' => $serv->description,
            'type' => $serv->type,
            'verification' => $serv->verification,
        ];

        return response()->json([

            'response' => $resp,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

}

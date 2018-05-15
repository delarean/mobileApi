<?php

namespace App\Http\Controllers\Delivery;

use App\Classes\ApiError;
use App\Models\Delivery;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;


class DeliveryController extends Controller
{

    public function addMethod(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'unique:delivery_method,name'
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

        $delivery = new Delivery;

        $delivery->name = $request->input('name');

        try{
            $delivery->save();
        }
        catch (QueryException $ex){
            $err = new ApiError(310);

            return $err->json();
        }

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function getMethodsList()
    {

        $delivery_methods = Delivery::where('is_deleted',0)->get();

        $response = [];

        foreach ($delivery_methods as $delivery_method){

            $method_in_arr = [
                'id' => $delivery_method->id,
                'name' => $delivery_method->name,
            ];

            $response[] = $method_in_arr;

        }

        return response()->json([

            'response' => $response,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function getMethod(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'delivery_method_id' => [
                'required',
                'integer',
                'exists:delivery_method,id'
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

        $dev_meth_id = $request->input('delivery_method_id');

        $delivery_method = Delivery::find($dev_meth_id);

        $response = [
            'id' => $delivery_method->id,
            'name' => $delivery_method->name,
        ];


        return response()->json([

            'response' => $response,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

}

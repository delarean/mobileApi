<?php

namespace App\Http\Controllers\Payments;

use App\Classes\ApiError;
use App\Models\Payment;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class PaymentsController extends Controller
{

    public function addMethod(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'unique:payment_method,name'
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

        $payment = new Payment();

        $payment->name = $request->input('name');

        try{
            $payment->save();
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

        $payment_methods = Payment::where('is_deleted',0)->get();

        $response = [];

        foreach ($payment_methods as $payment_method){

            $method_in_arr = [
                'id' => $payment_method->id,
                'name' => $payment_method->name,
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
            'payment_method_id' => [
                'required',
                'integer',
                'exists:payment_method,id'
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

        $paym_meth_id = $request->input('payment_method_id');

        $payment_method = Payment::find($paym_meth_id);

        $response = [
            'id' => $payment_method->id,
            'name' => $payment_method->name,
        ];


        return response()->json([

            'response' => $response,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }




}

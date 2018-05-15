<?php

namespace App\Http\Controllers\RelatedServices;

use App\Classes\ApiError;
use App\Models\RelatedService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class RelatedServicesController extends Controller
{

    public function addRelatedService(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:related_services,name',
            'description' => 'required|max:250',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $rel_service = new RelatedService;

        $rel_service->name = $request->input('name');
        $rel_service->description = $request->input('description');

        try{
            $rel_service->save();
        }
        catch (QueryException $ex){
            $err = new ApiError(310);
            return $err->json();
        }

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function editRelatedService(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'related_services_id' => 'required|integer|exists:related_services,id',
            'name' => 'required_without:description|unique:related_services,name',
            'description' => 'required_without:name|max:250',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $rel_serv_id = $request->input('related_services_id');

        $rel_serv = RelatedService::find($rel_serv_id);

        $change_params = ['name','description'];

        foreach ($change_params as $change_param){
            if($request->has($change_param))
                $rel_serv->$change_param = $request->input($change_param);
        }

        try{
            $rel_serv->save();
        }
        catch (QueryException $ex){
            $err = new ApiError(310);
            return $err->json();
        }

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function deleteRelatedService(Request $request){

        $validator = Validator::make($request->all(), [
            'related_services_id' => 'required|integer|exists:related_services,id',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $rel_serv_id = $request->input('related_services_id');

        $rel_serv = RelatedService::find($rel_serv_id);

        $rel_serv->is_deleted = 1;

        try{
            $rel_serv->save();
        }
        catch (QueryException $ex){
            $err = new ApiError(310);
            return $err->json();
        }

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function getRelatedService(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'related_services_id' => 'required|integer|exists:related_services,id',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $rel_serv_id = $request->input('related_services_id');

        $rel_serv = RelatedService::find($rel_serv_id);

        $response = [
            'id' => $rel_serv->id,
            'name' => $rel_serv->name,
            'description' => $rel_serv->description,
        ];

        return response()->json([

            'response' => $response,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

}

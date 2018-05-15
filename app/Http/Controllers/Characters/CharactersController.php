<?php

namespace App\Http\Controllers\Characters;

use App\Classes\ApiError;
use App\Models\Character;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class CharactersController extends Controller
{

    public function addCharacter(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:characters,name',
            'unit' => 'required|string',
            'description' => 'required',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $character = new Character;

        $character->name = $request->input('name');
        $character->unit = $request->input('unit');
        $character->description = $request->input('description');

        $is_saved = $character->save();

        if(!$is_saved){
            $err = new ApiError(310);
            return $err->json();
        }

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function editCharacter(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'character_id' => 'required|exists:characters,id',
            'name' => 'required_without_all:unit,description|unique:characters,name',
            'unit' => 'required_without_all:name,description|string',
            'description' => 'required_without_all:name,unit',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $character = Character::find($request->input('character_id'));

        $change_params = ['name','unit','description'];

        foreach ($change_params as $change_param){
            if($request->has($change_param))
                $character->$change_param = $request->input($change_param);
        }

        $is_saved = $character->save();

        if(!$is_saved){
            $err = new ApiError(310);
            return $err->json();
        }

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function deleteCharacter(Request $request){

        $validator = Validator::make($request->all(), [
            'character_id' => 'required|exists:characters,id',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $character = Character::find($request->input('character_id'));

        $character->is_deleted = 1;

        $is_saved = $character->save();

        if(!$is_saved){
            $err = new ApiError(310);
            return $err->json();
        }

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function getCharactersList(Request $request)
    {

        $characters = Character::where('is_deleted',0)
            ->get(['id','name','unit','description'])->toArray();

        return response()->json([

            'response' => $characters,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function getCharacter(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'character_id' => 'required|exists:characters,id',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $character = Character::find($request->input('character_id'))
            ->get(['id','name','unit','description'])
            ->toArray();

        return response()->json([

            'response' => $character,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

}

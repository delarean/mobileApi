<?php

namespace App\Http\Controllers\User;

use App\Classes\ApiError;
use App\Models\Branch;
use App\Models\BranchImage;
use App\Models\Image;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PartnerBranchesController extends Controller
{

    public function newBranch(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'type' => 'required|integer|between:1,2',
            'name' => 'required',
            'open_hours_from' => [
                'required','regex:/^([0-1][0-9]|[2][0-3]):([0-5][0-9])$/si',
            ],
            'open_hours_to' => [
                'required','regex:/^([0-1][0-9]|[2][0-3]):([0-5][0-9])$/si',
            ],
            'address' => 'required',
            'city_id' => 'required|integer|exists:cities,id',
            'address_lat' => [
                'required','regex:^-?\d{2}\.\d{6}$^',
            ],
            'address_lon' => [
                'required','regex:^-?\d{2}\.\d{6}$^',
                ]
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }


        $user = $this->getCurrentUser($request->input('auth_token'));


        if($user->user_type !== 2){
            $err = new ApiError(308);
            return $err->json();
        }

        $br_type = +$request->input('type');

        /*if(gettype($br_type) != "integer" || ($br_type !== 1 && $br_type !== 0)){
            $err = new ApiError(307,'type');
            return $err->json();
        }*/

        if($br_type === 1 && +$user->is_service === 0){

            $err = new ApiError(341,
                NULL,
                'Нужно отметить ,что вы занимаетесь сервисом',
                "партнёр не может заниматься сервисом");
            return $err->json();

        }

        if($br_type === 2 && +$user->is_service === 0){

            $err = new ApiError(342,
                NULL,
                'Нужно отметить ,что вы занимаетесь продажами',
                "партнёр не может заниматься продажей");
            return $err->json();

        }

        $branch = new Branch;

        $branch->type = $br_type;
        $branch->user_id = $user->id;
        $branch->name = $request->input('name');
        $branch->open_hours_from = $request->input('open_hours_from').':00';
        $branch->open_hours_to = $request->input('open_hours_to').':00';
        $branch->address = $request->input('address');
        $branch->city_id = $request->input('city_id');
        $branch->address_lat = $request->input('address_lat');
        $branch->address_lon = $request->input('address_lon');

        $branch->save();


        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function addBranchPhotos(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:users_branches,id',
            'branch_images' => 'required|array',
            'branch_images.*' => 'required|integer|exists:images,id',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        //Проверка принадлежности филиала пользователю
        $user = $this->getCurrentUser($request->input('auth_token'));

        $user_id = $user->id;

        $br_id = $request->input('branch_id');

        $is_owned = Branch::where('user_id',$user_id)
                ->where('id',$br_id)
                ->exists();

        if(!$is_owned){
            $err = new ApiError(308);
            return $err->json();
        }

        /*
         * Проверка картинок на принадлежность пользоваетелю
         * и прохождения модерации
        */

        $images_ids = $request->input('branch_images');

        $images_bld = Image::whereIn('id',$images_ids);

        $images_arr = $images_bld
                ->get()
                ->toArray();

        foreach ($images_arr as $image){

            if($image['is_accepted'] !== 1){
                $err = new ApiError(341,
                    NULL,
                    'Одно из изображений не прошло модерацию',
                    'Изображение id = '.$image['id'].' не прошло модерацию');
                return $err->json();
            }

            if($image['owner_id'] !== $user_id){
                $err = new ApiError(342,
                    NULL,
                    'У вас нет прав на одно из изображений',
                    'Изображение id = '.$image['id'].' не принадлежит пользователю id = '.$user_id);
                return $err->json();
            }

        }

        try {

            DB::transaction(function () use ($images_arr, $br_id) {


                foreach ($images_arr as $image) {

                    $br_img = new BranchImage;

                    $br_img->image_id = $image['id'];

                    $br_img->users_branches_id = $br_id;

                    $br_img->save();

                }


            });
        } catch (QueryException $ex){
            $err = new ApiError(310);
            return $err->json();
        }

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function deleteBranchPhoto(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'image_id' => 'required|integer|exists:images,id',
            'branch_id' => 'required|integer|exists:users_branches,id',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $image_id = $request->input('image_id');
        $br_id = $request->input('branch_id');

        //Проверка прав

        //На СЦ
        $user = $this->getCurrentUserModel($request->input('auth_token'));

        $brchs_bld = $user
                ->branches()
                ->where('users_branches.id',$br_id);

        if(!$brchs_bld->exists()){
            $err = new ApiError(308,
                NULL,
                'Вы не можете редактировать данный филиал');
            return $err->json();
        }

        //На фотографию
        $is_img_owner = $user->images()
                ->where('images.id',$image_id)
                ->exists();

        if(!$is_img_owner){

            $err = new ApiError(308,
                NULL,
                'Вы не владелец данной картинки');
            return $err->json();

        }

        $br_img_bound = BranchImage::where('users_branches_id',$br_id)
                        ->where('image_id',$image_id)
                        ->first();

        $is_deleted = $br_img_bound->delete();

        if(!$is_deleted){
            $err = new ApiError(310);
            return $err->json();
        }

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function editBranch(Request $request){

        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|integer|exists:users_branches,id',

            'name' => 'required_without_all:open_hours_from,
            open_hours_to,city_id,address,address_lat,address_lon',

            'open_hours_from' => [
                'required_without_all:name,
            open_hours_to,city_id,address,address_lat,address_lon',
                'regex:/^([0-1][0-9]|[2][0-3]):([0-5][0-9])$/si',
            ],

            'open_hours_to' => [
                'required_without_all:name,
            open_hours_from,city_id,address,address_lat,address_lon',
                'regex:/^([0-1][0-9]|[2][0-3]):([0-5][0-9])$/si',
            ],

            'city_id' => 'required_without_all:open_hours_from,
            open_hours_to,name,address,address_lat,address_lon|
            integer|exists:cities,id',

            'address' => 'required_without_all:open_hours_from,
            open_hours_to,name,city_id,address_lat,address_lon',

            'address_lat' => [
                'required_without_all:open_hours_from,
            open_hours_to,city_id,address,name,address_lon',
                'regex:^-?\d{2}\.\d{6}$^',
            ],

            'address_lon' => [
                'required_without_all:open_hours_from,
            open_hours_to,city_id,address,name,address_lat',
                'regex:^-?\d{2}\.\d{6}$^',
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

        $brnch_id = $request->input('branch_id');

        //Проверка прав
        $user = $this->getCurrentUserModel($request->input('auth_token'));

        $brnch_bld = $user->branches()
            ->where('id',$brnch_id);

        if(!$brnch_bld->exists()){

            $err = new ApiError(308);

            return $err->json();

        }

        $branch = $brnch_bld->first();

        if($request->has('name'))
        $branch->name = $request->input('name');

        if($request->has('open_hours_from'))
            $branch->open_hours_from =$request->input('open_hours_from');

        if($request->has('open_hours_to'))
            $branch->open_hours_to = $request->input('open_hours_to');

        if($request->has('city_id'))
            $branch->city_id = $request->input('city_id');

        if($request->has('address'))
            $branch->address = $request->input('address');

        if($request->has('address_lat'))
            $branch->address_lat = $request->input('address_lat');

        if($request->has('address_lon'))
            $branch->address_lon = $request->input('address_lon');

        $is_saved = $branch->save();

        if(!$is_saved){
            $err = new ApiError(310);
            return $err->json();
        }

        return response()->json([

            'response' => 1,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

}

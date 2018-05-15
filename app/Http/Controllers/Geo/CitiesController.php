<?php

namespace App\Http\Controllers\Geo;

use App\Classes\ApiError;
use App\Classes\Point;
use App\Models\City;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CitiesController extends Controller
{

    public function getCities(Request $request)
    {


        if($request->exists('search') && $request->filled('search')){


            $name_srch_str = $request->input('search');


            $city_bld = City::where( function ( $q ) use ( $name_srch_str ) {
                $q->whereRaw('LOWER(`name`) like ?', $name_srch_str.'%');
            });

            //$city_bld = City::where('name','like',$name_srch_str.'%');

            }
        elseif($request->exists('city_id') && $request->filled('city_id')){

            $inp_id = $request->input('city_id');

            $city_bld = City::where('id',$inp_id);

            if(!$city_bld->exists()){
                $err = new ApiError(311,
                    NULL,
                    "Город не найден",
                    "Город с переданным id отсутствует");
                return $err->json();
            }

        }
            else {

                $city_bld = new City;

            }

        $cities = $city_bld->get([
            'id','name','center_lat','center_lon','utc_offset'
        ]);

        $cities_arr = [];

        foreach ($cities as $city){

            $city_data = [
                    'id' => $city->id,
                'name' => $city->name,
                'center_lat' => $city->center_lat,
                'center_lon' => $city->center_lon,
                'utc_offset' => $city->utc_offset,

            ];

            array_push($cities_arr,$city_data);
        }

        return response()->json([
            'response' =>
            $cities_arr
            ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function nearestCity(Request $request)
    {

        $required_params = ['lat','lon'];

        $not_valid_param = $this->checkRequiredParams($required_params);

        if(isset($not_valid_param)){
            $err = new ApiError(305,$not_valid_param);
            return $err->json();
        }

        $lat = $request->input('lat');
        $lon = $request->input('lon');

        $point = new Point($lat,$lon);


        $cities = City::where('id','<>','0')
            ->get(['id','center_lat','center_lon']);

        $curNearestCityPoint = new Point($cities[0]->center_lat,$cities[0]->center_lon);
        $curNearestCity = $cities[0];
        $curNearestDistance = $point->distanceTo($curNearestCityPoint);

        foreach ($cities as $city){

            $distance = $point->distanceTo(new Point($city->center_lat,$city->center_lon));
            if ($distance < $curNearestDistance) {
                $curNearestDistance = $distance;
                $curNearestCity = $city;
            }

        }

        $nearest_city_id = $curNearestCity->id;

        if(!isset($lat) && !isset($lon) || ($lat == '0.000000' || $lon == '0.000000')){
            $nearest_city_id = 2;
        }


        $nearestCity = City::where('id',$nearest_city_id)->get(['utc_offset','name','id','center_lat','center_lon'])->first();


            $id = $nearestCity->id ?? 0;
            $name = $nearestCity->name ?? 0;
            $center_lat = $nearestCity->center_lat ?? 0;
            $center_lon = $nearestCity->center_lon ?? 0;
            $utc_offset = $nearestCity->utc_offset ?? 0;

        return response()->json([
'response' => [
            'id' => $id,
            'name' => $name,
            'center_lat' => $center_lat,
            'center_lon' => $center_lon,
            'utc_offset' => $utc_offset,
        ],
        ],200,[],JSON_UNESCAPED_UNICODE);

    }

}

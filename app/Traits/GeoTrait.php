<?php
namespace App\Traits;

use App\Classes\ApiError;
use Ixudra\Curl\Facades\Curl;

trait GeoTrait
{

//получение координат по адресу
    public function getApiAddressCoords(string $address)
    {


        $response = Curl::to('https://geocode-maps.yandex.ru/1.x/')
            ->withData([
                'geocode' => $address,
                'format' => 'json',
                'kind' => 'house'
            ])
            ->get();

        $response = json_decode($response,true);

        $found_json_path = [
            'response','GeoObjectCollection','metaDataProperty',
            'GeocoderResponseMetaData','found',
        ];



        $i = 0;

        foreach ($found_json_path as $item){
            if($i === 0){
                $response_found = $response;
            }

            if(!isset($response_found[$item])){
                $err = new ApiError(347,
                    NULL,
                    'Адрес не найден',
                    'Ошибка в работе со сторонним  api по поиску координат');
                return $err;
            }

            $response_found = $response_found[$item];

            if($item === 'GeoObjectCollection')
                $resp_geo_coll = $response_found;

            $i++;
        }

        unset($resp,$item,$found_json_path);

        if($response_found == '0'){
            if(!isset($resp)){
                $coords_not_found_err = new ApiError(346,
                    NULL,
                    'Адрес не найден',
                    'Координаты по данному адресу не найдены - '.$address);
                return $coords_not_found_err;
            }
        }

        $resp_coords_path = [
            'featureMember', 0,
            'GeoObject','Point','pos'
        ];

        $response_coords = $resp_geo_coll;

        foreach ($resp_coords_path as $item){


            if(!isset($response_coords[$item])){
                $err = new ApiError(347,
                    NULL,
                    'Адрес не найден',
                    'Ошибка в работе со сторонним  api по поиску координат(поиск координат)');
                return $err;
            }

            $response_coords = $response_coords[$item];
        }

        $exploded_coords = explode(' ',$response_coords);

        return [
            'lon' => $exploded_coords[0],
            'lat' => $exploded_coords[1],
        ];

    }

}

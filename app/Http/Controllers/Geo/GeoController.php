<?php

namespace App\Http\Controllers\Geo;

use App\Classes\ApiError;
use App\Traits\GeoTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Location\Coordinate;
use Location\Distance\Vincenty;

class GeoController extends Controller
{
    use GeoTrait;

    public function getDistance(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'lat1' => [
                'required','regex:^-?\d{2}\.\d{6}$^'
            ],
            'lon1' => [
                'required','regex:^-?\d{2}\.\d{6}$^'
            ],
            'lat2' => [
                'required','regex:^-?\d{2}\.\d{6}$^'
            ],
            'lon2' => [
                'required','regex:^-?\d{2}\.\d{6}$^'
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

        $lat1 = $request->input('lat1');
        $lon1 = $request->input('lon1');
        $lat2 = $request->input('lat2');
        $lon2 = $request->input('lon2');

        $coordinate1 = new Coordinate($lat1, $lon1);
        $coordinate2 = new Coordinate($lat2, $lon2);

        $calculator = new Vincenty();

        return response()->json([

            'response' => [
                'distance' => $calculator->getDistance($coordinate1, $coordinate2),
            ],

        ],200,[],JSON_UNESCAPED_UNICODE);

    }

    public function getAddressCoords(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'address' => 'required|string',
        ]);

        if($validator->fails()){

            $val_err = $validator->errors();

            $err = new ApiError(299,
                NULL,
                NULL,
                $val_err->all());

            return $err->json();

        }

        $address = $request->input('address');

        $resp = $this->getApiAddressCoords($address);

        if($resp instanceof ApiError)
            return $resp->json();

        return response()->json([

            'response' => $resp,

        ],200,[],JSON_UNESCAPED_UNICODE);

    }
}

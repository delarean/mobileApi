<?php

use Illuminate\Http\Request;
use \Illuminate\Support\Facades\Input;
use \Illuminate\Support\Facades\DB;
use Ixudra\Curl\Facades\Curl;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


    Route::middleware(['auth.api.dev'])->prefix('v1')->group(function () {

        Route::prefix('geo')->group(function (){

            Route::get('cities','Geo\CitiesController@getCities');

            Route::get('nearestCity','Geo\CitiesController@nearestCity');

            Route::get('distance','Geo\GeoController@getDistance');

            Route::get('addressCoords','Geo\GeoController@getAddressCoords');
        });

        Route::prefix('user')->group(function (){

            Route::post('sendSmsCode', 'User\SMSController@sendSmsCode');

            Route::post('checkSmsBalance', 'User\SMSController@checkSmsBalance');

            Route::post('authBySms', 'User\SMSController@authBySms');

            Route::middleware(['auth.api.user'])->group(function (){

                Route::post('checkAuthToken', 'User\RegistrationController@checkAuthToken');
                Route::post('regUser', 'User\RegistrationController@regUser');
                Route::post('info', 'User\UserDataController@getInfo');

            });

            Route::prefix('partner')->group(function (){

                Route::prefix('branches')->group(function (){

                    Route::middleware(['auth.api.user','auth.api.user.reged'])->group(function (){
                        Route::post('new', 'User\PartnerBranchesController@newBranch');
                        Route::post('edit', 'User\PartnerBranchesController@editBranch');
                    });

                    Route::prefix('photos')->group(function (){

                        Route::middleware(['auth.api.user','auth.api.user.reged'])->group(function (){
                            Route::post('add', 'User\PartnerBranchesController@addBranchPhotos');
                            Route::post('delete', 'User\PartnerBranchesController@deleteBranchPhoto');
                        });

                    });

                });

                Route::prefix('logo')->group(function (){

                    Route::middleware(['auth.api.user','auth.api.user.reged'])->group(function (){
                        Route::post('add', 'User\UserOptDataController@addUserOptLogo');
                    });

                });

            });
        });

        Route::middleware(['auth.api.user'])
            ->prefix('images')->group(function (){

            Route::post('upload', 'Image\ImageController@uploadImage');

            Route::middleware(['auth.api.user.reged'])
                ->post('delete', 'Image\ImageController@deleteImage');

        });


        Route::prefix('services')->group(function (){

            Route::middleware(['auth.api.user','auth.api.user.reged'])->group(function (){

                Route::post('add', 'Service\ServiceController@addService');

            });


            Route::get('list', 'Service\ServiceController@getServicesList');
            Route::get('getOne', 'Service\ServiceController@getService');

        });

        Route::prefix('products')->group(function (){

            Route::prefix('categories')->group(function () {

                Route::middleware(['auth.api.user','auth.api.user.reged'])
                    ->group(function () {

                    Route::post('add', 'Products\ProductCategoryController@addCategory');
                    Route::post('edit', 'Products\ProductCategoryController@editCategory');
                    Route::post('delete', 'Products\ProductCategoryController@deleteCategory');

                });

                Route::get('list', 'Products\ProductCategoryController@getCategoriesList');
            });

            Route::prefix('characters')->group(function () {

                Route::middleware(['auth.api.user','auth.api.user.reged'])
                    ->group(function () {

                        Route::post('add', 'Products\ProductsCharacterController@addProductsCharacter');
                        Route::post('delete', 'Products\ProductsCharacterController@deleteProductsCharacter');

                    });

                Route::get('list', 'Products\ProductsCharacterController@getProductsCharactersList');

            });

            Route::prefix('images')->group(function () {

                Route::middleware(['auth.api.user','auth.api.user.reged'])
                    ->group(function () {
                        Route::post('add', 'Products\ProductsImageController@addProductPhotos');
                        Route::post('delete', 'Products\ProductsImageController@deleteProductPhotos');
                    });

            });

            Route::prefix('relatedServices')->group(function () {

                Route::middleware(['auth.api.user','auth.api.user.reged'])
                    ->group(function () {
                        Route::post('add',
                            'Products\ProductsRelatedServicesController@addProductRelatedService');

                        Route::post('delete',
                            'Products\ProductsRelatedServicesController@deleteProductRelatedService');
                    });

                Route::get('list',
                    'Products\ProductsRelatedServicesController@getProductRelatedServicesList');

            });

            Route::middleware(['auth.api.user','auth.api.user.reged'])->group(function () {

                Route::post('add', 'Products\ProductsController@addProduct');
                Route::post('edit', 'Products\ProductsController@editProduct');
                Route::post('delete', 'Products\ProductsController@deleteProduct');

            });

            Route::get('getOne', 'Products\ProductsController@getProduct');
            Route::get('list', 'Products\ProductsController@getProductsList');

        });

        Route::prefix('characters')->group(function () {

            Route::middleware(['auth.api.user','auth.api.user.reged'])
                ->group(function () {

                    Route::post('add', 'Characters\CharactersController@addCharacter');
                    Route::post('edit', 'Characters\CharactersController@editCharacter');
                    Route::post('delete', 'Characters\CharactersController@deleteCharacter');

                });

            Route::get('list', 'Characters\CharactersController@getCharactersList');
            Route::get('getOne', 'Characters\CharactersController@getCharacter');

        });

        Route::prefix('bids')->group(function (){

            Route::middleware(['auth.api.user','auth.api.user.reged'])->group(function () {
                Route::post('add', 'Bids\BidsController@addBid');
                Route::post('cancel', 'Bids\BidsController@cancelBid');
                Route::post('edit', 'Bids\BidsController@editBid');
                Route::post('moderate', 'Bids\BidsController@moderateBid');
                Route::post('repeat', 'Bids\BidsController@repeatBid');

            });

            Route::middleware(['auth.api.user','auth.api.user.reged'])
                ->prefix('user')->group(function (){

                Route::post('list', 'Bids\UserBidsController@getUserBidsList');
                Route::post('getOne', 'Bids\UserBidsController@getBid');

            });

            Route::prefix('services')->group(function (){

                Route::get('list', 'Bids\BidsController@getBidsServices');

            });

            Route::prefix('response')->group(function () {

                Route::middleware(['auth.api.user','auth.api.user.reged'])->group(function () {

                    Route::post('add', 'Bids\BidsResponseController@addResponse');
                    Route::post('edit', 'Bids\BidsResponseController@editResponse');
                    Route::post('list', 'Bids\BidsResponseController@getBidResponsesList');
                    Route::post('select', 'Bids\BidsResponseController@selectBidResponse');
                    Route::post('getOne', 'Bids\BidsResponseController@getBidResponse');
                    Route::post('refuse', 'Bids\BidsResponseController@refuseBidResponse');
                    Route::post('delete', 'Bids\BidsResponseController@deleteBidResponse');
                    Route::post('fix', 'Bids\BidsResponseController@fixBidResponse');
                    Route::post('unfix', 'Bids\BidsResponseController@unfixBidResponse');

                });

                Route::get('contacts', 'Bids\BidsResponseController@getBidResponseContacts');

            });

        });

        Route::prefix('reviews')->group(function () {

            Route::middleware(['auth.api.user','auth.api.user.reged'])->group(function () {

                Route::post('add', 'Reviews\ReviewsController@addReview');
                Route::post('list', 'Reviews\ReviewsController@getReviewsList');
                Route::post('getOne', 'Reviews\ReviewsController@getReview');
                Route::post('edit', 'Reviews\ReviewsController@editReview');
                Route::post('moderate', 'Reviews\ReviewsController@moderateReview');

            });

        });


        Route::prefix('relatedServices')->group(function () {

            Route::middleware(['auth.api.user','auth.api.user.reged'])->group(function () {

                Route::post('add', 'RelatedServices\RelatedServicesController@addRelatedService');
                Route::post('edit', 'RelatedServices\RelatedServicesController@editRelatedService');
                Route::post('delete', 'RelatedServices\RelatedServicesController@deleteRelatedService');

            });

            Route::get('getOne', 'RelatedServices\RelatedServicesController@getRelatedService');

        });

        Route::prefix('sales')->group(function () {

            Route::middleware(['auth.api.user','auth.api.user.reged'])->group(function () {

                Route::post('add', 'Sales\SalesController@addSale');
                Route::post('edit', 'Sales\SalesController@editSale');
                Route::post('delete', 'Sales\SalesController@deleteSale');

            });

            Route::get('getOne', 'Sales\SalesController@getSale');
            Route::get('list', 'Sales\SalesController@getSalesList');

            Route::prefix('photos')->group(function () {
                Route::post('add', 'Sales\PhotosController@addSalesPhotos');
                Route::post('delete', 'Sales\PhotosController@deleteSalesPhotos');
            });

            Route::prefix('cities')->group(function () {
                Route::post('add', 'Sales\CitiesController@addSalesCities');
                Route::post('delete', 'Sales\CitiesController@deleteSalesCities');
            });


        });

        Route::prefix('news')->group(function () {

            Route::prefix('photos')->group(function () {

                Route::middleware(['auth.api.user','auth.api.user.reged'])->group(function () {

                Route::post('add', 'News\NewsController@addNewsPhotos');
                Route::post('delete', 'News\NewsController@deleteNewsPhotos');

                });

            });

            Route::middleware(['auth.api.user','auth.api.user.reged'])->group(function () {

                Route::post('add', 'News\NewsController@addNews');
                Route::post('edit', 'News\NewsController@editNews');
                Route::post('delete', 'News\NewsController@deleteNews');

            });

            Route::get('getOne', 'News\NewsController@getOneNews');
            Route::get('list', 'News\NewsController@getNewsList');

        });

        Route::prefix('businesses')->group(function () {

            Route::middleware(['auth.api.user','auth.api.user.reged'])->group(function () {
                Route::post('add', 'Businesses\BusinessesController@addBusiness');
                Route::post('edit', 'Businesses\BusinessesController@editBusiness');
                Route::post('delete', 'Businesses\BusinessesController@deleteBusiness');
            });

            Route::get('list', 'Businesses\BusinessesController@getBusinessesList');
            Route::get('getOne', 'Businesses\BusinessesController@getBusiness');

        });

        Route::prefix('taxes')->group(function () {

            Route::middleware(['auth.api.user','auth.api.user.reged'])->group(function () {
                Route::post('add', 'Taxes\TaxesController@addTax');
                Route::post('edit', 'Taxes\TaxesController@editTax');
                Route::post('delete', 'Taxes\TaxesController@deleteTax');
            });

            Route::get('list', 'Taxes\TaxesController@getTaxesList');
            Route::get('getOne', 'Taxes\TaxesController@getTax');

        });

        Route::prefix('delivery')->group(function () {
            Route::prefix('method')->group(function () {

            Route::middleware(['auth.api.user','auth.api.user.reged'])->group(function () {
                Route::post('add', 'Delivery\DeliveryController@addMethod');
            });

            Route::get('list', 'Delivery\DeliveryController@getMethodsList');
            Route::get('getOne', 'Delivery\DeliveryController@getMethod');

            });

        });

        Route::prefix('payments')->group(function () {
            Route::prefix('method')->group(function () {

                Route::middleware(['auth.api.user','auth.api.user.reged'])->group(function () {
                    Route::post('add', 'Payments\PaymentsController@addMethod');
                });

                Route::get('list', 'Payments\PaymentsController@getMethodsList');
                Route::get('getOne', 'Payments\PaymentsController@getMethod');

            });

        });

        Route::prefix('orders')->group(function () {
            Route::prefix('products')->group(function () {
            Route::middleware(['auth.api.user','auth.api.user.reged'])->group(function () {

                Route::post('add', 'Orders\OrdersController@addOrderProduct');
                Route::post('delete', 'Orders\OrdersController@deleteOrderProduct');

                });

                Route::get('list', 'Orders\OrdersController@getOrderProductsList');

                Route::prefix('relatedServices')->group(function () {

                    Route::middleware(['auth.api.user','auth.api.user.reged'])->group(function () {

                        Route::post('add',
                            'Orders\OrdersRelatedServicesController@addRelatedServices');

                        Route::post('delete',
                            'Orders\OrdersRelatedServicesController@deleteRelatedService');

                    });

                    Route::get('list',
                        'Orders\OrdersRelatedServicesController@getProductsRelatedServices');

                });
            });

            Route::prefix('user')->group(function () {

                Route::middleware(['auth.api.user','auth.api.user.reged'])->group(function () {

                    Route::post('list','Orders\OrdersUserController@getUserOrdersList');

                });

            });

            Route::prefix('partner')->group(function () {

                Route::middleware(['auth.api.user','auth.api.user.reged'])->group(function () {

                    Route::post('list', 'Orders\OrdersController@getOrdersList');

                });

            });

            Route::prefix('responses')->group(function () {

                Route::middleware(['auth.api.user','auth.api.user.reged'])->group(function () {

                    Route::post('add', 'Orders\OrdersResponsesController@addOrderResponse');

                });

            });

            Route::middleware(['auth.api.user','auth.api.user.reged'])->group(function () {
                Route::post('cancel', 'Orders\OrdersController@cancelOrder');
                Route::post('form', 'Orders\OrdersController@makeOrderFormed');
            });

            Route::get('getOne', 'Orders\OrdersController@getOrder');

        });



    });

//Временные методы

    //Метод для проверки значений

   /* Route::post('v1/dev/query/check', function (Request $request) {

        $all_params = Input::all() ?? "Нет параметров";

        return response()->json([

            'response' => [
                'ALL params' => $all_params,
                'headers' => $request->header(),
                'POST params' => $_POST,
                'GET PARAMS' => $_GET,
                'FILES' => $_FILES,
            ]

        ], 200, [], JSON_UNESCAPED_UNICODE);

    });

Route::post('v1/dev/user/delete', function (Request $request) {

    $auth_token = $request->input('auth_token');

    $ph_slt = \App\Models\PhoneSalt::where('auth_token',$auth_token)
        ->where('is_accepted',1)
        ->first();

      $user =  $ph_slt->user()
        ->first();

    DB::transaction(function () use($user,$ph_slt){

        $user->delete();
        $ph_slt->delete();

    });



    return response()->json([

        'response' => 1

    ], 200, [], JSON_UNESCAPED_UNICODE);

});

    //Методы для создания бд городов



Route::get('v1/selectCities',function (){

    //Получаю id регионов России


         $regions_bld = DB::table('region')
            ->where('country_id',0);


         if(!$regions_bld->exists())
             return response('Нет регионов');

         $regions = $regions_bld->get(['id']);

        foreach($regions as $region){

            $cities_bld = DB::table('city')
                ->where('region_id',$region->id);

            if(!$cities_bld->exists()){
                DB::table('region')->where('id',$region->id)->delete;
                continue;
            }

            $cities = $cities_bld->get(['name','id']);

            foreach ($cities as $city) {

                $name = $city->name;
                $id = $city->id;

                DB::transaction(function () use($name,$id){

                DB::table('cities')->insert(['name' => $name]);
                DB::table('city')->where('id',$id)->delete();

                });
            }

        }

});*/

/*Route::get('v1/getCitiesLatLon',function (){

    $cities = DB::table('cities')->where('id','>=',2572)->get(['name','id']);

    foreach ($cities as $city) {


        $response = Curl::to('https://geocode-maps.yandex.ru/1.x/')
            ->withData([
                'geocode' => 'город '.$city->name,
                'format' => 'json',
                'kind' => 'locality',
            ])
            ->get();

        $response = json_decode($response,true);

        $response_found = $response['response']['GeoObjectCollection']['metaDataProperty']['GeocoderResponseMetaData']['found'];

        if($response_found == '0'){
            echo $city->name."<br>";
            continue;
        }

        $response_coords = $response['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['Point']['pos'];

        $exploded_coords = explode(' ',$response_coords);

        $lon = $exploded_coords[0];
        $lat = $exploded_coords[1];

        DB::table('cities')->where('id',$city->id)->update([
            'center_lon' => $lon,
            'center_lat' => $lat,
        ]);

        DB::table('new_order_city')->insert([
           'city_id' => $city->id,
        ]);

    }

});

Route::get('v1/getCitiesTimes',function (){

    $cities = DB::table('cities')->where('id','>=',2572)->get(['center_lat','center_lon','id']);

    foreach ($cities as $city){

        $url = 'http://api.geonames.org/findNearbyJSON';
        $url .= '?lat='.$city->center_lat.'&lng='.$city->center_lon.'&username=den82721&style=full';


        $response = Curl::to($url)
            ->get();

        $response = json_decode($response,true);

        if(!isset($response['geonames'])){

            print_r( $response);
            echo "<br>".$city->id;
            return;

        }

        $time_values = $response["geonames"][0]['timezone'];

        $time_zone = $time_values['timeZoneId'];

        $utc_offset = $time_values['gmtOffset'] * 60 * 60;

        DB::table('cities')->where('id',$city->id)->update([
            'time_zone' => $time_zone,
            'utc_offset' => $utc_offset,
        ]);

    }

});

Route::get('v1/getCitiesKruim',function (){

    $cities_cruim = DB::table('city')->get(['name']);

    foreach ($cities_cruim as $city_crum){

        DB::table('cities')->insert([
            'name' => $city_crum->name,
        ]);

    }

});

Route::get('v1/getCitiesGeoNamesData',function (){

    $cities = DB::table('cities')->where('id','>=',2572)->get(['name','id']);

    foreach ($cities as $city) {

        $name = $city->name;

        $response = Curl::to('http://api.geonames.org/searchJSON')
            ->withData([
                'q' => $name,
                'maxRows' => '10',
                'username' => 'den82721',
                'lang' => 'ru',
                'cities' => 'cities5000',
                'country' => 'ua',
                'style' => 'full',
            ])
            ->get();

        $response = json_decode($response,true);

        if(isset($response['status'])){
            echo $city->id;
            return;
        }

        if($response['totalResultsCount'] == 0){

            DB::transaction(function () use($city){

                DB::table('order_city')->insert([
                    'city_id' => $city->id,
                    'name' => $city->name,
                ]);
                DB::table('cities')->where('id', $city->id)->delete();
            });

            echo $city->name."<br>";
            continue;
        }


    }

});*/

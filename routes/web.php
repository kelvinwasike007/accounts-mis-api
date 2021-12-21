<?php

/** @var \Laravel\Lumen\Routing\Router $router */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return response()->json(["appVersion"=>$router->app->version()])->header('Content-Type','application/json');
});

$router->group(['prefix'=>'sync'], function()use($router){
    $router->post('/upload', function (Request $req)
    {
        $collection = $req['dbName'];
        $documents = $req;
        $collections = DB::table('collections')->where('collection_name', $collection)->count();
        if($collections<=0){
           DB::table('collections')->insert([
                'collection_name'=>$req["dbName"],
                'documents'=>json_encode($req["rows"])
            ]);
        }else{
            //get new data from client
            $cliData = $req["rows"];
            $dbData = DB::table('collections')->where('collection_name', $req['dbName'])->value('documents');
            $tmpData = $dbData;
            
            //compare data
            foreach ($cliData as $document) {
                if(in_array($document, $dbData)){

                }else{
                    //add new data
                    array_push($tmpData, $document);
                }
            }
            

            //save to collections
            DB::table('collections')->where('collection_name', $collection)->update([
                'documents'=>$tmpData
            ]);
        }
        return response()->json(["status"=>"ok", "message"=>'Sync Complete'], 200);
    });
    $router->get('/download/{collection_name}', function (Request $req, $collection_name)
    {
        $collection = $collection_name;
        $documents = $req;
        $collections = DB::table('collections')->where('collection_name', $collection)->value('documents');
        return response()->json(json_decode($collections), 200);
    });
});
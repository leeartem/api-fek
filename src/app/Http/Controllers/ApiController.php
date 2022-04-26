<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\EmitentService;
use App\Services\ReestrSEOservice;
use App\Services\FnsServices\CheckInnService;

use App\Libs\DaData;

use App\Services\APIResponses;
use App\Services\Parsers\FedresursParserNew;

use GuzzleHttp\Client;

class ApiController extends Controller
{
    protected $client;

    protected $ogrn;

	protected $proxy;

	protected $userAgent;

	protected $cookies;

	protected $timeout = 7;

    public function __construct()
    {
    }

    public function get(Request $request)
    {
        $response = 228;
        return $response;
    }

    public function checkBankruptcy(Request $request)
    {
        // $ogrn = $this->getOgrnFromRequet($request);
        $ogrn  = $request->input('query');
        // dd($ogrn);
        (new FedresursParserNew($ogrn))->checkBankruptcy();
    }

    public function getOgrnFromRequet(Request $request)
    {
        $query  = $request->input('query');
        $path    = $request->input('path');

        if(!$query && !$path) {
            $array =  [
                'status' => APIResponses::STATUS_ERR,
                'msg' => 'empty request parameters'
            ];
            response($array, 400)->send();
            die;
        };

        if($query){
            try {
                $daDataResponse = (new Dadata (true))->getCompanyArray($query);
                $this->daData = $daDataResponse;
                return $daDataResponse['ogrn'];
            } catch (\App\Exceptions\DadataNotFound $e) {
                $array =  [
                    'status' => APIResponses::STATUS_NOT_FOUND,
                    'msg' => 'entity not found'
                ];

                response($array, 404)->send();
                die;
            } catch (\Exception $e) {
                throw $e;
            }
        }

        if($path){
            // TODO get from urn
        }
    }
}

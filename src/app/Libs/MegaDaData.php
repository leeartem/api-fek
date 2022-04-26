<?php

namespace App\Libs;

use App\Services\ReestrSEOservice;
use GuzzleHttp\Client;
use App\Exceptions\DadataNotFound;
use Illuminate\Support\Facades\DB;

class MegaDaData extends DaData
{
    public function __construct($useFullTariffByOgrnOrInn = true)
    {
        $this->api_key = "c4d7a989e901147ae5b2b6f583ba04c04455c2fb";
        
        $this->useFullTariffByOgrnOrInn = $useFullTariffByOgrnOrInn;

		$this->seoService = new ReestrSEOservice();
    }

}
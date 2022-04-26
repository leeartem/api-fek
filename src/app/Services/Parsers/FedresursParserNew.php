<?php

namespace App\Services\Parsers;

use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use App\Libs\Fedresurs;

class FedresursParserNew extends FedresursParser
{
	protected $ogrn;

	protected $proxy;

	protected $userAgent;

	protected $cookies;

    protected $client;

	protected $timeout = 7;

	protected $getCompanyGuidTries = 0;
	protected $getLeaseContractsTries = 0;
	protected $getContractContentCookiesTries = 0;
	protected $getContractContentTries = 0;

	public function __construct($ogrn)
	{
		parent::__construct($ogrn);

		$this->ogrn = $ogrn;

		$this->userAgent = $this->user_agent();

		$this->cookies = new \GuzzleHttp\Cookie\CookieJar();

		$this->setProxy();

        // $this->client = new Client();
	}

    public function checkBankruptcy()
    {
        $params = [

        ];

        // $headers = [
		// 	'authority' => 'fedresurs.ru',
		// 	'method' => 'GET',
		// 	'path' => "/backend/sfactmessages/$contractGuid",
		// 	'scheme' => 'https',
		// 	'accept' => 'application/json, text/plain, */*',
		// 	'accept-encoding' => 'gzip, deflate, br',
		// 	'accept-language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7,fr;q=0.6,kk;q=0.5',
		// 	'cache-control' => 'no-cache',
		// 	'pragma' => 'no-cache',
		// 	'referer' => "https://fedresurs.ru/sfactmessage/$contractGuid?attempt=1",

		// 	'sec-fetch-mode' => 'cors',
		// 	'sec-fetch-site' => 'same-origin',
		// 	'user-agent' => $this->userAgent
		// ];

        $headers = [
			'user-agent' => $this->userAgent,
			'accept' => 'image/gif, image/x-xbitmap, image/jpeg,image / pjpeg, application / x - shockwave - flash, application / vnd.ms - excel, application / vnd.ms - powerpoint,  application / msword,',
			// 'authority' => 'fedresurs.ru',
			'accept-language' => 'ru',
            'content-type' => "application/json",
            'keep-alive' => true,
			'referer' => "https://fedresurs.ru/search/entity?code=$this->ogrn",

            // ":authority" => 'fedresurs.ru',



			// 'method' => 'GET',
			// 'path' => "/backend/sfactmessages/$contractGuid",
			// 'scheme' => 'https',
			// 'accept-encoding' => 'gzip, deflate, br',
			// 'cache-control' => 'no-cache',
			// 'pragma' => 'no-cache',

			// 'sec-fetch-mode' => 'cors',
			// 'sec-fetch-site' => 'same-origin',
		];

        $options = [
			'headers' => $headers,
			'cookies' => $this->cookies,
			'timeout' => 20,
            'verify' => false,
			'proxy' => $this->proxy
		];

        $response = (new Fedresurs($this->ogrn))->request("https://fedresurs.ru/backend/companies?limit=15&offset=0&code=$this->ogrn");
        dd($response);

        $response = $this->getClient()->request('GET',"https://fedresurs.ru/backend/companies?limit=15&offset=0&code=$this->ogrn", $options);
        dd((string)$response->getBody());
    }

	protected function setProxy() {
		$this->proxy = "alan:HHLN*RmIyxFRT2w@".$this->proxy_company_session();
	}

	public function proxy_company_session() {
        $ips = [
            '135.125.247.47:3213',
        ];

        $host = $ips[0];
        return $host;
    }

	public function user_agent()
    {
        $agents = [
            'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:47.0) Gecko/20100101 Firefox/47.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X x.y; rv:42.0) Gecko/20100101 Firefox/42.0',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.103 Safari/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.106 Safari/537.36 OPR/38.0.2220.41',
            'Opera/9.80 (Macintosh; Intel Mac OS X; U; en) Presto/2.2.15 Version/10.00',
            'Opera/9.60 (Windows NT 6.0; U; en) Presto/2.1.1',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36 Edg/91.0.864.59',
            'Mozilla/5.0 (compatible; MSIE 9.0; Windows Phone OS 7.5; Trident/5.0; IEMobile/9.0)',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 13_5_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.1.1 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:45.0) Gecko/20100101 Firefox/45.0',
            'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/85.0.4183.102 YaBrowser/20.9.3.136 Yowser/2.5 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4389.105 YaBrowser/21.3.3.230 Yowser/2.5 Safari/537.36',
            'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:47.0) Gecko/20100101 Firefox/62.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4389.114 Safari/537.36',
            'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 YaBrowser/20.11.3.183 Yowser/2.5 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.114 Safari/537.36 OPR/77.0.4054.172',
            'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.18 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.79 Safari/537.36',
            'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/85.0.4183.83 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.141 Safari/537.36 OPR/73.0.3856.344 (Edition Yx)',
            'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4093.3 Safari/537.36',
            'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.111 Safari/537.36 OPR/72.0.3815.186',
            'Mozilla/5.0 (Windows NT 4; rv:52.0) Gecko/20100101 Firefox/52.0',
            'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.186 YaBrowser/18.3.1.1232 Yowser/2.5 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.138 YaBrowser/20.6.2.197 Yowser/2.5 Yptp/1.23 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.170 Safari/537.36 OPR/53.0.2907.99',
            'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.87 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/84.0.4147.135 Safari/537.36 OPR/70.0.3728.178',
            'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3409.2 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.125 Amigo/61.0.3163.125 MRCHROME SOC Safari/537.36',
            'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.61 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.14 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.138 YaBrowser/20.4.2.328 Yowser/2.5 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.138 YaBrowser/20.6.1.151 Yowser/2.5 Safari/537.36',
        ];

        $agent = $agents[rand(0, (count($agents) - 1))];
        return $agent;
    }
}
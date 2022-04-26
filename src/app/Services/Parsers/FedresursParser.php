<?php

namespace App\Services\Parsers;

use Illuminate\Support\Facades\DB;

class FedresursParser extends BaseParse
{
	protected $ogrn;

	protected $proxy;

	protected $userAgent;

	protected $cookies;

	protected $timeout = 7;

	protected $getCompanyGuidTries = 0;
	protected $getLeaseContractsTries = 0;
	protected $getContractContentCookiesTries = 0;
	protected $getContractContentTries = 0;

	public function __construct($ogrn)
	{
		parent::__construct();

		$this->ogrn = $ogrn;

		$this->userAgent = $this->getUserAgent();

		$this->cookies = new \GuzzleHttp\Cookie\CookieJar();

		$this->setProxy();
	}

	protected function setProxy() {
		$this->proxy = $this->proxy_company_rotating();
	}

	protected function getCompanyGuidNEW()
	{

		// $json = [
		// 	'entitySearchFilter' => [
		// 		'code' => $this->ogrn,
		// 		'legalCase' => null,
		// 		'name' => null,
		// 		'onlyActive' => false,
		// 		'pageSize' => 15,
		// 		'regionNumber' => null,
		// 		'startRowIndex' => 0,
		// 	],
		// 	'isCompany' => null,
		// 	'isFirmBankrupt' => null,
		// 	'isFirmTradeOrg' => null,
		// 	'isSro' => null,
		// 	'isSroTradePlace' => null,
		// 	'isTradePlace' => null,
		// ];
		$headers = [
			'accept' => 'application/json, text/plain, */*',
			// 'accept-encoding' => 'gzip, deflate, br',
			// 'accept-language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7,fr;q=0.6,kk;q=0.5',
			'cache-control' => 'no-cache',
			'DNT' => '1',
			// 'content-length' => '264',
			// 'content-type' => 'application/json',
			// 'origin' => 'https://fedresurs.ru',
			'pragma' => 'no-cache',
			'referer' => "https://fedresurs.ru/search/entity?code=$this->ogrn,",
			'sec-fetch-ua' => '" Not A;Brand";v="99", "Chromium";v="100", "Google Chrome";v="100"',
			'sec-fetch-dest' => 'empty',
			// 'sec-fetch-mode' => 'cors',
			'sec-fetch-site' => 'same-origin',
			'user-agent' => $this->getUserAgent(),
		];
		$options = [
			// 'json' => $json,
			'headers' => $headers,
			'timeout' => $this->timeout,
			// 'proxy' => $this->proxy
		];


		DB::disconnect();
		$response = $this->getClient()->request('GET', "https://fedresurs.ru/backend/companies?limit=15&offset=0&code=$this->ogrn&isActive=true", $options);
		dd($response);
	}

	protected function getCompanyGuid()
	{
		$json = [
			'entitySearchFilter' => [
				'code' => $this->ogrn,
				'legalCase' => null,
				'name' => null,
				'onlyActive' => false,
				'pageSize' => 15,
				'regionNumber' => null,
				'startRowIndex' => 0,
			],
			'isCompany' => null,
			'isFirmBankrupt' => null,
			'isFirmTradeOrg' => null,
			'isSro' => null,
			'isSroTradePlace' => null,
			'isTradePlace' => null,
		];
		$headers = [
			':authority' => 'fedresurs.ru',
			':method' => 'POST',
			':path' =>'backend/companies/search',
			':scheme' => 'https',
			'accept' => 'application/json, text/plain, */*',
			'accept-encoding' => 'gzip, deflate, br',
			'accept-language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7,fr;q=0.6,kk;q=0.5',
			'cache-control' => 'no-cache',
			'content-length' => '264',
			'content-type' => 'application/json',
			'origin' => 'https://fedresurs.ru',
			'pragma' => 'no-cache',
			'referer' => "https://fedresurs.ru/search/entity?code=$this->ogrn,",
			'sec-fetch-dest' => 'empty',
			'sec-fetch-mode' => 'cors',
			'sec-fetch-site' => 'same-origin',
			'user-agent' => $this->getUserAgent(),
		];
		$options = [
			'json' => $json,
			'headers' => $headers,
			'timeout' => $this->timeout,
			'proxy' => $this->proxy
		];

		// try {
			DB::disconnect();
			$response = $this->getClient()->request('POST', 'https://fedresurs.ru/backend/companies/search', $options);
			// $response = $this->getClient()->request('GET', "https://fedresurs.ru/backend/companies?limit=15&offset=0&code=$this->ogrn&isActive=true", $options);
			dd($response);
		// } catch (\GuzzleHttp\Exception\RequestException $e) { // proxy 502 , or timeout
		// 	if(++$this->getCompanyGuidTries > 20) throw $e;
		// 	$this->setProxy();
		// 	return $this->getCompanyGuid();
		// }

		$response = json_decode((string) $response->getBody(), true);

		if(!$response) { // because of proxy $response sometimes can be null
			++$this->getCompanyGuidTries;

			if($this->getCompanyGuidTries < 20) {
				return $this->getCompanyGuid();
			} else {
				return null;
			}
		}
		
		if(!$response['found']) return null;

		$guid = null;
		foreach ($response['pageData'] as $item) {
			if($item['ogrn'] == $this->ogrn) $guid = $item['guid'];
		}

		return $guid;
	}

	public function getLeaseContracts()
	{
		$companyGuid = $this->getCompanyGuid();

		dd($companyGuid);

		$json = [
			'guid' => $companyGuid,
			'pageSize' => 90,
			'startRowIndex' => 0,

			'startDate' => null,
			'endDate' => null,
			'messageNumber' => null,
			'bankruptMessageType' => null,
			'bankruptMessageTypeGroupId' => null,
			'legalCaseId' => null,

			'searchAmReport' => false,
			'searchFirmBankruptMessage' => false,
			'searchFirmBankruptMessageWithoutLegalCase' => false,
			'searchSfactsMessage' => true,
			'searchSroAmMessage' => false,
			'searchTradeOrgMessage' => false,

			'sfactMessageType' => null,
			'sfactsMessageTypeGroupId' => 3,
		];
		$headers = [
			':authority' => 'fedresurs.ru',
			':method' => 'POST',
			':path' => '/backend/companies/publications',
			':scheme' => 'https',
			':accept' => 'application/json, text/plain, */*',
			':accept-encoding' => 'gzip, deflate, br',
			':accept-language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7,fr;q=0.6,kk;q=0.5',
			':content-length' => '455',
			':content-type' => 'application/json',
			':origin' => 'https://fedresurs.ru',
			':sec-fetch-mode' => 'cors',
			':sec-fetch-site' => 'same-origin',
			':user-agent' => $this->userAgent,
			'referer' => "https://fedresurs.ru/company/$companyGuid"
		];
		$options = [
			'json' => $json,
			'headers' => $headers,
			'timeout' => $this->timeout,
			'proxy' => $this->proxy
		];

		try {
			DB::disconnect();
			$response = $this->getClient()->request('POST', 'https://fedresurs.ru/backend/companies/publications', $options);
		} catch (\GuzzleHttp\Exception\RequestException $e) { // proxy 502 or timeout
			if(++$this->getLeaseContractsTries > 15) throw $e;
			$this->setProxy();
			return $this->getLeaseContracts($companyGuid);
		}

		$response = json_decode((string) $response->getBody(), true);

		if(!$response) { // because of proxy $response sometimes can be null
			++$this->getLeaseContractsTries;

			if($this->getLeaseContractsTries < 15) {
				return $this->getLeaseContracts($companyGuid);
			} else {
				return null;
			}
		}

		$arr = [];
		foreach ($response['pageData'] as $item) {
			if(!preg_match('#лизинга#', $item['title'])) continue;

			$arr[] = [
				'entity_ogrn' => $this->ogrn,
				'fedresurs_guid' => $item['guid'],
				'fedresurs_msg_number' => $item['number'],
				'fedresurs_msg_date' => date('Y-m-d', strtotime($item['datePublish'])),
				'title' => $item['title'],
				'publisher' => $item['publisherName'],
				'participants' => $item['participants'],
			];
		}

		return count($arr) ? $arr : null;
	}


	protected function getContractContentCookies(string $contractGuid)
	{
		$headers = [
			'authority' => 'fedresurs.ru',
			'method' => 'GET',
			'path' => "/backend/sfactmessages/$contractGuid",
			'scheme' => 'https',
			'accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',

			'accept-encoding' => 'gzip, deflate, br',
			'accept-language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7,fr;q=0.6,kk;q=0.5',

			'upgrade-insecure-requests' => 1,

			'sec-fetch-mode' => 'navigate',
			'sec-fetch-site' => 'none',
			'sec-fetch-user' => '?1',

			'user-agent' => $this->userAgent
		];
		$options = [
			'headers' => $headers,
			'cookies' => $this->cookies,
			'timeout' => $this->timeout,
			'proxy' => $this->proxy
		];

		try {
			DB::disconnect();
			$this->getClient()->request('GET', "https://fedresurs.ru/sfactmessages/$contractGuid", $options);
		} catch (\GuzzleHttp\Exception\RequestException $e) { // proxy 502 or timeout
			if(++$this->getContractContentCookiesTries > 6) throw $e;
			$this->setProxy();
			return $this->getContractContentCookies($contractGuid);
		}
	}

	public function getContractContent(string $contractGuid)
	{
		$this->getContractContentCookies($contractGuid);

		$headers = [
			'authority' => 'fedresurs.ru',
			'method' => 'GET',
			'path' => "/backend/sfactmessages/$contractGuid",
			'scheme' => 'https',
			'accept' => 'application/json, text/plain, */*',
			'accept-encoding' => 'gzip, deflate, br',
			'accept-language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7,fr;q=0.6,kk;q=0.5',
			'cache-control' => 'no-cache',
			'pragma' => 'no-cache',
			'referer' => "https://fedresurs.ru/sfactmessage/$contractGuid?attempt=1",

			'sec-fetch-mode' => 'cors',
			'sec-fetch-site' => 'same-origin',
			'user-agent' => $this->userAgent
		];
		$options = [
			'headers' => $headers,
			'cookies' => $this->cookies,
			'timeout' => $this->timeout,
			'proxy' => $this->proxy
		];

		try {
			DB::disconnect();
			$response = $this->getClient()->request('GET', "https://fedresurs.ru/backend/sfactmessages/$contractGuid", $options);
		} catch (\GuzzleHttp\Exception\RequestException $e) { // proxy 502 or timeout
			if(++$this->getContractContentTries > 15) throw $e;
			$this->setProxy();
			return $this->getContractContent($contractGuid);
		}

		$response = json_decode((string) $response->getBody(), true);

		if(!$response) { // because of proxy $response sometimes can be null
			++$this->getContractContentTries;

			if($this->getContractContentTries < 15) {
				return $this->getContractContent($contractGuid);
			} else {
				return null;
			}
		}

		return [
			'fedresurs_guid' => $response['guid'],
			'publisher_inn' => $response['publisher']['inn'],
			'publisher_ogrn' => $response['publisher']['ogrn'],
			'subjects' => serialize($response['content']['subjects']),
			'contract_number' => $response['content']['contractNumber'],
			'contract_date' => date('Y-m-d', strtotime($response['content']['contractDate'])),

			'lease_start_date' => date('Y-m-d', strtotime($response['content']['startDate'])),
			'lease_end_date' => date('Y-m-d', strtotime($response['content']['endDate'])),

			'comment' => $response['content']['text'],
			'parse_date' => date('Y-m-d')
		];
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
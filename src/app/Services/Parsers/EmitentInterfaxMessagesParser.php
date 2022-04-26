<?php


namespace App\Services\Parsers;

use Illuminate\Support\Facades\DB;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Client;
use App\Services\Parsers\BaseParse;

class EmitentInterfaxMessagesParser extends BaseParse
{
	protected $ogrn;

	protected $proxy;
	protected $sendRequestTries = 0;

	public function __construct(string $ogrn)
	{
		parent::__construct();

		$this->ogrn = $ogrn;

		$this->setProxy();
	}

	public function setProxy()
	{
		$this->proxy = $this->proxy_company_session();
        // $this->proxy = $this->proxy_smartproxy_session();
	}

	public function parse()
	{
		$html = $this->getHtml();
		return $this->parseHtml($html);
	}

    /**
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException proxy 502/ timeout/ cURL error 56: Proxy CONNECT aborted
     * @throws \Exception Emitent parser: Empty response because of proxy
     */
	protected function getHtml() :string
	{
        $jar = new \GuzzleHttp\Cookie\CookieJar();
        $uri = 'https://www.e-disclosure.ru/poisk-po-soobshheniyam';

        // save cookies first
        $response = (new Client())->request('GET', $uri, ['cookies' => $jar]);

		$requestParams = [
            'cookies' => $jar,
			'timeout' => 12,
			'proxy' => $this->proxy,
			'form_params' => [
				'lastPageSize' => '2147483647',
				'lastPageNumber' => '1',
				'query' => $this->ogrn,
				'radView' => '0',
				'dateStart' => '03.11.2000',
				'dateFinish' => date('d.m.Y'),
				'radReg' => 'FederalDistricts',
				'districtsCheckboxGroup' => '-1',
				'regionsCheckboxGroup' => '-1',
				'branchesCheckboxGroup' => '-1',
				'textfieldCompany' => $this->ogrn,
			],
            'headers' => [
                'Accept' => '*/*',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7,fr;q=0.6,kk;q=0.5',
                'Cache-Control' => 'no-cache',
                'Connection' => 'keep-alive',
                'Content-Length' => '278',
                'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
                'Host' => 'www.e-disclosure.ru',
                'Origin' => 'https://www.e-disclosure.ru',
                'Pragma' => 'no-cache',
                'Referer' => 'https://www.e-disclosure.ru/poisk-po-soobshheniyam',
                'Sec-Fetch-Dest' => 'empty',
                'Sec-Fetch-Mode' => 'cors',
                'Sec-Fetch-Site' => 'same-origin',
                'User-Agent' => $this->getUserAgent(),
                'X-Requested-With' => 'same-origin',
            ]
		];

        DB::disconnect();
        $response = (new Client())->request('POST', $uri, $requestParams);
        $response = (string) $response->getBody();

        if($response === '') throw new \Exception('Emitent parser: Empty response because of proxy');

        return $response;
	}

	protected function parseHtml(string $html)
	{
		$arr = [];

		$crawler = (new Crawler($html));

		if(!$crawler->filter('#cont_wrap table tr')->count()) return null;

		$crawler->filter('#cont_wrap table tr')->each(function (Crawler $node, $i) use (&$arr){
			$date_time = $node->filter('td')->first()->text();
			$date_time = date('Y-m-d H:i', strtotime($date_time));

			$companyHref = $node->filter('td')->last()->filter('a')->first()->attr('href');
			preg_match('#id=(\d*)$#', $companyHref, $m);
			$interfaxCompanyId = $m[1];

			$eventHref = $node->filter('td')->last()->filter('a')->last()->attr('href');
			preg_match('#EventId=(.*)$#', $eventHref, $m);
			$interfaxEventId = $m[1];

			$eventTitle =  $node->filter('td')->last()->filter('a')->last()->text();

			$source = $node->filter('td')->last()->filter('span')->text();

			$arr[] = [
				'interfax_company_id' => $interfaxCompanyId,
				'interfax_event_id' => $interfaxEventId,
				'source' => $source,
				'title' => $eventTitle,
				'content' => null,
				'published_at' => $date_time,
			];
		});

		return  $arr;
	}
}

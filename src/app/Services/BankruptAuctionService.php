<?php


namespace App\Services;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;

class BankruptAuctionService
{
	protected $inn;

	protected $bankrupt_auction_key;
	
	/**
	 * BankruptAuctionService constructor.
	 * @param $inn
	 * @param $ogrn
	 */
	public function __construct(string $inn, string $ogrn)
	{
		$this->inn = $inn;
		$this->ogrn = $ogrn;
		$this->bankrupt_auction_key = config('app.bankrupt_auction_key');
	}

	public function getInfo()
	{
		$options = [
			'query' => [
				'key' => $this->bankrupt_auction_key,
				'inn' => $this->inn,
				'ogrn' => $this->ogrn,
				'dump' => 0
			],
			'headers' => [
				'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
				'Accept-Encoding' => 'gzip, deflate',
				'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7,fr;q=0.6,kk;q=0.5',
				'Cache-Control'		=> 'no-cache',
				'Connection' => 'keep-alive',
				'Host' => 'rutorgi.com',
				'Upgrade-Insecure-Requests' => '1',
				'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.149 Safari/537.36'
			]
		];

		DB::disconnect();
		$response = (new Client())->request('GET', 'https://rutorgi.com/api/subject/', $options);

		$response = json_decode((string) $response->getBody(), true);

		return $response;
	}
}
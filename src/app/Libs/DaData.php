<?php
/**
 * Created by PhpStorm.
 * User: Alan
 * Date: 28.03.2019
 * Time: 9:39
 */

namespace App\Libs;

use App\Services\ReestrSEOservice;
use GuzzleHttp\Client;
use App\Exceptions\DadataNotFound;
use Illuminate\Support\Facades\DB;

class DaData
{
    protected $api_key;

    protected $useFullTariffByOgrnOrInn;

    protected $client = null;

    protected function getClient() {
        if ($this->client === null) {
            $this->client = new Client();
        }
        return $this->client;
    }

    // Full search accepts only INN or OGRN in query string
    public function __construct($useFullTariffByOgrnOrInn = false)
    {
        $this->api_key = config('app.dadata_key_pro');
        $this->useFullTariffByOgrnOrInn = $useFullTariffByOgrnOrInn;

		$this->seoService = new ReestrSEOservice();
    }

    public function getAddrList(string $queryString) :array
    {
//        $this->logRequest($queryString, 'getAddrList');

        $client = $this->getClient();
        $response = $client->request(
            'POST',
            'https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/address',
            [
                'json' => [
                    'query' => $queryString,
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept'     => 'application/json',
                    'Authorization' => "Token {$this->api_key}"
                ]
            ]
        );

        $daData_res = json_decode((string)$response->getBody(), true);
        return $daData_res['suggestions'];
    }

	/**
	 * Full search accepts only INN or OGRN in query string
	 *
	 * @param string $queryString
	 * @param bool $strictSearchOnlyByOgrn
	 * @return array
	 * @throws DadataNotFound
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
    public function getCompanyArray(string $queryString, $strictSearchOnlyByOgrn=false) :array
    {
//        $this->logRequest($queryString, 'getCompanyArray');

        if($this->useFullTariffByOgrnOrInn) {
            $uri = 'https://suggestions.dadata.ru/suggestions/api/4_1/rs/findById/party';
        } else {
            $uri = 'https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/party';
        }

        $client = $this->getClient();
        $response = $client->request(
            'POST',
            $uri,
            [
                'json' => [
                    'query' => $queryString,
                    'count' => '1'
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept'     => 'application/json',
                    'Authorization' => "Token {$this->api_key}"
                ]
            ]
        );

        $daData_res = json_decode((string)$response->getBody(), true);
        if (isset($daData_res['suggestions'][0]['data'])) {
            if($strictSearchOnlyByOgrn) {
                if($daData_res['suggestions'][0]['data']['ogrn'] == $queryString) {
                    return $daData_res['suggestions'][0]['data'];
                } else {
                    throw new DadataNotFound();
                }
            }
            return $daData_res['suggestions'][0]['data'];
        } else {
            throw new DadataNotFound();
        }
    }

	/**
	 * Full search accept only INN or OGRN in query string
	 *
	 * @param string $queryString
	 * @return mixed
	 * @throws DadataNotFound
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
    public function getCompanyList(string $queryString)
    {
//        $this->logRequest($queryString, 'getCompanyList');

        if($this->useFullTariffByOgrnOrInn) {
            $uri = 'https://suggestions.dadata.ru/suggestions/api/4_1/rs/findById/party';
        } else {
            $uri = 'https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/party';
        }

        $client = $this->getClient();
        $response = $client->request(
            'POST',
            $uri,
            [
                'json' => [
                    'query' => $queryString,
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept'     => 'application/json',
                    'Authorization' => "Token {$this->api_key}"
                ]
            ]
        );

        $daData_res = json_decode((string)$response->getBody(), true);

        if (isset($daData_res['suggestions'][0]['data'])) {
            return $daData_res;
        } else {
            throw new DadataNotFound();
        }
    }

	public function getConnections(string $managerInn, string $excludeInn='', string $managerFio='')
	{
//        $this->logRequest("$managerInn $excludeInn $managerFio", 'getConnections');

		$client = $this->getClient();
		$response = $client->request(
			'POST',
			'https://suggestions.dadata.ru/suggestions/api/4_1/rs/findAffiliated/party',
			[
				'json' => [
					'query' => $managerInn,
					'count' => 20
				],
				'headers' => [
					'Content-Type' => 'application/json',
					'Accept'     => 'application/json',
					'Authorization' => "Token {$this->api_key}"
				]
			]
		);

		$daData_res = json_decode((string)$response->getBody(), true);

		$res = [
			'asManager' =>[],
			'asFounder' => [],
			'other' => []
		];

		if(!$daData_res['suggestions']) return null;

		foreach ($daData_res['suggestions'] as $company) {
			$company = $company['data'];

			if($company['inn'] == $excludeInn) continue;

			$item = [
				'inn' => $company['inn'],
				'ogrn' => $company['ogrn'],
				'name' => $company['name']['short_with_opf'] ?: $company['name']['full_with_opf'] ?:  $company['name']['full'],
				'uri' => $this->seoService->generateUri($company),
				'post' => null,
			];

			// если проверяем физ лицо,
			// то можно определить тип связи (кем является в найденной компании):
			// учредитель или руководитель
			if($managerFio) {
				if($company['management']) {
					$linkedFio = $company['management']['name'];

					$fio1_arr = explode(' ', mb_strtolower($linkedFio));
					$fio2_arr = explode(' ', mb_strtolower($managerFio));

					$intersect = array_uintersect($fio1_arr, $fio2_arr, 'strcasecmp');
					$isLinked= count($intersect) == count($fio1_arr);

					if($isLinked) {
						$item['post'] = $company['management']['post'] ? mb_ucfirst($company['management']['post']): null;
						$res['asManager'][] = $item;
					} else {
						$res['asFounder'][] = $item;
					}
				}
			} else {
				$res['other'][] = $item;
			}
		}

		if(!$res['other'] && !$res['asFounder'] && !$res['asManager']) return null;

		return $res;
    }

    protected function logRequest($request, $method) {
        $stack = debug_backtrace();
        $finalStack = [];
        foreach ($stack as $item) {

            if(isset($item['file']) && preg_match('#vendor|Illuminate#', $item['file'])) continue;

            $types = [
                'boolean',
                'integer',
                'string',
                'NULL',
            ];

            $argsFiltered = [];

            foreach ($item['args'] as $key => $arg) {
                $argType = gettype($arg);
                if(in_array($argType, $types)) $argsFiltered[$key] = $arg;
            }

            $finalStack[] = [
                'file' => $item['file'] ?? '',
                'line' => $item['line'] ?? '',
                'function' => $item['function'],
                'class' => $item['class'] ?? '',
                'args' => $argsFiltered,
            ];
        }

        DB::table('dadata_requests_logs')->insert([
            'method' => $method,
            'ip' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '',
            'request' => $request,
            'stack' => serialize($finalStack),
            'date' => date('Y-m-d H:i:s'),
        ]);
    }
}

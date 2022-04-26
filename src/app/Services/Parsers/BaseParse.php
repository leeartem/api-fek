<?php
/**
 * Created by PhpStorm.
 * User: Alan
 * Date: 27.06.19
 * Time: 9:46
 */

namespace App\Services\Parsers;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;

abstract class BaseParse
{
    protected $client = null;

    public $proxy_auth_company;

    public $proxy_auth_smartproxy;

    protected $choose_fast_proxy_tries = 0;

    public $months = [
        ' ' => '.',
        'января' => '01',
        'февраля' => '02',
        'марта' => '03',
        'апреля' => '04',
        'мая' => '05',
        'июня' => '06',
        'июля' => '07',
        'августа' => '08',
        'сентября' => '09',
        'октября' => '10',
        'ноября' => '11',
        'декабря' => '12'
    ];

    public function __construct()
    {
        $this->proxy_auth_company = config('app.proxy_auth_company');

        $this->proxy_auth_smartproxy = config('app.proxy_auth_smartproxy');
    }

    abstract protected function setProxy();

    public function choose_fast_proxy()
    {
        try {
            $response = $this->getClient(['defaults' => [
                'verify' => false
            ]])->request('GET', 'https://ipinfo.io/',
                [
                    'timeout' => 6,
                    'proxy' => $this->proxy
                ]
            );

        } catch (\Exception $e) {
            if(++$this->choose_fast_proxy_tries > 10) throw $e;
            $this->setProxy();
            $this->choose_fast_proxy();
        }
    }

    protected function getClient() {
        if ($this->client === null) {
            $this->client = new Client(['defaults' => [
                'verify' => false
            ]]);
        }
        return $this->client;
    }

    public function proxy_company_rotating() :string
    {
        $ips = [
            '135.125.247.47:3130',
            '135.125.247.47:3131',
            '135.125.247.47:3132',
        ];

        $host = $ips[mt_rand(0,2)];
        return "$this->proxy_auth_company@$host";
    }

    public function proxy_company_session() {
        $ips = [
            '135.125.247.47:3133',
            '135.125.247.47:3134',
            '135.125.247.47:3135',
            '135.125.247.47:3136',
            '135.125.247.47:3137',
            '135.125.247.47:3138',
            '135.125.247.47:3139',
            '135.125.247.47:3140',
            '135.125.247.47:3141',
            '135.125.247.47:3142',
            '135.125.247.47:3143',
            '135.125.247.47:3144',
            '135.125.247.47:3145',
            '135.125.247.47:3146',
            '135.125.247.47:3147',
            '135.125.247.47:3148',
            '135.125.247.47:3149',
            '135.125.247.47:3150',
            '135.125.247.47:3151',
            '135.125.247.47:3152',
            '135.125.247.47:3153',
            '135.125.247.47:3154',
            '135.125.247.47:3155',
            '135.125.247.47:3156',
            '135.125.247.47:3157',
            '135.125.247.47:3158',
            '135.125.247.47:3159',
            '135.125.247.47:3160',
            '135.125.247.47:3161',
            '135.125.247.47:3162',
            '135.125.247.47:3163',
            '135.125.247.47:3164',
            '135.125.247.47:3165',
            '135.125.247.47:3166',
            '135.125.247.47:3167',
            '135.125.247.47:3168',
            '135.125.247.47:3169',
            '135.125.247.47:3170',
            '135.125.247.47:3171',
            '135.125.247.47:3172',
            '135.125.247.47:3173',
            '135.125.247.47:3174',
            '135.125.247.47:3175',
            '135.125.247.47:3176',
            '135.125.247.47:3177',
            '135.125.247.47:3178',
            '135.125.247.47:3179',
            '135.125.247.47:3180',
            '135.125.247.47:3181',
            '135.125.247.47:3182',
            '135.125.247.47:3183',
            '135.125.247.47:3184',
            '135.125.247.47:3185',
            '135.125.247.47:3186',
            '135.125.247.47:3187',
            '135.125.247.47:3188',
            '135.125.247.47:3189',
            '135.125.247.47:3190',
            '135.125.247.47:3191',
            '135.125.247.47:3192',
            '135.125.247.47:3193',
            '135.125.247.47:3194',
            '135.125.247.47:3195',
            '135.125.247.47:3196',
            '135.125.247.47:3197',
            '135.125.247.47:3198',
            '135.125.247.47:3199',
            '135.125.247.47:3200',
            '135.125.247.47:3201',
            '135.125.247.47:3202',
            '135.125.247.47:3203',
            '135.125.247.47:3204',
        ];

        $host = $ips[mt_rand(0,71)];
        return "$this->proxy_auth_company@$host";
    }

    public function proxy_smartproxy_session()
    {
        $proxies = [
            ['ru', 40001, 49999],
            ['de', 20001, 29999],
            ['fr', 40001, 49999]
        ];

        $proxy = $proxies[mt_rand(0, 2)];

        $locName = $proxy[0];
        $port = mt_rand($proxy[1], $proxy[2]);

        return "$this->proxy_auth_smartproxy@$locName.smartproxy.com:$port";
    }





    public function getUserAgent() :string
    {
        $popularUserAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.110 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:64.0) Gecko/20100101 Firefox/64.0',
            'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_2) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.0.2 Safari/605.1.15',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.140 Safari/537.36 Edge/17.17134',
            'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:64.0) Gecko/20100101 Firefox/64.0',
            'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.110 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:63.0) Gecko/20100101 Firefox/63.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.110 Safari/537.36',
            'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:64.0) Gecko/20100101 Firefox/64.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.80 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.14; rv:64.0) Gecko/20100101 Firefox/64.0'
        ];

        return $popularUserAgents[rand(0, count($popularUserAgents)-1)];
    }

    public function extractZip(string $extractToInStorage, string $pathToZipInStorage) :array
    {
        set_time_limit(60);
        $extractTo = storage_path("app/{$extractToInStorage}");

        // if(!file_exists($extractTo)) mkdir($extractTo);
        Storage::makeDirectory('app/'.$extractToInStorage, $mode=0777);

        $zip = new \ZipArchive;
        $zip->open(storage_path("app/{$pathToZipInStorage}"));
        $zip->extractTo($extractTo);
        $zip->close();

        // Storage::delete($pathToZipInStorage);

        return Storage::files($extractToInStorage);
    }

    public function downloadZip($saveTo, $fileUri)
	{
		shell_exec("wget -O $saveTo $fileUri > /dev/null &");
	}


	public function extractZipNew(string $extractTo, string $pathToZip)
    {
        set_time_limit(99);

        $zip = new \ZipArchive;
        $zip->open($pathToZip);
        $zip->extractTo($extractTo);
        $zip->close();
    }
}

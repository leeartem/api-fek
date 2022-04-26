<?php

namespace App\Libs;

use Illuminate\Support\Facades\Storage;

class Fedresurs
{
    protected $ogrn;

    public function __construct($ogrn)
    {
        $this->ogrn = $ogrn;
        ####
        $this->cookies = Storage::disk('local')->path("fedresurs/cookies/".uniqid().".txt");; // Путь до cookie файла ! Поменяй на свой. Главное, чтобы папка была доступна для записи.
        $this->proxy   = '5.101.77.61:51958'; // ЭТО IP проксика. Можешь не менять его, он постоянный РФ, лежит на нашем серваке.
        ####

        ini_set('memory_limit', '-1');

        $this->curl_config                         = [];
        $this->curl_config[CURLOPT_CONNECTTIMEOUT] = 15;
        $this->curl_config[CURLOPT_TIMEOUT]        = 1000;
        $this->curl_config[CURLOPT_SSL_VERIFYPEER] = 0;
        $this->curl_config[CURLOPT_SSL_VERIFYHOST] = 0;

        $this->headers = [
            ':authority: fedresurs.ru',
            ':method: GET',
            ':path: /backend/companies?limit=15&offset=0&code=3112330166000&isActive=true',
            ':scheme: https',
            'accept: image/gif, image/x-xbitmap, image/jpeg,image / pjpeg, application / x - shockwave - flash, application / vnd.ms - excel, application / vnd.ms - powerpoint,  application / msword,',
            'accept-language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7,ja;q=0.6',
            "content-type: application/json",
            "keep-alive: true",
			"referer: https://fedresurs.ru/search/entity?code=$this->ogrn",

            'cache-control: no-cache',
            'sec-ch-ua: " Not A;Brand";v="99", "Chromium";v="99", "Google Chrome";v="99"',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-platform: "macOS"',
            'sec-fetch-dest: document',
            'sec-fetch-mode: navigate',
            'sec-fetch-site: none',
            'sec-fetch-user: ?1',
            'upgrade-insecure-requests: 1',
            'user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/99.0.4844.83 Safari/537.36',
        ];

        // dd($this->headers);
    }


    public function request(string $url, $post = [])
    {
        $ch = curl_init();
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        if ( ! empty($post)) {
            if ( ! is_string($post)) {
                $post = http_build_query($post);
            }
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookies);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookies);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        foreach ($this->curl_config as $name => $value) {
            curl_setopt($ch, $name, $value);
        }
        $res = curl_exec($ch);

        return $res;
    }

}

<?php

namespace App\Libs;

use Illuminate\Support\Facades\Storage;

class RknGov
{
    public function __construct()
    {
        ####
        $this->cookies = Storage::disk('local')->path("rkn/cookies/".uniqid().".txt");; // Путь до cookie файла ! Поменяй на свой. Главное, чтобы папка была доступна для записи.
        $this->proxy   = '5.101.77.61:51958'; // ЭТО IP проксика. Можешь не менять его, он постоянный РФ, лежит на нашем серваке.
        ####

        ini_set('memory_limit', '-1');

        $this->curl_config                         = [];
        $this->curl_config[CURLOPT_CONNECTTIMEOUT] = 15;
        $this->curl_config[CURLOPT_TIMEOUT]        = 1000;
        $this->curl_config[CURLOPT_SSL_VERIFYPEER] = 0;
        $this->curl_config[CURLOPT_SSL_VERIFYHOST] = 0;

        $this->headers = [
            'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'accept-language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7,ja;q=0.6',
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
    }

    public function download(string $url, string $filePath)
    {
        $this->curl_config[CURLOPT_HEADER] = 1;
        $resp                              = $this->request($url);

        preg_match('#__js_p_=(.*),#Us', $resp, $code);
        $hash = $this->get_jhash((int)$code[1]);
        file_put_contents($this->cookies, file_get_contents($this->cookies) . "rkn.gov.ru	FALSE	/	FALSE	0	__jhash_	" . $hash . "\n");
        file_put_contents($this->cookies, file_get_contents($this->cookies) . "rkn.gov.ru	FALSE	/	FALSE	0	__jua_	Mozilla%2F5.0%20%28Macintosh%3B%20Intel%20Mac%20OS%20X%2010_15_7%29%20AppleWebKit%2F537.36%20%28KHTML%2C%20like%20Gecko%29%20Chrome%2F99.0.4844.83%20Safari%2F537.36\n");

        sleep(1);
        $this->headers[]                   = 'referer: ' . $url;
        $this->curl_config[CURLOPT_HEADER] = 1;
        $resp                              = $this->request($url);


        usleep(400);
        $this->curl_config[CURLOPT_HEADER] = 0;
        $resp                              = $this->request($url); // Это последний запрос, который скачивает непосредственно сам zip. Можешь сразу писать ответ в файл через STREAM

        unlink($this->cookies);
        // return $resp;
        file_put_contents($filePath, $resp);
        return true;
    }

    private function get_jhash(int $b)
    {
        $x = 123456789;
        $k = 0;
        for ($i = 0; $i < 1677696; $i++) {
            $x = (($x + $b) ^ ($x + ($x % 3) + ($x % 17) + $b) ^ $i) % 16776960;
            if ($x % 117 == 0) {
                $k = ($k + 1) % 1111;
            }
        }

        return $k;
    }

    private function request(string $url, $post = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
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

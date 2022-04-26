<?php

namespace App\Services\Parsers;

use Archive7z\Archive7z;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;
use ZipArchive;

class LocalDiskOps
{
    public function downloadZip($saveTo, $fileUri)
	{
		shell_exec("wget --no-check-certificate -O $saveTo $fileUri > /dev/null &");
	}


	public function extractZipNew(string $extractTo, string $pathToArchive)
    {
		// dd($pathToArchive);
        try {
			$zip = new ZipArchive;
			$zip->open($pathToArchive);
			$zip->extractTo($extractTo);
			$zip->close();
			Storage::disk('local')->delete($pathToArchive);
		} catch (Throwable $th) {
			Storage::disk('local')->delete($pathToArchive);
			return false;
			// dd('error', $extractTo, $pathToArchive);
			// throw $th;
		}

		return true;
    }

    public function extract7z(string $extractTo, string $pathToArchive)
	{
		$filePath = Storage::disk('local')->path($pathToArchive);
		$obj = new Archive7z($filePath);
		$obj->setOutputDirectory($extractTo)->extract();
        Storage::disk('local')->delete($pathToArchive);
	}

    public function downloadCurl($fileUri, $saveTo) {
		// $opts = array(
		// 	'http' => array('method' => 'GET',
		// 					'authority' => 'rkn.gov.ru',
		// 					'max_redirects' => '20',
		// 					'header'  => 'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9'."\r\n"
		// 					.'accept-language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7,ja;q=0.6'."\r\n"
		// 					.'cache-control: no-cache'."\r\n"
		// 					.'cookie: __js_p_=984,1800,0,0; __jhash_=46; __jua_=Mozilla%2F5.0%20%28Macintosh%3B%20Intel%20Mac%20OS%20X%2010_15_7%29%20AppleWebKit%2F537.36%20%28KHTML%2C%20like%20Gecko%29%20Chrome%2F99.0.4844.83%20Safari%2F537.36; __hash_=a5f6fad7cd0770be700520646681f237; __lhash_=6ab2219d5f9311d1c36a5116f3850c07'."\r\n"
		// 					.'pragma: no-cache'."\r\n"
		// 					// .'referer: https://rkn.gov.ru/opendata/7705846236-OperatorsPD/data-20220324T0000-structure-20180129T0000.zip'."\r\n"
		// 					.'accept-encoding: gzip, deflate, br'."\r\n"
		// 					.'accept-language: en-US,en;q=0.9'."\r\n"
		// 					// .':authority: rkn.gov.ru'."\r\n"
		// 					.'dnt: 1'."\r\n"
		// 					.'sec-fetch-site: same-origin'."\r\n"
		// 					.'sec-ch-ua: " Not A;Brand";v="99", "Chromium";v="99", "Google Chrome";v="99"'."\r\n"
		// 					.'sec-ch-ua-mobile: ?0'."\r\n"
		// 					.'sec-ch-ua-platform: "macOS"'."\r\n"
		// 					.'sec-fetch-dest: document'."\r\n"
		// 					.'sec-fetch-mode: navigate'."\r\n"
		// 					// .'sec-fetch-site: none'."\r\n"
		// 					.'sec-fetch-user: ?1'."\r\n"
		// 					.'upgrade-insecure-requests: 1'."\r\n"
		// 					.'user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/99.0.4844.83 Safari/537.36'."\r\n",)
		// 	);
		// $context = stream_context_create($opts);
		// dd($context);
		// $rh = fopen($fileUri, 'rb', false, $context);
		$rh = fopen($fileUri, 'rb');
		$wh = fopen($saveTo, 'w+b');
		if (!$rh || !$wh) {
			return false;
		}
		echo "Downloading";
		while (!feof($rh)) {
			if (fwrite($wh, fread($rh, 4096)) === FALSE) {
				return false;
			}
			echo ".";
			flush();
		}

		fclose($rh);
		fclose($wh);
		echo "\n";

		return true;
	}
}

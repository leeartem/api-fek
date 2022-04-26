<?php


namespace App\Libs;


class CaptchaOffice extends Captcha
{
    public function __construct()
    {
        parent::__construct();
        $this->key = config('app.office_captcha_key');
    }

    public function ImageToTextTask($body, $encodeToBase64 = false)
    {
        $base64Str = $encodeToBase64 ? base64_encode($body) : $body;

        $cap_response = $this->getClient()->request(
            'POST',
            'http://95.179.170.106:17854/in.php',
            [
                'form_params' => [
					"method" => 'base64',
					"key" => $this->key,
					"body" => $base64Str,
					"CapMonsterModule" => 'ZennoLab.universal',
					"ParallelMode" => true,
					'numeric' => true
				]
            ]
        );

        preg_match('#OK\|(\d*)#', (string) $cap_response->getBody(), $m);
        $taskId = $m[1];

        // sleep(2);

        return $this->getTaskResult($taskId);
    }

    public function getTaskResult($taskId, $timeoutSec = false)
    {
        if ($timeoutSec) $this->recursionTimeOutSec = $timeoutSec;

        $cap_response = $this->getClient()->request(
            'GET',
            'http://95.179.170.106:17854/res.php',
            [
                'query' => [
                    "key" => $this->key,
                    "action" => 'get',
                    'id' => $taskId
                ]
            ]
        );

        $cap_response = (string) $cap_response->getBody();

        if ($cap_response == 'CAPCHA_NOT_READY') {
            sleep(3);
            if (!$this->isTaskStillActual()) return false; //custom timeout!
            return $this->getTaskResult($taskId, $timeoutSec);
        }

        preg_match('#OK\|(.*)#', $cap_response, $m);
        return $m[1];
    }
}

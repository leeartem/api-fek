<?php

namespace App\Libs;

class AntiCaptcha extends Captcha
{
    public $russian = false;

    public $creatTaskTries = 0;

    public $getTaskResultTries = 0;

    public function __construct()
    {
        parent::__construct();

        $this->key = "9c9b16b15a9c422a268d2c4150c65f1a";
    }

    public function ImageToTextTask($body, $encodeToBase64 = false, $russian = false) :string
    {
        $this->russian = $russian;
        $base64Str = ($encodeToBase64) ? base64_encode($body) : $body;

        $task = [
            "type" => "ImageToTextTask",
            "body" => str_replace("\n", "", $base64Str),
            "phrase" => false,
            "case" => false,
            "numeric" => false,
            "math" => 0,
            "minLength" => 0,
            "maxLength" => 0,
        ];

        $taskId = $this->createTask($task);

        $result = $this->getTaskResult($taskId);

        //ERROR_NO_SLOT_AVAILABLE
        if($result['errorId'] == 2 ) {
            if(++ $this->getTaskResultTries > 10) throw new \App\Exceptions\Anticaptcha_no_slot_available();
            sleep(2);
            return $this->ImageToTextTask($body, $encodeToBase64, $russian);
        }

        return $result['solution']['text'];
    }

    public function createTask(array $task)
    {
        $cap_response = $this->getClient()->request(
            'POST',
            'https://api.anti-captcha.com/createTask',
            [
                'json' => [
                    "clientKey" => $this->key,
                    "task" => $task,
                    "languagePool" => ($this->russian) ? 'rn' : 'en'
                ]
            ]
        );

        $cap_response = json_decode((string) $cap_response->getBody(), true);

        if(isset($cap_response['errorCode']) && $cap_response['errorCode'] == 'ERROR_NO_SLOT_AVAILABLE') {
            sleep(3);
            $this->creatTaskTries++;

            if($this->creatTaskTries < 5) {
                return $this->createTask($task);
            } else {
                throw new \Exception('anti-captcha ERROR_NO_SLOT_AVAILABLE');
            }
        }
        return $cap_response['taskId'];
    }

    public function getTaskResult($taskId, $timeoutSec = false)
    {
        if($timeoutSec) $this->recursionTimeOutSec = $timeoutSec;

        $cap_response = $this->getClient()->request(
            'POST',
            'https://api.anti-captcha.com/getTaskResult', [
            'json' => [
                "clientKey" => $this->key,
                "taskId" => $taskId
            ]
        ]);

        $cap_response = json_decode((string) $cap_response->getBody(), true);
        if ($cap_response['status'] == 'processing' && $this->isTaskStillActual()) {
            sleep(3);
            return $this->getTaskResult($taskId, $timeoutSec);
        }

        return $cap_response;
    }
}

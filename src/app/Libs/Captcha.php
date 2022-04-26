<?php


namespace App\Libs;


use GuzzleHttp\Client;
use App\Exceptions\CaptchaTimeOut;

abstract class Captcha
{
    public $key;

    public $recursionTimeOutSec = 180;

    public $startTime = 0;

    protected $client = null;

    public function __construct()
    {
        $this->startTime = time();
    }

    protected function getClient() {
        if ($this->client === null) {
            $this->client = new Client();
        }
        return $this->client;
    }

    abstract public function ImageToTextTask ($body, $encodeToBase64);

    abstract public function getTaskResult ($taskId, $timeoutSec);

    public function isTaskStillActual () {
        $timePassedSec = (time() - $this->startTime);

        if($timePassedSec >= $this->recursionTimeOutSec) throw new CaptchaTimeOut();

        return true;
    }
}

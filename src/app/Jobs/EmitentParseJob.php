<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use App\Services\EmitentService;

class EmitentParseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	public $ogrn;

	public $tries = 1;

	public $timeout = 20;

	public $service;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($ogrn)
    {
		$this->queue = 'EmitentParseJob';

		$this->ogrn = $ogrn;

		$this->service = new EmitentService($ogrn);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
		$this->service->createForJob();
    }


}

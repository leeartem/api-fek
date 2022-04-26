<?php


namespace App\Services;


use App\Services\Parsers\EmitentInterfaxMessagesParser;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Jobs\EmitentParseJob;

class EmitentService
{
	protected $ogrn;

	protected $taskWhere;

	protected $parser;

	public function __construct($ogrn)
	{
		$this->ogrn = $ogrn;

		$this->parser = new EmitentInterfaxMessagesParser($ogrn);

		$this->taskWhere = [
			['task_name', 'entity_emitent'],
			['task_key', $ogrn],
		];
	}

	public function getMessages()
	{
		if($task = $this->task_get()) {
			if($task->errors) {
				$this->task_delete();
				$this->createRows();
			}
			return $this->response_processing();
		} else {
			if($rows = $this->getMessagesFromDB()) {
				return  $this->response_ok($rows);
			} else {
				$this->createRows();
				return $this->response_processing();
			}
		}
	}

	protected function response_processing()
	{
		return [
			'status' => APIResponses::STATUS_PROC,
			'data' => null,
		];
	}

	protected function response_ok($rows)
	{
		return [
			'status' => APIResponses::STATUS_OK,
			'data' => $rows['data'],
			'updated_at' => $rows['updated_at']
		];
	}

	protected function task_get()
	{
		return DB::table('tasks')->where($this->taskWhere)->first();
	}

	protected function task_create()
	{
		$insertArr = [
			'task_name' => 'entity_emitent',
			'task_key' => $this->ogrn,
			'progress_current'=> 0,
			'progress_all'=> 1,
			'errors'=> 0,
		];

		DB::table('tasks')->insert($insertArr);
	}

	protected function task_delete()
	{
		DB::table('tasks')->where($this->taskWhere)->delete();
	}

	public function task_mark_as_error()
	{
		DB::table('tasks')->where($this->taskWhere)->increment('errors');
	}

	protected function createRows()
	{
        $this->task_create();
		EmitentParseJob::dispatch($this->ogrn);
	}

	public function createForJob()
	{
		$messages = $this->parser->parse();
		// dd($messages);
		try {
			// dd(2211);

			if(!$messages) {
				DB::table('emitent_interfax_companies')->insert([
					'ogrn' => $this->ogrn,
					'interfax_company_id' => null,
					'updated_at' => date('Y-m-d'),
				]);
			} else {
				DB::table('emitent_interfax_events')->insert($messages);

				$interfax_company_id = $messages[0]['interfax_company_id'];
				DB::table('emitent_interfax_companies')->insert([
					'ogrn' => $this->ogrn,
					'interfax_company_id' => $interfax_company_id,
					'updated_at' => date('Y-m-d'),
				]);
			}

			$this->task_delete();

		} catch (Exception $e) {
			$this->task_mark_as_error();
		}

	}

	protected function getMessagesFromDB()
	{
		$company = DB::table('emitent_interfax_companies')
			->where('ogrn', $this->ogrn)
			->first();

		if(!$company) return null;

		$responseArr = [
			'data' => null,
			'updated_at' => $company->updated_at,
		];

		if(!$company->interfax_company_id) return $responseArr;

		$messages = DB::table('emitent_interfax_events')
			->where('interfax_company_id', $company->interfax_company_id)
			->orderBy('published_at', 'asc')
			->get();

		$messages = $messages->map(function ($item, $key) {
			unset($item->interfax_event_id);
			unset($item->interfax_company_id);
			unset($item->source);

			$item->published_at = dateTimeFormat($item->published_at);
			return $item;
		});

		$responseArr['data'] = $messages;
		return $responseArr;
	}
}

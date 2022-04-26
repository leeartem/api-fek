<?php


namespace App\Services;

use App\Services\APIResponses;
use App\Services\Parsers\FedresursParser;
use Illuminate\Support\Facades\DB;
use App\Jobs\LeaseContracts\ParseList;
use App\Jobs\LeaseContracts\ParseMsg;

class LeaseContractService
{
	protected $parser;

	protected $ogrn;

	protected $taskWhereList;

	protected $taskWhereMsg;

	public function __construct($ogrn)
	{
		$this->ogrn = $ogrn;

		$this->parser = new FedresursParser($this->ogrn);

		$this->taskWhereList = [
			['task_name', 'entity_lease_list'],
			['task_key', $ogrn],
		];

		$this->taskWhereMsg = [
			['task_name', 'entity_lease_msg'],
			['task_key', $ogrn],
		];
	}

	public function getList()
	{
		if($task = $this->task_list_get()) {
			if($task->errors) {
				$this->task_list_delete();
				$this->createRowsForList();
			}
			return $this->response_processing();
		} else {
			if($rows = $this->getListFromDB()) {
				return  $this->response_ok($rows);
			} else {
				$this->createRowsForList();
				return $this->response_processing();
			}
		}
	}

	public function getMessage($message_guid)
	{
		if($task = $this->task_msg_get()) {
			if($task->errors) {
				$this->task_msg_delete();
				$this->createRowsForMsg($message_guid);
			}
			return $this->response_processing();
		} else {
			if($rows = $this->getMsgFromDB($message_guid)) {
				return  $this->response_ok($rows);
			} else {
				$this->createRowsForMsg($message_guid);
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



	protected function getListFromDB()
	{
		$status = DB::table('entities_leasing_ogrn_statuses')
			->where('ogrn',  $this->ogrn)
			->first();

		if(!$status) return null;


		if($status->has_rows) {
			$rows = DB::table('entities_leasing_contracts')
				->where('entity_ogrn',  $this->ogrn)
				->get();

			$rows = $rows->sortBy(function ($item, $key) {
				return $item->fedresurs_msg_date;
			});

			$rows = $rows->map(function ($item, $key) {
				unset($item->entity_ogrn);
				unset($item->fedresurs_guid);
				$item->fedresurs_msg_date = dateFormat($item->fedresurs_msg_date);
				return $item;
			});

			return [
				'data' => $rows,
				'updated_at' => $status->parse_date,
			];
		} else {
			return [
				'data' => null,
				'updated_at' => $status->parse_date,
			];
		}
	}

	protected function task_list_get()
	{
		return DB::table('tasks')->where($this->taskWhereList)->first();
	}

	protected function task_list_create()
	{
		$insertArr = [
			'task_name' => 'entity_lease_list',
			'task_key' => $this->ogrn,
			'progress_current'=> 0,
			'progress_all'=> 1,
			'errors'=> 0,
		];

		return DB::table('tasks')->insert($insertArr);
	}

	protected function task_list_delete()
	{
		DB::table('tasks')->where($this->taskWhereList)->delete();
	}

	protected function task_list_mark_as_error()
	{
		DB::table('tasks')->where($this->taskWhereList)->increment('errors');
	}

	protected function createRowsForList()
	{
		
		$this->task_list_create();
		ParseList::dispatch($this->ogrn);
	}

	public function createRowsForListJob()
	{
		try {
			$contracts = $this->parser->getLeaseContracts();
		} catch (\Exception $e) {
			$this->task_list_mark_as_error();
			throw $e;
		}

		if($contracts) {
			DB::table('entities_leasing_contracts')->insert($contracts);

			$in_status_table = [
				'ogrn' => $this->ogrn,
				'has_rows' => 1,
				'parse_date' => date('Y-m-d'),
			];
			DB::table('entities_leasing_ogrn_statuses')->insert($in_status_table);
		} else {
			$in_status_table = [
				'ogrn' => $this->ogrn,
				'has_rows' => 0,
				'parse_date' => date('Y-m-d'),
			];
			DB::table('entities_leasing_ogrn_statuses')->insert($in_status_table);
		}

		$this->task_list_delete();
	}




	protected function task_msg_get()
	{
		return DB::table('tasks')->where($this->taskWhereMsg)->first();
	}

	protected function task_msg_create()
	{
		$insertArr = [
			'task_name' => 'entity_lease_msg',
			'task_key' => $this->ogrn,
			'progress_current'=> 0,
			'progress_all'=> 1,
			'errors'=> 0,
		];

		DB::table('tasks')->insert($insertArr);
	}

	protected function task_msg_delete()
	{
		DB::table('tasks')->where($this->taskWhereMsg)->delete();
	}

	protected function task_msg_mark_as_error()
	{
		DB::table('tasks')->where($this->taskWhereMsg)->increment('errors');
	}

	protected function createRowsForMsg(string $message_guid)
	{
        $this->task_msg_create();
		ParseMsg::dispatch($this->ogrn, $message_guid);
	}

	public function createRowsForMsgJob(string $message_guid)
	{
		try {
			$message = $this->parser->getContractContent($message_guid);
		} catch (\Exception $e) {
			$this->task_msg_mark_as_error();
			throw $e;
		}

		DB::table('entities_leasing_content')->insert($message);

		$this->task_msg_delete();
	}

	protected function getMsgFromDB(string $message_guid)
	{
		$row = DB::table('entities_leasing_content')
			->where('fedresurs_guid', $message_guid)
			->first();

		if($row) {
			$parse_date = $row->parse_date;
			unset($row->parse_date);

			return [
				'data' => $row,
				'updated_at' => $parse_date
			];
		}
	}
}

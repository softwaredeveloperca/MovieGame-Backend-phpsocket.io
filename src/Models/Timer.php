<?php
namespace PHPSocketIO\Models;

//use Illuminate\Database\Capsule\Manager as Capsule;
//use Illuminate\Database\Eloquent\Model as Eloquent;

use PHPSocketIO\Models\Db as DB;

class Timer  {
	
	public function __construct($DB, $GameID)
	{
		$this->db=$DB;
		$this->GameID=$GameID;
	}
	
	public function createSlot($HowMany=1)
	{
		
	}
}

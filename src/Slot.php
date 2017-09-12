<?php
namespace PHPSocketIO\Models;

//use Illuminate\Database\Capsule\Manager as Capsule;
//use Illuminate\Database\Eloquent\Model as Eloquent;

use PHPSocketIO\Models\Db as DB;

class Slot  {
	
	public function __construct($DB, $GameID)
	{
		$this->db=$DB;
		$this->GameID=$GameID;
	}
	
	public function createSlot($HowMany=1)
	{
		for($x=1; $x<=$HowMany;$x++)
		{
			$data = Array ("Name" => "Slot " . $x, "SlotID" => $x, "GameID" => $this->GameID);	
			$SlotID = $this->db->insert ("Slots", $data);
		}
	}
}

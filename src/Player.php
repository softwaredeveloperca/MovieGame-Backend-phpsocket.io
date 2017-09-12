<?php
namespace PHPSocketIO\Models;

use PHPSocketIO\Models\Db as DB;

class Player  {
	
	public $Messages=array();
	public $PlayerID;
	
	public function __construct($DB)
	{
		$this->db=$DB;
	}
	
	public function addPlayerGame($GameID, $SocketID, $Name="Unknown")
	{

		$results=$this->db->where("GameID", $GameID)->where("SocketID", $SocketID)->getOne("Players");
	//	$results=null;
	//	if(count($results) > 0){
			
	//		$this->Messages[]=$results['Name'] . " has re-joined the game";
	//		$this->PlayerID=$results['id'];
	//	}
	//	else {
			$data = Array ("GameID" => $GameID, "Name" => $Name, "Money" => rand(1000, 2500), "SocketID" => $SocketID);	
			$this->PlayerID = $this->db->insert("Players", $data);
			$this->Messages[]=$Name . " has joined the game";
			return $this->PlayerID;
	//	}
	}
	
	public function getPlayerSocket($Socket)
	{
		$results=$this->db->where("SocketID", $SocketID)->getOne("Players");
		return $results;
	}
}
?>

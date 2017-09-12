<?php
namespace PHPSocketIO\Models;

//use Illuminate\Database\Capsule\Manager as Capsule;
//use Illuminate\Database\Eloquent\Model as Eloquent;

use PHPSocketIO\Models\Db as DB;

class Movie  {
	
	public function __construct($DB, $GameID)
	{
		$this->db=$DB;
		$this->GameID=$GameID;
	}
	
	public function createMovie($HowMany=1)
	{
		for($x=0; $x<$HowMany; $x++)
		{
			$data = Array ("Name" => "Movie " + rand(1111111, 22222222), "Cost" => rand(1000, 2500), "MovieType" => rand(1, 7), 
			"GameID", $this->GameID, "Duration", rand(1,4));	
			$PlayerID = $this->db->insert("Players", $data);
		}
	}
}

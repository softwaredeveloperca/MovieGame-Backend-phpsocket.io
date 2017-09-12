<?php
namespace PHPSocketIO\Models;



ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);



//use Illuminate\Database\Capsule\Manager as Capsule;
// use Illuminate\Database\Eloquent\Model as Eloquent;
use PHPSocketIO\Models\Db as DB;
use PHPSocketIO\Models\Player as Player;
use PHPSocketIO\Models\Movie as Movie;
use PHPSocketIO\Models\Slot as Slot;
use PHPSocketIO\Models\Timer as Timer;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);





class Game  {
	protected $PlayerID;
	public $GameID;
	protected $Messages=[];
	public $NewGame;
	protected $Sockets=[];
	protected $CityPopulation=100000;
	protected $HeartBeatLimitHigh=50;
	protected $HeartBeatLimitLow=1;
	protected $GameStart=1080;
	
	public function __construct($Sockets, $socket_id=null){
		$this->db = new Db ('localhost', 'root', '', 'MovieGame');
		$this->NewGame=false;
		
		if($socket_id)
		{
			$Player=$this->db->where('SocketID', $socket_id)->getOne('Players');
			$this->GameID=$Player['GameID'];
			$this->PlayerID=$Player['id'];
		}
		
		$this->Sockets=$Sockets;
	}
	
	
	public function getPlayerSocket($Socket)
	{
		$results=$this->db->where("SocketID", $Socket->id)->getOne("Players");
		$this->PlayerID=$results['id'];
		return $results;
	}
	
	public function restartGame($GameID=null)
	{
		if($GameID) $this->GameID=$GameID;
		$data = Array ('Day' => 1, 'Counter' => $this->GameStart, 'Status' => 'Pause');	
		$this->db->where('id', $this->GameID)->update('Games', $data);
		
	}
	
	public function speedTimer()
	{
		
		$Updated=$this->db->rawQuery("UPDATE Games SET Speed=Speed+1 where id=" . $this->GameID . " AND Speed < " . $this->HeartBeatLimitHigh);		
	}
	
	public function slowTimer()
	{
		$Updated=$this->db->rawQuery("UPDATE Games SET Speed=Speed-1 where id=" . $this->GameID . " AND Speed > " . $this->HeartBeatLimitLow);		
	}
	
	public function gameStatusTimer()
	{
		$actions=array('Play' => 'Pause', 'Pause' => 'Play');
		$GameData=$this->db->where("id",  $this->GameID)->getOne("Games");
		$Updated=$this->db->rawQuery("UPDATE Games SET Status='" . $actions[$GameData['Status']] . "' where id=" . $this->GameID);	
	}
	
	public function getSchedule2($data)
	{
		if($data == "All") 
			$wherestr="GameID='" . $this->GameID . "'";
		else 
			$wherestr="PlayerID='" . $this->PlayerID . "'";
			
	
		$Movies=$this->db->rawQuery("select * from GameMovies, movie_metadata where trim(GameMovies.Name) LIKE trim(movie_metadata.movie_title) AND " . $wherestr);
		return $Movies;
	}
	
	public function getMovies($data)
	{
		if($data == "All") 
			$wherestr="GameID='" . $this->GameID . "'";
		else 
			$wherestr="PlayerID='" . $this->PlayerID . "'";
			
	
		$Movies=$this->db->rawQuery("select * from GameMovies, movie_metadata where trim(GameMovies.Name) LIKE trim(movie_metadata.movie_title) AND " . $wherestr);
		return $Movies;
	}
	
	public function timerControls($Action)
	{
		switch($Action)
		{
			case "play":
				$this->gameStatusTimer();
			break;
			case "restart":
				$this->restartGame();
			break;
			case "slower":
				$this->slowTimer();
			break;
			case "faster":
				$this->speedTimer();
			break;
		}
	}
	
	public function incrementTimer($GameID)
	{

		$this->db->rawQuery("update Games set Counter=Counter+(Speed*Heartbeat) where Status LIKE 'Play' and id='" . $GameID . "'");
	}
	
	public function startWorld()
	{
		$this->db->rawQuery('truncate Games');
		$this->db->rawQuery('truncate Players');
		$this->db->rawQuery('truncate GameSlots');
		$this->db->rawQuery('truncate GameMovies');
		$this->db->rawQuery('truncate GameAds');
		$this->db->rawQuery('truncate MovieCategories');
		$this->db->rawQuery('truncate PeoplePreferences');
		$this->db->rawQuery('truncate Schedules');
	}
	
	public function lg($message)
	{
	
		 $this->message[]=$message;
	}
	

	public function removePlayer($SocketID)
	{
		
		$Player=$this->db->where('SocketID', $SocketID)->getOne('Players');
		$Game=$this->db->where("id", $Player['GameID'])->getOne("Games");
		$this->GameID=$Game['id'];
		
		
		$data = array('Players' => ($Game['Players'] - 1));
		$this->db->where("id", $Player['GameID'])->update("Games", $data);
	
		$this->db->where('SocketID', $SocketID)->where('GameID', $Player['GameID'])->delete('Players');
		
		return $Game['Players'];	
	}
	
	public function removeGame($GameID)
	{
		$this->db->where("GameID", $GameID)->delete("GameMovies");
		$this->db->where("GameID", $GameID)->delete("GameSlots");
		$this->db->where("id", $GameID)->delete("Games");
	}
	
	public function getTimerID()
	{
		$Game=$this->db->where('id', $this->GameID)->getOne('Games');
		return $Game['TimerID'];
	}
	
	
	
	public function setTimerID($TimerID)
	{
		$data = Array ("TimerID" => $TimerID);	
		$this->db->where('id', $this->GameID)->update('Games', $data);
	}
	
	public function broadcastEveryone($key, $message)
	{
		
		$this->Sockets[0]->broadcast->emit($key, array(
            'username' => $Name,
            'numUsers' => $numUsers
        ));
			
	}
	
	public function broadcastGamePlayers($key, $message)
	{
		$Players=$this->db->where('GameID', $this->GameID)->get('Players');
		
		foreach($this->Sockets as $sock)
		{
			var_dump($sock->id);
		}
		foreach($Players as $Player)
		{
			//var_dump($Player);
			$Socket=$this->Sockets[$Player['SocketID']];
			//var_dump($key);
			//var_dump($message);
			$Socket->emit($key, $message);
		}
	}
	
	public function broadcastPlayer($PlayerID, $Key, $Message)
	{
		$Player=$this->db->where('id', $PlayerID)->getOne('Players');
		$Socket=$this->Sockets[$Player['SocketID']];
		
		$Socket->emit($Key, $Message);
	}
	
	public function updateSchedule($GameID, $SlotID, $CurrentSlot)
	{
		
	}
	
	public function broadcastSocketPlayer($SocketID, $Key, $Message)
	{
		$Socket=$this->Sockets[$SocketID];
		$Socket->emit($Key, $Message);
	}
	
	public function getRow($Table="Games", $id=null)
	{
		if($Table == "Games" && !$id) $id=$this->GameID;
		
		$Data=$this->db->where("id", $id)->getOne($Table);
		return $Data;
		
	}
	
	public function addAdsGame($HowMany)
	{
			$GameCompanies=$this->db->rawQuery("select * from Companies order by rand() LIMIT " . $HowMany);
			foreach($GameCompanies as $Company)
			{
				$cost=rand(100,500);
				$data = Array ("Name" => $Company['company_name'], "GameID" => $this->GameID, "PlayerID" => 0, "Cost" => $cost, "FailedCost" => round($cost/rand(5,20)), 'Duration' => rand(1,4), "CompanyID" => $Company['company_name_id']);	
				$this->db->insert ("GameAds", $data);
			}
	}
	

	
	public function addMoviesGame($HowMany)
	{
			$GameMovies=$this->db->rawQuery("select * from movie_metadata order by rand() LIMIT " . $HowMany);
			foreach($GameMovies as $Movie)
			{
				$Slots = ceil($Movie['duration'] / 30);
				$data = Array ("Name" => $Movie['movie_title'], "GameID" => $this->GameID, "PlayerID" => 0, "Cost" => ($Movie['imdb_score'] * 100), 'MovieType' => $Movie['genres'], 'Duration' => $Movie['duration'], 'Slots' => $Slots);	
				$movie_id=$this->db->insert ("GameMovies", $data);
				$genres=explode("|", $Movie['genres']);
				foreach($genres as $genre)
				{
					$Category=$this->db->where('Name', $genre)->getOne('Categories');
					$data = Array("GameID" => $this->GameID, "CategoryID" => $Category['id'], "MovieID" => $movie_id);
					$this->db->insert ("MovieCategories", $data);
				}
			}

	}
	
	public function scheduleRandom($PlayerID, $HowMany, $Day=0, $OnlyOneDay=false)
	{
		$Movies=$this->db->where('PlayerID', $PlayerID)->get('GameMovies', $HowMany);
		$Slots=[];
		foreach($Movies as $Movie)
		{
			for($x=1; $x<=$Movie['Slots']; $x++)
			{
				if(count($Slots) < 1){
					 $Slots=$this->db->where('GameID', $this->GameID)->orderBy('SlotOrder', 'asc')->get('GameSlots');
					 if(!$OnlyOneDay)
					 	$cnt++;
				}
				$NextSlot=array_shift($Slots);
				if($Movie['Slots'] > count($Slots)) $Slots=[];
				$data = Array("GameID" => $this->GameID, 
								"SlotID" => $NextSlot['id'], 
								"MovieID" => $Movie['id'],
								"AdID" => 0,
								"Day" => $Day,
								"TimeStart" => $NextSlot['TimeStart'], 
								"Name" =>  $NextSlot['Name'],
								"MoviePart" => $x,
								"PlayerID" => $PlayerID);

				$this->db->insert ("Schedules", $data);
			}		
		}	
	}
	
	public function updateDay($Counter, $Day)
	{
		$Day++;
		//$data = Array ('Day' => $Day, 'Counter' => $Counter - (24 * 60));	
		$data = Array ('Day' => $Day, 'Counter' => $this->GameStart);		
		$this->db->where('id', $this->GameID)->update('Games', $data);
	}
	
	public function getSchedule($Counter, $Day)
	{
		return $this->db->where('GameID', $this->GameID)->where('TimeStart', $Counter, '<')->where('Day', $Day)->orderBy('SlotID', 'desc')->getOne('Schedules');
	}
	
	public function getPlayers()
	{
		$Players=$this->db->where('GameID', $this->GameID)->get('Players');
		return $Players;
	}
	
	public function checkSchedule($GameData)
	{
		$Players=$this->getPlayers();
		$ReturnArray=array();
		$cnt=0;
		foreach($Players as $Player)
		{
			if($GameData['Counter']) {
				$ReturnArray[$Player['id']]=$this->db->rawQuery("select s.Name as ScheduleName, s.TimeStart, GameMovies.Name as MovieName, MovieType, Duration, Players.Name as PlayerName, s.MovieID, MoviePart from MovieGame.Schedules s, MovieGame.GameSlots gs, MovieGame.GameMovies, MovieGame.Players where s.SlotID=gs.id AND s.PlayerID=Players.id AND  
GameMovies.id=s.MovieID and s.GameID='" . $this->GameID . "' AND s.PlayerID='" . $Player['id'] . "' AND Day='" . $GameData['Day'] . "' AND (" . $GameData['Counter'] . " >= s.TimeStart AND " . ($GameData['Counter']) . " <=  s.TimeStart+30) order by SlotOrder DESC LIMIT 1");

			}


			//$Update=$this->db->rawQuery("update MovieGame.Schedules SET Status=1 where TimeStart < " . ($GameData['Counter']+60) . " AND Status = 0 AND GameID='" . $this->GameID . "' AND PlayerID='" . $Player['id'] . "' AND Day='" . $GameData['Day'] . "' ");
			$cnt++;
		}
		var_dump($ReturnArray);
		return $ReturnArray;
		
	}
	
	public function addMoviesPlayer($PlayerID, $HowMany)
	{
		$GameMovies=$this->db->rawQuery("update GameMovies set PlayerID='" . $PlayerID . "' where GameID='" . $this->GameID . "' and PlayerID < 1 order by rand() LIMIT " . $HowMany);
	
	}
	
	public function addAdsPlayer($PlayerID, $HowMany)
	{
		$GameMovies=$this->db->rawQuery("update GameAds set PlayerID='" . $PlayerID . "' where GameID='" . $this->GameID . "' and PlayerID < 1 order by rand() LIMIT " . $HowMany);
	
	}
	
	public function setupAudience()
	{
		$AgeGroups=$this->db->get('PeopleAgeGroups');
		foreach($AgeGroups as $AgeGroup)
		{
			
			$Cats=$this->db->get('Categories');

			$CatPop=$this->CityPopulation/count($Cats);
			$Population=rand(($CatPop / 5), ($CatPop * 5));
			$data = Array ("GameID" => $this->GameID, 
							"Name" => $AgeGroup['Name'], 
							"Population" => $Population);
			$PeopleID=$this->db->insert ("GamePeople", $data);
			
			foreach($Cats as $Cat)
			{
								
				$LikeLevel = rand(0, 100);
				$DislikeLevel = rand(0, (100-$LikeLevel));
				$ChangeLevel = rand(0, 10)-$AgeGroup['id'];
				if($ChangeLevel < 1) $ChangeLevel = 1;
			
				$data = Array ("GameID" => $this->GameID, 
								"AgeGroupID" => $PeopleID, 
								"CategoryID" => $Cat['id'], 
								"LikeLevel" => $LikeLevel, 
								"DislikeLevel" => $DislikeLevel, 
								"ChangeRate" => $ChangeLevel);
								
				$this->db->insert ("PeoplePreferences", $data);
			}
		}
		
	}
	
	public function addSlots($SlotTypeID)
	{
		$Slots=$this->db->get('Slots');
		foreach($Slots as $Slot)
		{
			$data = array("GameID" => $this->GameID,
						   "Name" => $Slot['Name'], 
						   "SlotOrder" => $Slot['SortOrder'],
						   "TimeStart" => $Slot['TimeInterval']);
			$this->db->insert ("GameSlots", $data);
				
		}
	}
	
	public function login($SocketID, $Name)
	{
			$InGame=$this->db->rawQuery("SELECT * FROM Players where SocketID LIKE '" . $SocketID . "' LIMIT 1");
			if($InGame)
			{
				$this->broadcastPlayer($InGame->id, 'newgame', 'newgame');
				return $InGame->GameID;
			}
			if(!$Name) $Name="Unknown Player";
			
			$SlotTypeID=1;
			$CheckGames=$this->db->rawQuery("SELECT * FROM Games where Players < 4 LIMIT 1");
			if(count($CheckGames) > 0)
			{
				
				$this->GameID=$CheckGames[0]['id'];
				$Updated=$this->db->rawQuery("UPDATE Games SET players=players+1 where id='" . $this->GameID . "'");			
				$Player=new Player($this->db);
				$PlayerID=$Player->addPlayerGame($this->GameID, $SocketID, $Name);
				$this->addMoviesPlayer($PlayerID, 5);
				$this->addAdsPlayer($PlayerID, 5);
				$this->broadcastPlayer($PlayerID, 'newgame', 'newgame');
				$this->scheduleRandom($PlayerID, 5);
	
			}
			else {					
				$data = Array ("Name" => "New Game", "SlotTypeID" => $SlotTypeID, 'Counter' => $this->GameStart, "Status" => "Play", "Heartbeat" => 1);	
				$this->GameID = $this->db->insert ("Games", $data);
			    $this->setupAudience();
		
				
				$Player=new Player($this->db);
				$PlayerID=$Player->addPlayerGame($this->GameID, $SocketID, $Name);
				
				$this->addSlots($SlotTypeID);
			//	$Slot = new Slot($this->db, $this->GameID);
			//	$Slot->createSlot($num_slots);
				
				$this->addMoviesGame(50);
				$this->addMoviesPlayer($PlayerID, 5);
				
				$this->addAdsGame(25);
				$this->addAdsPlayer($PlayerID, 5);
				
				$this->scheduleRandom($PlayerID, 5);
				
				//$Movie=new Movie($this->db, $GameID);
				//$Movie->createMovie(50);
				foreach($Player->Messages as $message)
				{
					$this->broadcastGamePlayers('gamemessage', $message);
				}
				//$Player->Messages=[];
					
					
				$this->broadcastPlayer($Player->PlayerID, 'newgame', 'newgame');
				$this->NewGame=true;
			}
			
			print $this->GameID;
			return $this->GameID;

	}

	/*
	public function getGameData($field)
	{
		if(array_key_exists($field, $this->data))
		{
			return $this->data[$field];
		}
		
		return null;
	}
	*/
}
?>

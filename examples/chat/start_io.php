<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


use Workerman\Worker;
use Workerman\WebServer;
use Workerman\Autoloader;
use Workerman\Lib\Timer;

use PHPSocketIO\SocketIO;
use PHPSocketIO\Models\DB;

use PHPSocketIO\Models\Game as Game;


//use PHPSocketIO\Models\Model_Games as Model_Games;
/*

use PHPSocketIO\Models\Slot as Slot;
use PHPSocketIO\Models\Player as Player;
*/

//use Illuminate\Database\Capsule\Manager as Capsule;



// composer autoload
require_once __DIR__ . '/../../vendor/autoload.php';

global $Sockets;
$Sockets = array();	


$Game=new Game($Sockets);
$Game->startWorld();
/*
$capsule = new Capsule;

$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => 'localhost',
    'database'  => 'MovieGame',
    'username'  => 'clf55_rob',
    'password'  => '',
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();
*/


//var_dump($Game->getGameData('MovieTypes'));
$io = new SocketIO(2020);
$io->on('connection', function($socket)  {
    $socket->addedUser = false;
	global $Sockets;
	$Game=new Game($Sockets);
	$Sockets[$socket->id]=$socket;
	
	
	$socket->on('login', function ($data) use($socket){
		global $Sockets;
		
		$Game=new Game($Sockets);
		$GameID=$Game->login($socket->id, $data);

		if($GameID > 0 && $Game->NewGame==true)
		{
			$GameData=$Game->getRow("Games", $GameID);
			$time_interval = $GameData['Heartbeat'];
			$TimerID=Timer::add($time_interval, function() use($socket, $GameID){
					global $Sockets;
					$Game=new Game($Sockets);
					$Game->GameID=$GameID;
					
					$GameData=$Game->getRow("Games", $GameID);
			
					if($GameData['Status'] == "Play")
					{
						$Game->incrementTimer($Game->GameID);
						var_dump($GameData['Counter']);
						$Game->broadcastGamePlayers("tick", array($GameData['Day'], $GameData['Counter']));
						
						//print $GameData['Counter']."-".$GameData['Day'];
						
						
						//$Schedule=$Game->getSchedule($GameData['Counter'], $GameData['Day']);
						
						
						if(($GameData['Counter'] / 60) > 24) {
							print "update counter " . $GameData['Counter'];
							$Game->updateDay($GameData['Counter'], $GameData['Day']);
						}
						//$ScheduleMessage=$Game->checkSchedule();
						//if($ScheduleMessage)
						//{
						//	$Game->broadcastGamePlayers("schedule", $ScheduleMessage);
						//}
						
						$ScheduleData=$Game->checkSchedule($GameData);
						if($ScheduleData) $Game->broadcastGamePlayers("scheduledata", $ScheduleData);
						
						$CurrentSlot=$GameData['Counter']%$GameData['Slot_Interval'];
						if($CurrentSlot<($GameData['Heartbeat']*$GameData['Speed']))
						{
						//	$this->updateSchedule($Game->GameID, "SlotID", $CurrentSlot);
							
							//var current_day=schedule_day=getScheduleDay(0, result);
							
							//models.Schedules.findOneAndUpdate({"game_id": game_id, day: current_day, "slot_id" : currentslot},{}).exec(function(err, scheduleresult) {
										
										//if(scheduleresult)
									//	{
											
											//broadcast_gameid('schedule',  scheduleresult.players, game_id);

									//	}
									//});
						}
						// var data={ time_count: Number(counter) };
						//		broadcast_gameid('tick', data, game_id);
					}
					elseif($GameData['Status'] == "Restart") {
						$Game->restartGame($Game->GameID);
						// broadcast_gameid('tick', data, game_id);
						
						
					}
					
					/*
								var currentslot=parseInt(result['counter'])%parseInt(result['slot_interval']);
								if(currentslot<(result['heartbeat']*result['speed']))
								{

									//models.Schedules.findOneAndUpdate({"game_id": game_id, "slot_id" : (currentslot-1) },{}).exec(function(err, schedulecalulate) {
									//});

									var current_day=schedule_day=getScheduleDay(0, result);

									//{ "price" : { "$exists" : false } }
									models.Schedules.findOneAndUpdate({"game_id": game_id, day: current_day, "slot_id" : currentslot},{}).exec(function(err, scheduleresult) {
										console.log('schedule result');
										console.log(scheduleresult);

										if(scheduleresult)
										{
											//var scheduleoutput = Array.prototype.slice.call(scheduleresult);
											console.log(scheduleresult.players);
											broadcast_gameid('schedule',  scheduleresult.players, game_id);

											//var data=Object.keys(scheduleresult).map(function (key) {return scheduleresult[key]});
											//var indents = [];
											//for (var i = 0; i < this.props.level; i++) {
	//										  indents.push(<span className='indent' key={i}></span>);
											//}
											//socket.emit('schedule',  data);
										}
									});
								}

						
					});
				  }, 2000);*/
					
					
					//$Game->updateCount
					
					/*
					$io->emit('new message', array(
						'username'=> 'admin',
						'message'=> 'blabla...'
					));
					
					models.Games.findOne({"_id": game_id}).exec(function(err, result) {
						//console.log('findone');
						//console.log(result);
						if(result)
						{
							counter=counter+(result['heartbeat']*result['speed']);
							models.Games.findOneAndUpdate({"_id": game_id, 'status': 'play'},{ $inc: { "counter": (result['heartbeat']*result['speed']) }}).exec(function(err2, result2){ });
							if(result['status']=="play")
							{
								counter=result['counter'];
								console.log('find game');
								var currentslot=parseInt(result['counter'])%parseInt(result['slot_interval']);
								if(currentslot<(result['heartbeat']*result['speed']))
								{

									//models.Schedules.findOneAndUpdate({"game_id": game_id, "slot_id" : (currentslot-1) },{}).exec(function(err, schedulecalulate) {
									//});

									var current_day=schedule_day=getScheduleDay(0, result);

									//{ "price" : { "$exists" : false } }
									models.Schedules.findOneAndUpdate({"game_id": game_id, day: current_day, "slot_id" : currentslot},{}).exec(function(err, scheduleresult) {
										console.log('schedule result');
										console.log(scheduleresult);

										if(scheduleresult)
										{
											//var scheduleoutput = Array.prototype.slice.call(scheduleresult);
											console.log(scheduleresult.players);
											broadcast_gameid('schedule',  scheduleresult.players, game_id);

											//var data=Object.keys(scheduleresult).map(function (key) {return scheduleresult[key]});
											//var indents = [];
											//for (var i = 0; i < this.props.level; i++) {
	//										  indents.push(<span className='indent' key={i}></span>);
											//}
											//socket.emit('schedule',  data);
										}
									});
								}

								var data={ time_count: Number(counter) };
								broadcast_gameid('tick', data, game_id);
							}		
							else if(result['status']=="restart")
							{
								counter=0;
								models.Games.findOneAndUpdate({"_id": game_id},{ day : 1, counter : 0, status: 'pause'}).exec(function(err2, result2){ });	
								broadcast_gameid('tick', data, game_id);
							}
						}
					});
				  }, 2000);
					*/
			 });
			 $Game->setTimerID($TimerID);
			 var_dump('timer');
			 var_dump($TimerID);
		}
		
	

		/*
		 $socket->emit('login', array( 
            'numUsers' => $numUsers
        ));
        // echo globally (all clients) that a person has connected
        $socket->broadcast->emit('user joined', array(
            'username' => $Name,
            'numUsers' => $numUsers
        ));
		*/
		
		
		
		
		
/*
		
		$Game=new Game($socket->id, $data['loginName']);
		$opengame=Game::where('players', '<', 4)->first();
		if(count($opengame) < 1)
		{
			print "in here";
			//$users = Capsule::table('users')->insert('votes', '>', 100)->get();
			$num_slots=8;
			//Game::insert('');
			$NewGame=new Game();
			$NewGame->Name="Game";
			$NewGame->Players=1;
			$NewGame->Speed=1;
			$NewGame->Day=1;
			$NewGame->Counter=0;
			$NewGame->Heartbeat=2;
			$NewGame->Population=10000;
			$NewGame->Slot_Interval=15;
			$NewGame->Number_Slots=$num_slots;
			
			var_dump($NewGame->toArray());
			$GameID=Capsule::table('Game')->insertGetID( $NewGame->toArray());
			var_dump($Game_ID);
			
			
		
			
			
			
			print "after";
			
				
			
		  for($j=1; $j <= $num_slots; $j++)
          {
			  $slot=new Slot();
			  $slot->name="Timeslot " + j;
			  $slot->GameID=$NewGame->ID;
			  $slot->SlotID=$j;
                       
          }

			
                                        util.setup_player_new_game(game_id, models, 10, socket.id);
                                        util.setup_player_new_game(game_id, models, 40, 0);
										*/
			
			// 
		
		//}
		
		
	
        // echo globally (all clients) that a person has connected
       // $socket->broadcast->emit('newgame', 'newgame'
       // );

		
		
        // we tell the client to execute 'new message'
     //   global $usernames, $numUsers;
        // we store the username in the socket session for this client
     //   $socket->username = $username;
        // add the client's username to the global list
      //  $usernames[$username] = $username;
      //  ++$numUsers;
      //  $socket->addedUser = true;
	  
	  /*
	  $Name="test";
	  
	  $numUsers=1;
        $socket->emit('login', array( 
            'numUsers' => $numUsers
        ));
        // echo globally (all clients) that a person has connected
        $socket->broadcast->emit('user joined', array(
            'username' => $Name,
            'numUsers' => $numUsers
        ));*/
    });
	
	
	$socket->on('autoSchedule', function ($data)use($socket){
		global $Sockets;
		
		$Game=new Game($Sockets, $socket->id);
		$Movies=$Game->scheduleRandom($PlayerID, 5, 2, true);
		$Game->broadcastSocketPlayer($socket->id, 'movies', $Movies);
		
    });
	
	$socket->on('getMovies', function ($data)use($socket){
		global $Sockets;
		
		$Game=new Game($Sockets, $socket->id);
		$Movies=$Game->getMovies($data);
		$Game->broadcastSocketPlayer($socket->id, 'movies', $Movies);
		
    });
	
	$socket->on('getSchedule', function ($data)use($socket){
		global $Sockets;
		
		$Game=new Game($Sockets, $socket->id);
		$Schedule=$Game->getSchedule($data);
		$Game->broadcastSocketPlayer($socket->id, 'schedule', $Schedule);
		
    });
	
	$socket->on('logout', function ($data)use($socket){
		global $Sockets;
		$Game=new Game($Sockets);
		$Game->broadcastSocketPlayer($socket->id, 'loggedoff', 'Logoff.');
		$NumberOfPlayers=$Game->removePlayer($socket->id);
		
		if($NumberOfPlayers < 2 )
		{
			$TimerID=$Game->getTimerID();
			Timer::del($TimerID);
			$Game->removeGame($Game->GameID);
		
		}
    });
	
	$socket->on('timercontrol', function ($data)use($socket){
		global $Sockets;
		print "in timer controls";
		$Game=new Game($Sockets, $socket->id);
		$Player=$Game->getPlayerSocket($socket);
		$Game->timerControls($data);
    });
	
	
	
	
	
	/*8888888888888888888888*/

    // when the client emits 'new message', this listens and executes
    $socket->on('new message', function ($data)use($socket){
        // we tell the client to execute 'new message'
        $socket->broadcast->emit('new message', array(
            'username'=> $socket->username,
            'message'=> $data
        ));
    });

    // when the client emits 'add user', this listens and executes
    $socket->on('add user', function ($username) use($socket){
        global $usernames, $numUsers;
        // we store the username in the socket session for this client
        $socket->username = $username;
        // add the client's username to the global list
        $usernames[$username] = $username;
        ++$numUsers;
        $socket->addedUser = true;
        $socket->emit('login', array( 
            'numUsers' => $numUsers
        ));
        // echo globally (all clients) that a person has connected
        $socket->broadcast->emit('user joined', array(
            'username' => $socket->username,
            'numUsers' => $numUsers
        ));
    });

    // when the client emits 'typing', we broadcast it to others
    $socket->on('typing', function () use($socket) {
        $socket->broadcast->emit('typing', array(
            'username' => $socket->username
        ));
    });

    // when the client emits 'stop typing', we broadcast it to others
    $socket->on('stop typing', function () use($socket) {
        $socket->broadcast->emit('stop typing', array(
            'username' => $socket->username
        ));
    });

    // when the user disconnects.. perform this
    $socket->on('disconnect', function () use($socket) {
        global $usernames, $numUsers;
        // remove the username from global usernames list
        if($socket->addedUser) {
            unset($usernames[$socket->username]);
            --$numUsers;

           // echo globally that this client has left
           $socket->broadcast->emit('user left', array(
               'username' => $socket->username,
               'numUsers' => $numUsers
            ));
        }
   });
   
});

if (!defined('GLOBAL_START')) {
    Worker::runAll();
}

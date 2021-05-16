<?php

require_once(__DIR__ . '/phpgraphlib.php');
require_once(__DIR__ . '/phpgraphlib_pie.php');

/** @var array $timeonline */
$timeonline = json_decode(file_get_contents("/home/crowdmc/plugin_data/Core/timeonline.js"), true);
$sessions = [];
/*date_default_timezone_set('UTC');
$date = 'l jS \of F Y h:i:s A';
echo "TigerLillyStaff Sessions: <br /><br />";
$i = 0;
foreach($timeonline["TigerLillyStaff"] as $session){
	foreach($session as $time => $sec){
		if(time() - $time > 86400 * 7){
			continue;
		}
	    //var_dump($time);
	    $i++;
		echo $i . ". " . date($date, $time) . " to " . date($date, $time + $sec) . "<br />";
	}
}
exit;*/
		
//XxKenyGamerxX,CatmanStaff,EliteStarWolfy,OddRageStaffAcc,CheruStaffAcc,ThroneStaff,DsrStaff,Insanestaff,TigerLillyStaff,JokerTRS

/** @var string $player */
$players = $_GET["players"] ?? null;
if(!is_string($players)){
	die("players parameter is not set.");
}
/** @var int $days */
$dayNo = min(7, intval($_GET["days"] ?? 7)); //Up to 7 days because of Mon-Sun limitation

$players = explode(",", $players);

if(count($players) === 1){
	$sessions = $timeonline[$players[0]] ?? [];
	$days = [];
	foreach($timeonline[$players[0]] ?? [] as $session){
		foreach($session as $start => $seconds){
			//$seconds += $seconds / 20; //1 tick compensation
			if(time() - $start > 86400 * 7){
				continue;
			}
			$day = date("D", $start); //Mon-Sun
			if(!isset($days[$day])){
				$days[$day] = 0;
			}
			$days[$day] += $seconds;
		}
	}
	foreach($days as $day => $seconds){
		$days[$day] = round($seconds / 3600, 1);
	}
	
	$days = array_slice($days, -$dayNo, $dayNo); //Last $dayNo Days
	if($pie){
		$graph = new PHPGraphLibPie(500, 350);
	}else{
		$graph = new PHPGraphLib(500, 350);
	}
	$graph->addData($days);
	$graph->setTitle("Time Online of $players[0] (Last $dayNo Days)");
	$graph->setBars(false);
	$graph->setLine(true);
	$graph->setDataPoints(true);
	$graph->setLineColor('green');
	$graph->setDataValues(true);
	$graph->setXValuesInterval(5);
	$graph->setLegend(true);
	$graph->setLegendTitle("Hours (" . array_sum($days) . "/14)");
	$graph->setGoalLine(2);
	$graph->setGoalLineColor('red');
	$graph->createGraph();;
}else{
	$concurrent = intval($_GET["concurrent"] ?? 0);
	
	$showAllTime = intval($_GET["alltime"] ?? 0);
	
	$days = [];

	foreach($players as $player){
		$alltime[$player] = 0;
		foreach($timeonline[$player] ?? [] as $session){
			foreach($session as $start => $seconds){
				if(time() - $start > 86400 * 7){
					continue;
				}
				$alltime[$player] += $seconds / 3600;
				
				$seconds += ($seconds / 20);
				$day = date("D", $start);
				$hour = date("G", $start);
				
				if($concurrent){
					if(!isset($secondsPerHour[$day][$hour])){
						$secondsPerHour[$day][$hour] = 0;
					}
					if($secondsPerHour[$day][$hour] >= 3600){
						continue;
					}
					if($seconds > 3600){
						$seconds = 3600 - $secondsPerHour[$day][$hour];
					}
					$secondsPerHour[$day][$hour] += $seconds;
				}
				
				if(!isset($days[$day])){
					$days[$day] = 0;
				}
				$days[$day] += $seconds / 3600;
			}
		}
	}
	
	$list = implode(", ", $players);
	if(strlen($list) > 20){
		$list = substr($list, 0, 20) . "...";
	}
	if($showAllTime){
		
		$graph = new PHPGraphLibPie(500, 350);
		$graph->addData($alltime);
		$graph->setTitle("Time Online of " . $list . " (Last $dayNo Days)");
		$graph->setLabelTextColor('50, 50, 50');
		$graph->setLegendTextColor('50, 50, 50');
		$graph->createGraph();
		exit;
	}
	if(count($players) === 0){
    	$days = array_slice($days, -24);
	}else{
		$days = array_slice($days, -$dayNo);
	}
	$graph = new PHPGraphLib(500, 350);
	$graph->addData($days);
	if(count($players) === 0){
		$graph->setLegendTitle("Minutes");
		$graph->setTitle("Average Game Session (GMT. Now: " . $hour . ")");
	}else{
		$graph->setLegendTitle("Hours");
		$graph->setTitle(($concurrent ? "Concurrent" : "") . " Time Online of " . $list . " (Last $dayNo Days)");
	}
	$graph->setBars(false);
	$graph->setLine(true);
	$graph->setDataPoints(true);
	$graph->setDataPointColor('maroon');
	$graph->setLineColor('green');
	$graph->setDataValues(false);
	$graph->setXValuesInterval(1);
	$graph->setLegend(true);
    $graph->createGraph();

}
<?php

define("ARG_PREFIX", "--");
\set_time_limit(-1);

if(\php_sapi_name() !== "cli"){
	echo "Please run in CLI." . PHP_EOL;
	die(1);
}
\array_shift($argv);
$arr = \array_chunk($argv, 2);
$args = [];
foreach($arr as $arr_){
	if(\count($arr_) !== 2 || \stripos($arr_[0], ARG_PREFIX) !== 0){
		echo "Invalid arguments." . PHP_EOL;
		die(2);
	}
	$args[\str_replace(ARG_PREFIX, "", $arr_[0])] = $arr_[1];
}

$term = !isset($args["search"]) ? (!isset($args["s"]) ? \null : $args["s"]) : $args["search"];
if($term === null){//or $_SERVER["_"]
	echo "Usage: " . PHP_BINDIR . " " . __FILE__ . PHP_EOL . "    * --search/s <term>" . PHP_EOL . "    * --extension/ext <ext>" . PHP_EOL . "    * --caseless/cl <bool>" . PHP_EOL;
	die(3);
}

$extension = (string) !isset($args["extension"]) ? (!isset($args["ext"]) ? \null : $args["ext"]) : $args["extension"];

$caseless = (bool) !isset($args["caseless"]) ? (!isset($args["cl"]) ? \null : $args["cl"]) : $args["caseless"];

$files = [];
findFiles($term);
echo "Search term: " . $term . PHP_EOL;
echo "Search result: " . PHP_EOL;
$start = \microtime(true);
function findFiles($term, $match = "*"){
	global $files;
	global $caseless;
	foreach(glob($match) as $file){
		if($file === basename(__FILE__)){
			continue;
		}elseif(\is_dir($file)){
			findFiles($term, $file . "/*");
		}elseif(@\pathinfo($file)["extension"] === "php"){
			$lines = file($file);
			foreach($lines as $i => $line){
				if($caseless){
					$valid = stripos($line, $term) !== \false;
				}else{
					$valid = strpos($line, $term) !== \false;
				}
				if($valid){
					$files[$file][] = [$i + 1, $line];
				}
			}
		}
	}
}
uasort($files, function(array $fileA, array $fileB) : int{
	foreach($fileA as $filenameA => $linesA){
		
	}
	foreach($fileB as $filenameB => $linesB){
		
	}
	return count($linesA) < count($linesB) ? -1 : 1;
});
foreach($files as $filename => $data){
	echo "* " . ((string) $filename) . " => ";
	foreach($data as $l){
		$i = 0;
		while($l[1][$i] === ""){
			$i++;
			unset($l[1][$i]);
		}
		if($l !== end($data)){
			echo "L" . $l[0] . ", ";
		}else{
			echo "L" . $l[0];
		}
	}
	echo PHP_EOL;
}
echo "Took: " . ((\microtime(true) - $start) * 1000) . "ms" . PHP_EOL;
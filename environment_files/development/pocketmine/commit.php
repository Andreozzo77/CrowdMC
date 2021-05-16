<?php

/**
 * An interactive CLI program to commit locally & remotely.
 *
 * @author kenygamer
 * @link https://kenygamer.com/
 */

define("GITHUB_USER", "kenygamer");
define("GITHUB_PASSWORD", "8f8d590f5241cdc4de1f10096c36b9b5f80e00b1"); //Or PAT
define("GITHUB_REPO", "EliteStar");
define("WORKING_DIRECTORY", "/home/elitestar/plugins");
define("DEV_BRANCH", "dev");
define("MASTER_BRANCH", "stable");

set_time_limit(-1);
ignore_user_abort(false);

/**
 * handy function to read STDIN
 * @return mixed
 */
function input(){
	$handle = fopen("php://stdin", "r");
	$line = fgets($handle);
	fclose($handle);
	return $line;
}

echo "What would you like to do?" . PHP_EOL;
echo "1.  Commit to " . DEV_BRANCH . " branch" . PHP_EOL;
echo "2.  Merge " . MASTER_BRANCH . " branch with " . DEV_BRANCH . PHP_EOL;

while(true){
	$input = input();
	switch(intval($input)){
		case 1:
		    echo "Enter commit description: " . PHP_EOL;
			while(true){
				$input = input();
				if(($input = trim($input)) === ""){
					echo "Commit description must not be empty. " . PHP_EOL;
				}else{
					break;
				}
			}
			echo "Are you sure you want to do this? [N/Y] " . PHP_EOL; //>&1
			if(strtoupper(trim(input())) === "Y"){
				shell_exec("cd " . WORKING_DIRECTORY . " ; git checkout " . DEV_BRANCH . " ; git add . 2>&1 ; sudo git commit -S -m \"" . $input . "\" ; sudo git push https://" . GITHUB_USER . ":" . GITHUB_PASSWORD . "@github.com/" . GITHUB_USER . "/" . GITHUB_REPO . ".git " . DEV_BRANCH);
				echo "Commited to " . DEV_BRANCH . PHP_EOL;
				echo $input . PHP_EOL;
			}else{
				echo "Aborted. " . PHP_EOL;
			}
		    exit;
		case 2:
		 	echo "Are you sure you want to merge branches? [N/Y] " . PHP_EOL;
			if(strtoupper(trim(input())) === "Y"){
				shell_exec("cd " . WORKING_DIRECTORY . " ; git checkout " . MASTER_BRANCH . " ; git merge " . DEV_BRANCH . " ; sudo git push https://" . GITHUB_USER . ":" . GITHUB_PASSWORD . "@github.com/" . GITHUB_USER . "/" . GITHUB_REPO . ".git " . MASTER_BRANCH);
				echo "Merged branches. " . PHP_EOL;
			}
		    exit;
		default:
			echo "Aborted. " . PHP_EOL;
			exit;
	}
}
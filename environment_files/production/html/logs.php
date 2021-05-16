<?php

$password = "[REDACTED]";
if(isset($_GET["id"])){
	if(!isset($_GET["password"]) || $_GET["password"] !== $password){
		?>
		<!DOCTYPE html>
		<html>
		<head><title>Logs</title></head>
		<body>
		<h1>Logs</h1>
		<form action="" method="get">
			<input type="hidden" name="id" value="<?=$_GET["id"];?>">
			<input type="password" name="password" value="" placeholder="...">
		</form>
		<?php
	}else{
		$data = json_decode(file_get_contents("https://jsonblob.com/api/jsonBlob/" . $_GET["id"]), true);
		if(isset($data["title"]) && isset($data["stacktrace"])){
			?>
			<!DOCTYPE html>
			<html>
			<head><title>Logs</title></head>
			<body>
			<h1>Logs</h1>
			<h2><?=base64_decode($data["title"]);?></h2>
			<code><?=implode("<br />", explode("\n", base64_decode($data["stacktrace"])));?></code>
			<?php
		}
	}
}

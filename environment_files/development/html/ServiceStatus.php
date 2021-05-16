<?php

//intval(shell_exec("sudo /bin/systemctl is-active --quiet $service 2>&1; echo $?"))
//0=active

function serviceActive(string $service) : bool{
	return intval(shell_exec("sudo /bin/systemctl is-active --quiet $service 2>&1; echo $?")) === 0;
}


function message($message){
	echo "<script type='text/javascript'>alert('" . $message . "');</script>";
	echo "<meta http-equiv='refresh' content='0 url=/ServiceStatus'>";
}
if(isset($_GET["restart"])){
	if($_GET["passwd"] !== "V92mNeGTZrxApe4m"){
		message("Wrong password!");
	}else{
		switch($_GET["service"]){
			case 4:
			    shell_exec("sudo /bin/systemctl restart transferserver 2>&1");
			    message("Restarted EliteStar Transfer Server");
			    exit;
			case 5:
			    shell_exec("sudo /bin/systemctl restart buildtests 2>&1");
			    message("Restarted EliteStar Build Tests Server");
			    exit;
			case 6:
			    shell_exec("sudo /bin/systemctl restart pmmp 2>&1");
			    message("Restarted EliteStar Test Server");
			    exit;
			case 1:
			case 2:
			case 3:
			    echo "<script type='text/javascript'>window.open('http://mcpe.life/ServiceStatus?service="  . $_GET["service"] . "&passwd=" . $_GET["passwd"] . "&restart');</script>";
			    break;
		}
	}
}
?>
<!DOCTYPE html>
<html>
	<head>
	    <title>Service Status</title>
    </head>
    <body>
    	<h1>Service Status (<?=$_SERVER['SERVER_ADDR']?>: ON, CA)</h1>
    	<ul>
    		<li><strong>EliteStar Bot (<a href="http://mcpe.life/ServiceStatus" target=_blank style=text-decoration:none>Check</a>)</strong>: Discord-MCPE account linkment system. Also provides !whois and !query Discord commands.</li>
    		<li><strong>EliteStar Server (<a href="http://mcpe.life/ServiceStatus" target=_blank style=text-decoration:none>Check</a>)</strong>: The main Minecraft server.</li>
    		<li><strong>EliteStar Transfer Server (<?php if(serviceActive("transferserver")){ echo "<font color='green'>Active</font>"; }else{ echo "<font color='gray'>Inactive (dead)</font>"; } ?>)</strong>: Minecraft server to which players are transferred when server restarts and sends them back to the main server.</li>
    		<li><strong>EliteStar Build Tests Server (<?php if(serviceActive("buildtests")){ echo "<font color='green'>Active</font>"; }else{ echo "<font color='gray'>Inactive (dead)</font>"; } ?>)</strong>: Minecraft server used to run build tests of potential builders.</li>
    		<li><strong>EliteStar Test Server (<?php if(serviceActive("pmmp")){ echo "<font color='green'>Active</font>"; }else{ echo "<font color='gray'>Inactive (dead)</font>"; } ?>)</strong>: Minecraft server where all development takes place.</li>
    	</ul>
    	<h2>Restart Service</h2>
    	<p>In case any service is down you can use this panel to force restart it.</p>
    	<form action="" method="get">
    		<select name="service">
    			<option value="1">EliteStar Bot</option>
    			<option value="3">EliteStar Server</option>
    			<option value="4">EliteStar Transfer Server</option>
    			<option value="5">EliteStar Build Tests Server</option>
    			<option value="6">EliteStar Test Server</option>
    		</select>
    		<input type="text" name="passwd" value="" placeholder="Password">
    		<input type="submit" name="restart" value="Restart" placeholder="">
    	</form>
    </body>
</html>
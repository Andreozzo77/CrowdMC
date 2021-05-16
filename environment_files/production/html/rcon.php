<?php

class Rcon
{
    private $host;
    private $port;
    private $password;
    private $timeout;
    private $socket;
    private $authorized = false;
    private $lastResponse = '';
    const PACKET_AUTHORIZE = 5;
    const PACKET_COMMAND = 6;
    const SERVERDATA_AUTH = 3;
    const SERVERDATA_AUTH_RESPONSE = 2;
    const SERVERDATA_EXECCOMMAND = 2;
    const SERVERDATA_RESPONSE_VALUE = 0;
    public function __construct($host, $port, $password, $timeout)
    {
        $this->host = $host;
        $this->port = $port;
        $this->password = $password;
        $this->timeout = $timeout;
    }
    public function getResponse()
    {
        return $this->lastResponse;
    }
    public function connect()
    {
        $this->socket = fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);
        if (!$this->socket) {
            $this->lastResponse = $errstr;
            var_dump($errstr);
            return false;
        }
        stream_set_timeout($this->socket, 3, 0);
        return $this->authorize();
    }
    public function disconnect()
    {
        if ($this->socket) {
                    fclose($this->socket);
        }
    }
    public function isConnected()
    {
        return $this->authorized;
    }
    public function sendCommand($command)
    {
        if (!$this->isConnected()) {
                    return false;
        }
        $this->writePacket(self::PACKET_COMMAND, self::SERVERDATA_EXECCOMMAND, $command);
        $response_packet = $this->readPacket();
        if ($response_packet['id'] == self::PACKET_COMMAND) {
            if ($response_packet['type'] == self::SERVERDATA_RESPONSE_VALUE) {
                $this->lastResponse = $response_packet['body'];
                return $response_packet['body'];
            }
        }
        return false;
    }
    private function authorize()
    {
        $this->writePacket(self::PACKET_AUTHORIZE, self::SERVERDATA_AUTH, $this->password);
        $response_packet = $this->readPacket();
        if ($response_packet['type'] == self::SERVERDATA_AUTH_RESPONSE) {
            if ($response_packet['id'] == self::PACKET_AUTHORIZE) {
                $this->authorized = true;
                return true;
            }
        }
        $this->disconnect();
        return false;
    }
    private function writePacket($packetId, $packetType, $packetBody)
    {
        $packet = pack('VV', $packetId, $packetType);
        $packet = $packet.$packetBody."\x00";
        $packet = $packet."\x00";
        $packet_size = strlen($packet);
        $packet = pack('V', $packet_size).$packet;
        fwrite($this->socket, $packet, strlen($packet));
    }
    private function readPacket()
    {
        $size_data = fread($this->socket, 4);
        $size_pack = unpack('V1size', $size_data);
        $size = $size_pack['size'];
        $packet_data = fread($this->socket, $size);
        $packet_pack = unpack('V1id/V1type/a*body', $packet_data);
        return $packet_pack;
    }
}

$timeout = 3;

$ip = $_GET['ip'] ?? "";
$port = $_GET['port'] ?? "";
$password = $_GET['passwd'] ?? "";
$command = $_GET['cmd'] ?? "";

$rateLimited = false;
$response = '';

if(empty($ip)) {
    $response = '<div class="alert alert-danger">Enter the server IP.</div>';
}elseif(empty($port)){
	$response = '<div class="alert alert-danger">Enter the server port.</div>';
}elseif(empty($command)) {
	$response = '<div class="alert alert-danger">Enter a command.</div>';
} elseif(empty($password)) {
    $response = '<div class="alert alert-danger">Enter the RCON password.</div>';
} else {
    session_start();
    
    $list = $_SESSION["commands"] ?? [];
    $commands = "";
    foreach(array_reverse($list) as $i => $cmd){
    	$commands .= "/" . $cmd . ($i === count($list) - 1 ? "" : "<br>");
    }
    
    if(isset($_SESSION['last'])) {
        $last = strtotime($_SESSION['last']);
        $curr = strtotime(date('Y-m-d h:i:s'));
        $sec = abs($last - $curr);
        if($sec <= 1) {
            $rateLimited = true;
            $left = (int) 1 - $sec;
            $response = '<div class="alert alert-danger">Wait before sending another command.</div>';
        }
    }
    $_SESSION['last'] = date('Y-m-d h:i:s');
    if(!$rateLimited) {
        $rcon = new Rcon($ip, $port, $password, $timeout);
        if($rcon->connect()) {
            $rcon->sendCommand($command);
            $resp = $rcon->getResponse();
            $response = str_replace("\n", "<br>", "<div class='alert alert-info'>$resp</div>");
            $rcon->disconnect();
            if(!isset($_SESSION["commands"])){
            	$_SESSION["commands"] = [];
            }
            $_SESSION["commands"][] = $command;
            if(count($_SESSION["commands"]) > 5){
            	array_shift($_SESSION["commands"]);
            }
        } else {
            $response = "<div class='alert alert-danger'>The supplied password was not accepted.</div>";
        }
    }
}
?>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>RCON Console</title>

    <!-- Bootstrap core CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/pace/1.0.2/themes/blue/pace-theme-minimal.css" rel="stylesheet">

    <!-- Pace.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pace/1.0.2/pace.js"></script>

    <!-- Custom styles for this template -->
    <style>body { padding-top: 40px; padding-bottom: 40px; background-color: #eee; } .form-signin { max-width: 330px; padding: 15px; margin: 0 auto; } .form-signin .form-signin-heading, .form-signin .checkbox { margin-bottom: 10px; } .form-signin .checkbox { font-weight: normal; } .form-signin .form-control { position: relative; height: auto; -webkit-box-sizing: border-box; box-sizing: border-box; padding: 10px; font-size: 16px; } .form-signin .form-control:focus { z-index: 2; } .form-signin input[type="text"] { margin-bottom: -1px; border-bottom-right-radius: 0; border-bottom-left-radius: 0; } .form-signin input[type="text2"] { margin-bottom: 10px; border-top-left-radius: 0; border-top-right-radius: 0; }</style>
  </head>
  <body>

    <div class="container">

    <?=$response?>

     <form class="form-signin" action="" method="get">
        <h2 class="form-signin-heading">RCON Client</h2>
        <?php if(!empty($ip) && !empty($port)){ echo '<p class="form-signin-heading">Connected to ' . gethostbyname($ip) . ':' . $port . ($commands !== '' ? '<br/><h2 class="form-signin-heading">Last Commands</h2><code>' . $commands . '</code>' : '') . '</p>'; } ?>
        <label for="inputEmail" class="sr-only">IP</label>
        <input type="text" id="inputEmail" name="ip" class="form-control" value="<?=$ip?>" placeholder="IP" required="" autofocus="">
        <label for="inputEmail" class="sr-only">Port</label>
        <input type="text" id="inputEmail" name="port" class="form-control" value="<?=$port?>" placeholder="Port" required="" autofocus=""><br />
        <label for="inputEmail" class="sr-only">Command</label>
        <input type="text" id="inputEmail" name="cmd" class="form-control" value="<?=$rateLimited ? $command : ''?>" placeholder="Command (no starting slash)" required="" autofocus="">
        <label for="inputPassword" class="sr-only">Password</label>
        <input type="text2" id="inputPassword" name="passwd" class="form-control" value="<?=$password?>" placeholder="Password" required="">
        <!-- div class="checkbox">
          <label>
            <input type="checkbox" value="remember-me"> Remember password
          </label>
        </div -->
        <button class="btn btn-lg btn-primary btn-block" type="submit">Run ></button>
      </form>


    </div> <!-- /container -->

</body>
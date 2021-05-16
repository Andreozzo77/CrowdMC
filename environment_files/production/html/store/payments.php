<?php

use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalHttp\HttpException;

require_once __DIR__ . "/vendor/autoload.php";

//payments.json must be cleaned out as SDK info is renewed
define("PAYPAL_SDK_CLIENT_ID", "AeFDzl7evZE2mbx-zN3CmEisyYZex-MqeJRavOY-axhQYlergtR6AqRGJDhFizd_fi-_FnTfJfqbqt8L");
define("PAYPAL_SDK_CLIENT_SECRET", "EI0Ax0vScLMh84nXmk60cI2NA9LryfQzAgs_wVAP7FfGNqTIZhLEzjXXK-0rN5_5Q8AcTwyRic6dy6iY");

ignore_user_abort(true);
set_time_limit(0);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

define("PAYMENTS_FILE", "payments.js");

/** @var array */
$payments = json_decode(file_get_contents(PAYMENTS_FILE), true);

switch($_GET["action"] ?? ""){
	case "capture":
	    if(isset($_GET["id"]) && isset($payments[$id = $_GET["id"]]) && !$payments[$id]["completed"] && isset($payments[$id]["orderId"])){	
	        $client = new PayPalHttpClient(new ProductionEnvironment(
                PAYPAL_SDK_CLIENT_ID,
                PAYPAL_SDK_CLIENT_SECRET)
            );
	        $orderId = $payments[$id]["orderId"];
	        $request = new OrdersCaptureRequest($orderId);
	        $request->prefer("return=representation");
        	try{
		        $response = $client->execute($request);
	        }catch(HttpException $ex){
		        header("Location: payment_error.txt?error=" . $ex->statusCode);
		        exit;
        	}
	        $payments[$id]["completed"] = true;
        	file_put_contents(PAYMENTS_FILE, json_encode($payments));
        	header("Location: payment_complete.txt");
        }
        break;
    case "pay":
        if(isset($_GET["id"]) && isset($payments[$_GET["id"]]) && isset($_GET["url"])){
        	header("Location: " . $_GET["url"]);
        	exit;
        }
        header("Location: payment_error.txt?error=-1");
}
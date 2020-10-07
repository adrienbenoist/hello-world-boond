<?php

require_once 'init.php';
require_once 'boondmanager.php';

$script = isset($_REQUEST['script']) ? $_REQUEST['script'] : '';

$boondmanager = new BoondManager(APP_KEY);

var_dump($_REQUEST);
echo("<br>script request {$_REQUEST['script']}!<br>");
echo("signedRequest request {$_REQUEST['signedRequest']}!<br>");

// decoding data sent by boondmanager
// $data = $boondmanager->signedRequestDecode($_REQUEST['signedRequest']);
$data = array(
	"urlCallback" => "https:\/\/ui.boondmanager.com\/apps\/1420",
	"userToken" => "31382e6c6f69635f6c616d62657274",
	"clientToken" => "6c6f69635f6c616d62657274",
	"language" => "fr",
	"issuedAt" => "2020-10-06T14:24:57+0200"
);
// $data = json_encode($data);
echo("data $data!<br>");

switch($script) {
	// installation api
	case 'install':
		// install script needs an answer in json
		header('Content-Type: application/json');

		// handle first request with installationCode
		if($data && isset($data['installationCode']) && $data['installationCode'] == APP_CODE) {
			exit(json_encode([
				'result' => true
			]));
		}

		// handle second request with appToken
		if($data && isset($data['appToken']) && isset($data['clientToken']) && isset($data['clientName'])) {
			// backup the customer token (for example, in a database)
			file_put_contents('appToken.txt', $data['appToken']);

			exit(json_encode([
				'result' => true
			]));
		}

		 //if something went wrong, return false
		exit(json_encode([
			'result' => false
		]));
		break;

	// uninstall api
	case 'uninstall':
		// install script needs an answer in json
		header('Content-Type: application/json');

		if($data && isset($data['clientToken'])) {//Authorization's confirmation
			// deleting the token
			if(file_exists('appToken.txt')) {
				unlink('appToken.txt');
			}

			exit(json_encode([
				'result' => true
			]));
		}

		exit(json_encode([
			'result' => false
		]));
		break;

	// configuration's view
	case 'configuration':
		echo 'Configuration Page';
		break;

	// main's view
	default:
		$boondmanager->setAppToken( file_get_contents('appToken.txt') ); // TODO: Check the app tocken that I think it is not ok right now
		$boondmanager->setUserToken( $data['userToken'] );

		$response = $boondmanager->callApi('application/current-user');
		if(!$response) {
			echo('You do not have right for this api !<br>');
		}

		$function = isset($_REQUEST['function']) ? $_REQUEST['function'] : '';
		$firstName = $response->data->attributes->firstName;
		$lastName = $response->data->attributes->lastName;

		$responseCandidates = $boondmanager->callApi('candidates');
		if(!$responseCandidates) {
			$responseCandidates = 'Failed call candidates<br>';
		}
		

		$page = <<<CONTENT
		<!DOCTYPE html>
		<html>
			<head>
					<script type="text/javascript" src="https://ui.boondmanager.com/sdk/boondmanager.js"></script>
			</head>
			<body>
				<script type="text/javascript">
						
				</script>
				
				<h1>{$function}</h1>
				
				<p>Hello {$firstName} {$lastName} </p>
				
				<p id="paragraph2" style="display: none"> Text hidden which is now visible</p>
				
				<hr/>
				
				<div>
					<a href="#" onclick="BoondManager.redirect('/candidates?perimeterAgencies=%5B%223%22%5D');" class="button">go to candidates</a>
					<p>The candidates result {$responseCandidates}</p>
				</div>
				
				<script>
					BoondManager.init({
						targetOrigin : '*'
					}).then( () => {
								BoondManager.setAutoResize();
						})
				</script>
			</body>
		</html>
		CONTENT;

		echo $page;
		break;
}





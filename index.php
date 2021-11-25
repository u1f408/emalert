<?php declare(strict_types=1);
define("X_BASE_DIR", dirname(($p = realpath(__FILE__)) === false ? __FILE__ : $p));

// Hack to allow PHP development server to serve static files
if (php_sapi_name() === 'cli-server') {
	$fileName = X_BASE_DIR . parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
	if (file_exists($fileName) && !is_dir($fileName)) return false;
}

// Load configuration
if (!file_exists(X_BASE_DIR . "/config.php")) die("No configuration found");
require_once(X_BASE_DIR . "/config.php");

// Define some helper functions
function emalert_write_header() {
	echo '<!DOCTYPE html><html lang="en">';
	echo '<head><title>emalert</title>';
	echo '<meta name="viewport" content="initial-scale=1, width=device-width">';
	echo '<link rel="stylesheet" href="/styles.css">';
	echo '</head><body>';
}

function emalert_write_footer() {
	echo '</body></html>';
}

function emalert_write_token_form() {
	emalert_write_header();
	echo '<form class="form box constrain" method="get">';
	echo '<input name="t" type="password" placeholder="Token">';
	echo '<button type="submit">Continue</button>';
	echo '</form>';
	emalert_write_footer();
}

function emalert_write_message_form(array $token_data) {
	emalert_write_header();
	echo '<form class="form box constrain" method="post">';
	echo "<h1>Hello, {$token_data['name']}</h1>";
	echo '<input name="m" type="text" placeholder="Alert message">';
	echo '<button type="submit">Send alert</button>';
	echo '</form>';
	emalert_write_footer();
}

function emalert_write_success(array $token_data) {
	emalert_write_header();
	echo '<div class="box constrain">';
	echo "<h1>Alert sent!</h1>";
	echo '</div>';
	emalert_write_footer();
}

// Define the alerter functions
function emalert_alerter_discord(array $alerter, array $token_data, string $message) {
	$post = json_encode([
		"content" => "From **{$token_data['name']}**\n> {$message}",
		"tts" => true,
	]);

	$ch = curl_init($alerter['webhook_url']);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

	curl_exec($ch);
	curl_close($ch);	
}

function emalert_alerter_pushover(array $alerter, array $token_data, string $message) {
	$title = "Emergency alert: {$token_data['name']}";
	foreach ($alerter['users'] as $user_token) {
		$post = [
			'token' => $alerter['token'],
			'user' => $user_token,
			'title' => $title,
			'message' => $message,
			'priority' => '2',
			'retry' => '30',
			'expire' => '10800',
		];

		$ch = curl_init("https://api.pushover.net/1/messages.json");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

		curl_exec($ch);
		curl_close($ch);	
	}
}

// Check if we have a token, display prompt page if not
$token = array_key_exists("t", $_GET) ? trim($_GET['t']) : null;
if ($token === null || $token === "" || !array_key_exists($token, EMALERT_TOKENS)) {
	emalert_write_token_form();
	exit;
} else if (is_null($token_data = EMALERT_TOKENS[$token])) {
	emalert_write_token_form();
	exit;
}

// `$token_data` contains our token data, show the alert page if we don't have a message
$message = array_key_exists("m", $_POST) ? trim($_POST["m"]) : null;
if ($message === null || $message === "") {
	emalert_write_message_form($token_data);
	exit;
}

// We have a message, send the alerts
foreach (EMALERT_ALERTERS as $idx => $alerter) {
	$alert_fn = "emalert_alerter_{$alerter['type']}";
	if (function_exists($alert_fn)) {
		$alert_fn($alerter, $token_data, $message);
	}
}

emalert_write_success($token_data);

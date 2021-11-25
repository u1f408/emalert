<?php declare(strict_types=1);
if (!defined("BASE_DIR")) die;

define("EMALERT_TOKENS", [
	"abcd" => [
		"name" => "Iris System",
	],
]);

define("EMALERT_ALERTERS", [
	[
		"type" => "discord",
		"webhook_url" => "https://discord.com/api/webhooks/...",
	],
	[
		"type" => "pushover",
		"token" => "abcd",
		"users" => ["abcd"],
	],
]);

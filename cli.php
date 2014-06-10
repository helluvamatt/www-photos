<?php
$app = require "bootstrap.php";

if (!isset($argv[1]))
{
	echo "\nUsage: php cli.php <command> [<options> ...]\n";
	exit(1);
}
$cmd = $argv[1];

if ($cmd === "adduser")
{
	if ($argc < 3)
	{
		print "\nUsage: php cli.php adduser <username> <password>\n";
		exit(1);
	}
	$username = $argv[2];
	$password = $argv[3];
	$hash = password_hash($password, PASSWORD_BCRYPT);
	if ($hash === FALSE)
	{
		throw new RuntimeException("Failed to hash user password.", -1);
	}
	
	$new_user = User::create(array(
		"username" => $username,
		"email" => '',
		"password" => $hash
	));
	
	if ($new_user)
	{
		echo "\nUser '$username' successfully added.\n";
	}
	else
	{
		echo "\nThere was a problem creating the record.\n";
	}
}

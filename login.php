<?php
session_start();

if (isset($_SESSION["logged"]) && $_SESSION["logged"] != "") {
	header ("Location: panel.php");
	exit;
}

require_once("Db.php");
$nick = @$_POST["nick"];
$password = @$_POST["password"];

if ($nick === null || $password === null) {
  header("Location: index.php?invalid");
  exit;
}

$rawPasswordDb = "";

Db::connect();

foreach (Db::queryAll("SELECT * FROM auth WHERE name = LOWER(?);", $nick) as $row) {
	$rawPasswordDb = $row["password"];
}

if ($rawPasswordDb === "") {
  header("Location: index.php?invalid");
  exit;
}

$parts = explode("\$", $rawPasswordDb);
$salt = $parts[2];

$rawPassword = "\$SHA\$" . $salt . "\$" . hash("sha256", hash("sha256", $password) . $salt);

if ($rawPasswordDb !== $rawPassword) {
	header("Location: index.php?invalid");
	exit;
}

// Credentials OK
$_SESSION["logged"] = $nick;
header("Location: panel.php");
?>
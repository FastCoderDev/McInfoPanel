<?php
// https://stackoverflow.com/questions/53796098/php-minecraft-offline-uuid
function offlinePlayerUuid($username) {
    //extracted from the java code:
    //new GameProfile(UUID.nameUUIDFromBytes(("OfflinePlayer:" + name).getBytes(Charsets.UTF_8)), name));
    $data = hex2bin(md5("OfflinePlayer:" . $username));
    //set the version to 3 -> Name based md5 hash
    $data[6] = chr(ord($data[6]) & 0x0f | 0x30);
    //IETF variant
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return createJavaUuid(bin2hex($data));
}

function createJavaUuid($striped) {
    //example: 069a79f4-44e9-4726-a5be-fca90e38aaf5
    $components = array(
        substr($striped, 0, 8),
        substr($striped, 8, 4),
        substr($striped, 12, 4),
        substr($striped, 16, 4),
        substr($striped, 20),
    );
    return implode('-', $components);
}

session_start();

if (!isset($_SESSION["logged"]) || $_SESSION["logged"] == "") {
	header ("Location: index.php");
	exit;
}

$nick = $_SESSION["logged"];

require_once("Db.php");
Db::connect();

$group = "default";
foreach (Db::queryAll("SELECT * FROM luckperms_players WHERE username = LOWER(?);", $nick) as $row) {
	$group = $row["primary_group"];
}

$uuid = offlinePlayerUuid($nick);

$isFullPerms = false;
foreach (Db::queryAll("SELECT id FROM luckperms_user_permissions WHERE uuid = ? AND permission = '*' AND value = 1;", $uuid) as $row) {
	$isFullPerms = true;
}

if (!$isFullPerms && $group != "majitel") {
	die ("perms");
}

$status = "false";
foreach (Db::queryAll("SELECT * FROM maintenance_settings WHERE setting = 'maintenance';") as $row) {
	$status = $row["value"];
}

if ($status == "false")
	$newStatus = "true";
else
	$newStatus = "false";

Db::query("UPDATE maintenance_settings SET value = ? WHERE setting = 'maintenance';", $newStatus);

$stream = fopen("log/log.txt", "a");
fwrite($stream, "\nMaintenance toggle - admin=" . $nick);
fclose($stream);

echo ($newStatus);
?>
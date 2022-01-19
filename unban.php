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

$target = @$_POST["nick"];

if($target == null){
    exit;
}

session_start();

if (!isset($_SESSION["logged"]) || $_SESSION["logged"] == "") {
	header ("Location: index.php");
	exit;
}

$nick = $_SESSION["logged"];
$uuid = offlinePlayerUuid($nick);

require_once("Db.php");
Db::connect();

$group = "default";
foreach (Db::queryAll("SELECT * FROM luckperms_user_permissions WHERE uuid = ? AND permission LIKE 'group.%' ORDER BY id DESC LIMIT 1;", $uuid) as $row) {
	$group = substr($row["permission"], 6);
}

$isFullPerms = false;
foreach (Db::queryAll("SELECT id FROM luckperms_user_permissions WHERE uuid = ? AND permission = '*' AND value = 1;", $uuid) as $row) {
	$isFullPerms = true;
}

if (!$isFullPerms && $group != "helper" && $group != "elhelper" && $group != "hlhelper" && $group != "vedeni" && $group != "majitel") {
	die ("perms");
}

$targetUuid = offlinePlayerUuid($target);

Db::query("UPDATE litebans_bans SET removed_by_uuid = ? , removed_by_name = ? , active = 0 WHERE uuid = LOWER(?);", $uuid, $nick, $targetUuid);

Db::query("INSERT INTO `adminka_log` (`admin`, `target`, `action`) VALUES (?, ?, 'Unban');", $nick, $target);

echo "ok";

?>
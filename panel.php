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

function getPlayeruuid($player) {
	$rv = json_decode(file_get_contents("https://api.mojang.com/users/profiles/minecraft/{$player}"), 1);
	return (empty($rv["id"]) ? "null" : $rv["id"]);
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

$groupname = "Hráč";
foreach (Db::queryAll("SELECT * FROM luckperms_group_permissions WHERE name = ? AND permission LIKE 'displayname.%';", $group) as $row) {
	$groupname = substr($row["permission"], 12);
}

$coins = "0";
foreach (Db::queryAll("SELECT * FROM playerpoints_points WHERE uuid = LOWER(?);", $uuid) as $row) {
	$coins = $row["points"];
}

$servercoins = "0";
foreach (Db::queryAll("SELECT SUM(`points`) AS `kredity` FROM `playerpoints_points`;") as $row) {
	$servercoins = $row["kredity"];
}

$isFullPerms = false;
foreach (Db::queryAll("SELECT id FROM luckperms_user_permissions WHERE uuid = ? AND permission = '*' AND value = 1;", $uuid) as $row) {
	$isFullPerms = true;
}

$json = file_get_contents('https://api.mcsrvstat.us/2/play.midcore.cz');
$server = json_decode($json);

$canUnregister = $isFullPerms || $group == "elhelper" || $group == "hlhelper" || $group == "vedeni" || $group == "majitel";
$canUnban = $isFullPerms || $group == "helper" || $group == "elhelper" || $group == "hlhelper" || $group == "vedeni" || $group == "majitel";
$canMaintenance = $isFullPerms || $group == "majitel";
$canLog = $isFullPerms || $group == "majitel";

?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MidCore.cz | Panel</title>
	<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/js/bootstrap.bundle.min.js" integrity="sha384-b5kHyXgcpbZJO/tY9Ul7kGkf1S0CWuKcCD38l8YkeH8z8QjE0GmW1gYU5S9FOnJ0" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BmbxuPwQa2lc/FVzBcNJ7UAyJxM6wuqIj61tLrc4wSX0szH/Ev+nYRRuWlolflfl" crossorigin="anonymous">
	<script data-ad-client="ca-pub-1836565616510693" async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>	
    <link rel="stylesheet" href="assets/css/dash.css">
</head>
<body>
	<main id="admin">
		<div class="wrapper">
			<nav id="sidebar">
				<div class="sidebar-header">
					<p>MidCore.cz</p>
				</div>
				<div class="user">
					<img src="https://visage.surgeplay.com/bust/128/<?=getPlayeruuid($nick)?>">
					<h2><?=$nick?></h2>
				</div>
				<ul class="list-unstyled">
					<div class="section">
						<p>Hlavní Nabídka</p>
					</div>
					<li class="pt-1 active">
						<a href="/admin">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-speedometer2" viewBox="0 0 16 16">
								<path d="M8 4a.5.5 0 0 1 .5.5V6a.5.5 0 0 1-1 0V4.5A.5.5 0 0 1 8 4zM3.732 5.732a.5.5 0 0 1 .707 0l.915.914a.5.5 0 1 1-.708.708l-.914-.915a.5.5 0 0 1 0-.707zM2 10a.5.5 0 0 1 .5-.5h1.586a.5.5 0 0 1 0 1H2.5A.5.5 0 0 1 2 10zm9.5 0a.5.5 0 0 1 .5-.5h1.5a.5.5 0 0 1 0 1H12a.5.5 0 0 1-.5-.5zm.754-4.246a.389.389 0 0 0-.527-.02L7.547 9.31a.91.91 0 1 0 1.302 1.258l3.434-4.297a.389.389 0 0 0-.029-.518z"/>
								<path fill-rule="evenodd" d="M0 10a8 8 0 1 1 15.547 2.661c-.442 1.253-1.845 1.602-2.932 1.25C11.309 13.488 9.475 13 8 13c-1.474 0-3.31.488-4.615.911-1.087.352-2.49.003-2.932-1.25A7.988 7.988 0 0 1 0 10zm8-7a7 7 0 0 0-6.603 9.329c.203.575.923.876 1.68.63C4.397 12.533 6.358 12 8 12s3.604.532 4.923.96c.757.245 1.477-.056 1.68-.631A7 7 0 0 0 8 3z"/>
							</svg>
							Hlavní stránka
						</a>
					</li>
				</ul>
			</nav>
			<div id="content">
				<div class="navgtn">
					<svg id="menu" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-list icon mr-auto" viewBox="0 0 16 16">
  						<path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"/>
					</svg>
					<svg onclick="window.location.href='logout.php'" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-box-arrow-in-right icon ml-auto" viewBox="0 0 16 16">
						<path fill-rule="evenodd" d="M6 3.5a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v9a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-2a.5.5 0 0 0-1 0v2A1.5 1.5 0 0 0 6.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2h-8A1.5 1.5 0 0 0 5 3.5v2a.5.5 0 0 0 1 0v-2z"/>
						<path fill-rule="evenodd" d="M11.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 1 0-.708.708L10.293 7.5H1.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3z"/>
					</svg>
				</div>
				<div class="container">
					<div class="row mt-5">
						<h1 class="title">Informace</h1>
						<div class="col-lg-3">
							<div class="card box2">
								<div class="card-body">
									<div class="align-items-center row">
										<div class="ftcntnt">
											<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person" viewBox="0 0 16 16">
												<path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4zm-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10z"/>
											</svg>
										</div>
										<div class="ftcntnt">
											<h2><?php echo $server->players->online; ?></h2>
											<span>Online hráčů</span>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="col-lg-3">
							<div class="card box1">
								<div class="card-body">
									<div class="align-items-center row">
										<div class="ftcntnt">
											<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-tag" viewBox="0 0 16 16">
												<path d="M6 4.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-1 0a.5.5 0 1 0-1 0 .5.5 0 0 0 1 0z"/>
												<path d="M2 1h4.586a1 1 0 0 1 .707.293l7 7a1 1 0 0 1 0 1.414l-4.586 4.586a1 1 0 0 1-1.414 0l-7-7A1 1 0 0 1 1 6.586V2a1 1 0 0 1 1-1zm0 5.586 7 7L13.586 9l-7-7H2v4.586z"/>
											</svg>
										</div>
										<div class="ftcntnt">
											<h2><?php echo $groupname; ?></h2>
											<span>Hodnost</span>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="col-lg-3">
							<div class="card box3">
								<div class="card-body">
									<div class="align-items-center row">
										<div class="ftcntnt">
											<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-coin" viewBox="0 0 16 16">
												<path d="M5.5 9.511c.076.954.83 1.697 2.182 1.785V12h.6v-.709c1.4-.098 2.218-.846 2.218-1.932 0-.987-.626-1.496-1.745-1.76l-.473-.112V5.57c.6.068.982.396 1.074.85h1.052c-.076-.919-.864-1.638-2.126-1.716V4h-.6v.719c-1.195.117-2.01.836-2.01 1.853 0 .9.606 1.472 1.613 1.707l.397.098v2.034c-.615-.093-1.022-.43-1.114-.9H5.5zm2.177-2.166c-.59-.137-.91-.416-.91-.836 0-.47.345-.822.915-.925v1.76h-.005zm.692 1.193c.717.166 1.048.435 1.048.91 0 .542-.412.914-1.135.982V8.518l.087.02z"/>
												<path fill-rule="evenodd" d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
												<path fill-rule="evenodd" d="M8 13.5a5.5 5.5 0 1 0 0-11 5.5 5.5 0 0 0 0 11zm0 .5A6 6 0 1 0 8 2a6 6 0 0 0 0 12z"/>
											</svg>
										</div>
										<div class="ftcntnt">
											<h2><?php echo $coins; ?></h2>
											<span>Kredity</span>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="col-lg-3">
							<div class="card box4">
								<div class="card-body">
									<div class="d-flex row">
										<div class="ftcntnt">
											<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bank" viewBox="0 0 16 16">
												<path d="M8 .95 14.61 4h.89a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5H15v7a.5.5 0 0 1 .485.379l.5 2A.5.5 0 0 1 15.5 17H.5a.5.5 0 0 1-.485-.621l.5-2A.5.5 0 0 1 1 14V7H.5a.5.5 0 0 1-.5-.5v-2A.5.5 0 0 1 .5 4h.89L8 .95zM3.776 4h8.447L8 2.05 3.776 4zM2 7v7h1V7H2zm2 0v7h2.5V7H4zm3.5 0v7h1V7h-1zm2 0v7H12V7H9.5zM13 7v7h1V7h-1zm2-1V5H1v1h14zm-.39 9H1.39l-.25 1h13.72l-.25-1z"/>
											</svg>
										</div>
										<div class="ftcntnt">
											<h2><?php echo $servercoins; ?></h2>
											<span>Oběh kreditů</span>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="row mt-5">
						<h1 class="title">Akce</h1>
						<?php
							if ($canLog || $canMaintenance) {
								echo '<div class="col-md-8">';
							} else {
								echo '<div class="col-md-12">';
							}
						?>
						<?php
							if($canUnban){
						?>
							<div class="card">
								<div class="card-title unban gradient">
									<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-slash-circle" viewBox="0 0 16 16">
										<path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
										<path d="M11.354 4.646a.5.5 0 0 0-.708 0l-6 6a.5.5 0 0 0 .708.708l6-6a.5.5 0 0 0 0-.708z"/>
									</svg>
									<h1>Unban</h1>
								</div>
								<div class="card-body">
									<form onsubmit="return handleUnban()" class="d-flex">
										<input id="unbanNick" class="form-control me-2" type="text" placeholder="Nickname" aria-label="Nickname">
										<button class="btn gradient" type="submit">Unban</button>
									</form>
								</div>
							</div>
						<?php
							}
						?>
						<?php
							if($canUnregister) {
						?>
							<div class="card mt-4">
								<div class="card-title unregister gradient">
									<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-dash" viewBox="0 0 16 16">
										<path d="M6 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H1s-1 0-1-1 1-4 6-4 6 3 6 4zm-1-.004c-.001-.246-.154-.986-.832-1.664C9.516 10.68 8.289 10 6 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10z"/>
										<path fill-rule="evenodd" d="M11 7.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 0 1h-4a.5.5 0 0 1-.5-.5z"/>
									</svg>
									<h1>Unregister</h1>
								</div>
								<div class="card-body">
									<form onsubmit="return handleUnregister()" class="d-flex">
										<input id="unregisterNick" class="form-control me-2" type="text" placeholder="Nickname" aria-label="Nickname">
										<button class="btn gradient" type="submit">Unregister</button>
									</form>
								</div>
							</div>
						<?php
							}
						?>
						</div>
						<?php
							if ($canLog || $canMaintenance) {
						?>
							<div class="col-md-4">
							<?php
								if ($canMaintenance) {
							?>
								<div class="card">
									<div class="card-title maintenance gradient">
										<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-gear" viewBox="0 0 16 16">
											<path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492zM5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0z"/>
											<path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52l-.094-.319zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 0 0 2.693 1.115l.291-.16c.764-.415 1.6.42 1.184 1.185l-.159.292a1.873 1.873 0 0 0 1.116 2.692l.318.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 0 0-1.115 2.693l.16.291c.415.764-.42 1.6-1.185 1.184l-.291-.159a1.873 1.873 0 0 0-2.693 1.116l-.094.318c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 0 0-2.692-1.115l-.292.16c-.764.415-1.6-.42-1.184-1.185l.159-.291A1.873 1.873 0 0 0 1.945 8.93l-.319-.094c-.835-.246-.835-1.428 0-1.674l.319-.094A1.873 1.873 0 0 0 3.06 4.377l-.16-.292c-.415-.764.42-1.6 1.185-1.184l.292.159a1.873 1.873 0 0 0 2.692-1.115l.094-.319z"/>
										</svg>
										<h1>Údržba</h1>
									</div>
									<div class="card-body">
										<form onsubmit="return handleMaintenance()">
											<button class="btn gradient wdthmax" type="submit">Přepnout stav údržby</button>
										</form>
									</div>
								</div>
							<?php
								}
								if ($canLog) {
							?>
								<div class="card mt-4">
									<div class="card-title log gradient">
										<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-journal-code" viewBox="0 0 16 16">
											<path fill-rule="evenodd" d="M8.646 5.646a.5.5 0 0 1 .708 0l2 2a.5.5 0 0 1 0 .708l-2 2a.5.5 0 0 1-.708-.708L10.293 8 8.646 6.354a.5.5 0 0 1 0-.708zm-1.292 0a.5.5 0 0 0-.708 0l-2 2a.5.5 0 0 0 0 .708l2 2a.5.5 0 0 0 .708-.708L5.707 8l1.647-1.646a.5.5 0 0 0 0-.708z"/>
											<path d="M3 0h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2v-1h1v1a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H3a1 1 0 0 0-1 1v1H1V2a2 2 0 0 1 2-2z"/>
											<path d="M1 5v-.5a.5.5 0 0 1 1 0V5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1zm0 3v-.5a.5.5 0 0 1 1 0V8h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1zm0 3v-.5a.5.5 0 0 1 1 0v.5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1z"/>
										</svg>
										<h1>Log</h1>
									</div>
									<div class="card-body nopd">
										<div class="table-responsive">
											<table class="table table-striped mb-0">
												<thead>
    												<tr>
    													<th>Admin</th>
    													<th>Uživatel</th>
    													<th>Akce</th>
    												</tr>
												</thead>
												<tbody>
													<?php
														if ($canLog) {
															foreach (Db::queryAll("SELECT * FROM adminka_log") as $row) {
																echo "<tr>
																	<th>{$row['admin']}</th>
																	<th>{$row['target']}</th>
																	<th>{$row['action']}</th>
														  		</tr>";
															}
														}
													?>
												</tbody>
											</table>
										</div>
									</div>
								</div>
							<?php
								}
							?>
							</div>
						<?php
							}
						?>
					</div>
				</div>
				<div class="footer">
					<p>© 2020-<?php echo date('Y')?> MidCore.eu</p>
				</div>
			</div>
		</div>
	</main>

	<?php
	if ($canUnregister) {
	?>
	<div class="modal fade" id="unregisterOk" tabindex="-1" role="dialog" aria-labelledby="unregiserOkLabel" aria-hidden="true">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="unregiserOkLabel">Úspěch</h5>
				</div>
				<div class="modal-body">
					Unregister proběhl úspěšně!
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-danger" onclick="closeUnregisterOk()">Zavřít</button>
				</div>
			</div>
		</div>
	</div>
	
	<div class="modal fade" id="unregisterPerms" tabindex="-1" role="dialog" aria-labelledby="unregiserPermsLabel" aria-hidden="true">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="unregiserPermsLabel">Chyba</h5>
				</div>
				<div class="modal-body">
					Nemáš dostatečná oprávnění pro unregister tohoto hráče!
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-danger" onclick="closeUnregisterPerms()">Zavřít</button>
				</div>
			</div>
		</div>
	</div>
	
	<div class="modal fade" id="unregisterError" tabindex="-1" role="dialog" aria-labelledby="unregiserErrorLabel" aria-hidden="true">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="unregiserErrorLabel">Chyba</h5>
				</div>
				<div class="modal-body">
					Nastala chyba při odesílání unregister požadavku!
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-danger" onclick="closeUnregisterError()">Zavřít</button>
				</div>
			</div>
		</div>
	</div>
	
	<script>
		function handleUnregister() {
			var nick = $('#unregisterNick').val();
			
			console.log("Calling unregister for nick: " + nick);
			
			$.ajax({url: "unreg.php", type: "post", data: "nick=" + nick, success: function(result){
				if (result == "ok") {
					$('#unregisterOk').modal('show');
				} else if (result == "perms") {
					$('#unregisterPerms').modal('show');
				} else {
					$('#unregisterError').modal('show');
				}
			}, error: function() {
				$('#unregisterError').modal('show');
			}});
			
			return false;
		}
		
		function closeUnregisterOk() {
			$('#unregisterOk').modal('hide');
		}
		
		function closeUnregisterPerms() {
			$('#unregisterPerms').modal('hide');
		}
		
		function closeUnregisterError() {
			$('#unregisterError').modal('hide');
		}
	</script>
	<?php
	}
	?>
	
	<?php
	if ($canUnregister) {
	?>
	<div class="modal fade" id="unbanOk" tabindex="-1" role="dialog" aria-labelledby="unbanOkLabel" aria-hidden="true">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="unregiserOkLabel">Úspěch</h5>
				</div>
				<div class="modal-body">
					Unban proběhl úspěšně!
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-danger" onclick="closeUnbanOk()">Zavřít</button>
				</div>
			</div>
		</div>
	</div>
	
	<div class="modal fade" id="unbanError" tabindex="-1" role="dialog" aria-labelledby="unbanErrorLabel" aria-hidden="true">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="unbanErrorLabel">Chyba</h5>
				</div>
				<div class="modal-body">
					Nastala chyba při odesílání unban požadavku!
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-danger" onclick="closeUnbanError()">Zavřít</button>
				</div>
			</div>
		</div>
	</div>
	
	<script>
		function handleUnban() {
			var nick = $('#unbanNick').val();
			
			console.log("Calling unban for nick: " + nick);
			
			$.ajax({url: "unban.php", type: "post", data: "nick=" + nick, success: function(result){
				if (result == "ok") {
					$('#unbanOk').modal('show');
				} else {
					$('#unbanError').modal('show');
				}
			}, error: function() {
				$('#unbanError').modal('show');
			}});
			
			return false;
		}
		
		function closeUnbanOk() {
			$('#unbanOk').modal('hide');
		}
		
		function closeUnbanError() {
			$('#unbanError').modal('hide');
		}
	</script>
	<?php
	}
	?>
	
	<?php
	if ($canMaintenance) {
	?>
	<div class="modal fade" id="maintenanceEnabled" tabindex="-1" role="dialog" aria-labelledby="maintenanceEnabledLabel" aria-hidden="true">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="maintenanceEnabledLabel">Úspěch</h5>
				</div>
				<div class="modal-body">
					Údržba byla aktivována!
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-danger" onclick="closeMaintenanceEnabled()">Zavřít</button>
				</div>
			</div>
		</div>
	</div>
	
	<div class="modal fade" id="maintenanceDisabled" tabindex="-1" role="dialog" aria-labelledby="maintenanceDisabledLabel" aria-hidden="true">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="maintenanceDisabledLabel">Úspěch</h5>
				</div>
				<div class="modal-body">
					Udržba byla deaktivována!
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-danger" onclick="closeMaintenanceDisabled()">Zavřít</button>
				</div>
			</div>
		</div>
	</div>
	
	<div class="modal fade" id="maintenanceError" tabindex="-1" role="dialog" aria-labelledby="maintenanceErrorLabel" aria-hidden="true">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="maintenanceErrorLabel">Chyba</h5>
				</div>
				<div class="modal-body">
					Nastala chyba při odesílání požadavku na změnu stavu údržby!
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-danger" onclick="closeMaintenanceError()">Zavřít</button>
				</div>
			</div>
		</div>
	</div>
	
	<script>
		function handleMaintenance() {
			console.log("Calling maintenance toggle!");
			
			$.ajax({url: "maintenance.php", success: function(result){
				if (result == "true") {
					$('#maintenanceEnabled').modal('show');
				} else if (result == "false") {
					$('#maintenanceDisabled').modal('show');
				} else {
					$('#maintenanceError').modal('show');
				}
			}, error: function() {
				$('#maintenanceError').modal('show');
			}});
			
			return false;
		}
		
		function closeMaintenanceEnabled() {
			$('#maintenanceEnabled').modal('hide');
		}
		
		function closeMaintenanceDisabled() {
			$('#maintenanceDisabled').modal('hide');
		}
		
		function closeMaintenanceError() {
			$('#maintenanceError').modal('hide');
		}
	</script>

	<?php
		}
	?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

<script type="text/javascript">
    $(document).ready(function () {
        $('#menu').on('click', function () {
            $('#sidebar').toggleClass('active');
        });
    });
</script>

</html>
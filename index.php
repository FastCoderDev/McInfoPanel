<?php
session_start();

if (isset($_SESSION["logged"]) && $_SESSION["logged"] != "") {
	header ("Location: panel.php");
	exit;
}
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MidCore.eu | Login </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BmbxuPwQa2lc/FVzBcNJ7UAyJxM6wuqIj61tLrc4wSX0szH/Ev+nYRRuWlolflfl" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/js/bootstrap.bundle.min.js" integrity="sha384-b5kHyXgcpbZJO/tY9Ul7kGkf1S0CWuKcCD38l8YkeH8z8QjE0GmW1gYU5S9FOnJ0" crossorigin="anonymous"></script> 
	<script data-ad-client="ca-pub-1836565616510693" async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>	
    <link rel="stylesheet" href="assets/css/dash.css"> 
</head>
<body>
    <main id="login">
        <div class="content">
            <header>
                <img src="https://static.wikia.nocookie.net/meme/images/1/16/AwesomeFace-1.png">
                <p class="info">
                    <span class="big">MidCore.cz</span>
                    <br>
                    <span>Přihlášení do InfoPanelu</span>
                </p>
				<?php
				    if (isset($_GET["invalid"])) {
				    	echo "<div class=\"alert alert-danger\" role=\"alert\">Neplatné údaje, zkus to znovu!</div>";
				    }
				?>
            </header>
            <div class="form">
                <form action="login.php" method="POST" class="form">
                    <div class="form-group">
                      <label class="form-label">Nickname</label>
                      <input name="nick" type="text" class="form-control">
                      <div class="form-text">Nick který máte ve hře.</div>
                    </div>
                    <div class="form-group">
                      <label class="form-label">Password</label>
                      <input name="password" type="password" class="form-control">
                      <div class="form-text">Heslo přes které se přihlašujete ve hře.</div>
                    </div>
                    <button type="submit" class="btn gradient">Přihlásit se</button>
                </form>
            </div>
        </div>
    </main>
</body>
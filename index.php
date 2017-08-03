<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport"
		  content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<title>iWatchLife API</title>

 	<link href="css/bootstrap/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
<div class="container">
	<div class="row">
		<img src="iwl.png" alt="iWatchLife, See what matters">
	</div>
	<div class="row col-sm-6 col-sm-push-3">
		<form action="API.php" method="post">
			<div class="form-group">
				<label for="username">Nom d'utilisateur</label>
				<input type="text" id="username" name="username" placeholder="Enter your user account name" <? if(isset($_POST['username'])) echo "value=".$_POST['username'] ?> class="form-control">
			</div>
			<div class="form-group">
				<label for="password">Mot de passe</label>
				<input type="password" id="password" name="password" placeholder="Enter your user account password" <? if(isset($_POST['password'])) echo "value=".$_POST['password'] ?> class="form-control">
			</div>
			<div class="form-group">
				<label for="camera_number">Nombre de caméra(s)</label>
				<input type="number" id="camera_number" name="camera_number" placeholder="Enter number of cameras available" <? if(isset($_POST['camera_number'])) echo "value=".$_POST['camera_number'] ?> class="form-control">
			</div>
			<div class="form-group">
				<label for="client_id">Client ID</label>
				<input type="text" id="client_id" name="client_id" value="APP_OTTAWAU" class="form-control">
			</div>
			<div class="form-group">
				<label for="client_secret">Client secret key</label>
				<input type="text" id="client_secret" name="client_secret" value="5e1e23b05fe8443f0f651cbae030a27e" class="form-control">
			</div>
			<div class="form-group">
				<label for="date">Jour</label>
				<input type="date" id="date" name="date" class="form-control">
			</div>
			<div class="form-group">
				<button class="btn btn-primary" type="submit">Go !</button>
			</div>
		</form>
	</div>

</div>

<script src="js/jquery-3.2.1.min.js"></script>
<script>
    $(document).ready(function() {
        var today = new Date();
        var dd = today.getDate();
        var mm = today.getMonth() + 1; //January is 0!
        var yyyy = today.getFullYear();
        if(dd<10){dd='0'+dd} if(mm<10){mm='0'+mm}
        $('#date').attr('value', yyyy + '-' + mm + '-' + dd);
    });
</script>
</body>
</html>
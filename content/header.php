<?php
?>

<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Eishockey to Dream</title>
		<link href="style.css" rel="stylesheet" type="text/css">
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" integrity="sha512-xh6O/CkQoPOWDdYTDqeRdPCVd1SpvCA9XXcUnZS2FmJNp1coAFzvtCN9BmamE+4aHK8yyUHUSCcJHgXloTyT2A==" crossorigin="anonymous" referrerpolicy="no-referrer">
	</head>
	<body class="loggedin">
		<div class="header">
			<div class="title">
				<h1><a href="home.php">Eishockey to Dream</a></h1>
			</div>
			<div class="menu">
				<nav class="navtop">
					<a href="statistics.php"><i class="fas fa-chart-simple"></i><?=$translator->__('Statistics',$language)?></a>
					<a href="profile.php"><i class="fas fa-user-circle"></i><?=$translator->__('Profile',$language)?></a>
					<a href="help.php"><i class="fas fa-circle-info"></i><?=$translator->__('Help',$language)?></a>
<?php if($_SESSION['admin'] > 0) {?>
					<a href="admin.php"><i class="fas fa-user-secret"></i>Admin</a>
<?php }?>
					<a href="logout.php"><i class="fas fa-sign-out-alt"></i><?=$translator->__('Logout',$language)?></a>
				</nav>
			</div>
		</div>
        <div class="content">
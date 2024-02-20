<?php
include_once 'config/config.php';
include 'content/session.php';

$con = mysqli_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);
if (mysqli_connect_errno()) {
	exit('Failed to connect to MySQL: ' . mysqli_connect_error());
}
// We don't have the password or email info stored in sessions, so instead, we can get the results from the database.
$stmt = $con->prepare('SELECT password, email FROM User WHERE id = ?');
// In this case we can use the account ID to get the account info.
$stmt->bind_param('i', $_SESSION['id']);
$stmt->execute();
$stmt->bind_result($password, $email);
$stmt->fetch();
$stmt->close();

include 'content/header.php';
?>
<h2>Profile Page</h2>
<div>
	<p>Your account details are below:</p>
	<table>
		<tr>
			<td>Username:</td>
			<td><?=$_SESSION['name']?></td>
		</tr>
		<tr>
			<td>Password:</td>
			<td><?=$password?></td>
		</tr>
		<tr>
			<td>Email:</td>
			<td><?=$email?></td>
		</tr>
<?php if($_SESSION['admin'] > 0) {?>
		<tr>
			<td>Admin:</td>
			<td><i class="fas fa-check-circle"></i></td>
		</tr>
<?php } ?>
	</table>
</div>
<?php
include 'content/footer.php';
?>

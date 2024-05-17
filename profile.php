<?php
include_once 'config/functions.php';
include 'content/session.php';

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
<h2><?=$translator->__('Profile Page',$language)?></h2>
<div>
	<p><?=$translator->__('Your account details are below',$language)?>:</p>
	<table>
		<tr>
			<td><?=$translator->__('Username',$language)?>:</td>
			<td><?=$_SESSION['name']?></td>
		</tr>
		<tr>
			<td>Email:</td>
			<td><?=$email?></td>
		</tr>
<?php if($_SESSION['admin'] > 0) {?>
		<tr>
			<td><?=$translator->__('Admin',$language)?>:</td>
			<td><i class="fas fa-check-circle"></i></td>
		</tr>
<?php } ?>
	</table>
</div>
<?php
include 'content/footer.php';
?>

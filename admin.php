<?php
include_once 'config/config.php';
include 'content/session.php';

$con = mysqli_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);
if (mysqli_connect_errno()) {
	exit('Failed to connect to MySQL: ' . mysqli_connect_error());
}

// form response

// change user team
if(isset($_POST["team_id"]) && isset($_POST["user_id"])) {
	$stmt = $con->prepare('UPDATE User SET team_id = ? WHERE id = ?');
	$stmt->bind_param('ii', $_POST['team_id'], $_POST['user_id']);
	$stmt->execute();
}

// get data from database

// get all users from database
$stmt = $con->prepare('SELECT * FROM User');
$stmt->execute();
$result = $stmt->get_result();
while($user = $result->fetch_array()) {
	$users[] = $user;
}
$stmt->close();

// get all teams from database
$stmt = $con->prepare('SELECT * FROM Team');
$stmt->execute();
$result = $stmt->get_result();
while($team = $result->fetch_array()) {
	$teams[$team['id']] = $team;
}
$stmt->close();

include 'content/header.php';
?>
<h2>Admin Page</h2>
<div>
	<p>Registered users:</p>
	<table>
		<tr>
			<th>Username</th>
			<th>Email</th>
			<th>Activation</th>
			<th>Team</th>
			<th>Dream Team</th>
			<th>Admin</th>
		</tr>
<?php foreach ($users as $user) { ?>
		<tr>
			<td><?=$user['username']?></td>
			<td><?=$user['email']?></td>
			<td><?=$user['activation_code']?></td>
			<td>
				<form method="POST" action="">
					<select name="team_id" onchange="this.form.submit()">
						<option value="" <?php if($user['team_id'] == 0) echo 'selected'; ?>></option>
						<?php foreach($teams as $team) {?>
							<option value="<?=$team['id']?>" <?php if($user['team_id'] == $team['id']) echo 'selected'; ?>><?=$team['name']?></option>
						<?php } ?>
					</select>
					<input type="hidden" name="user_id" value="<?=$user['id']?>"></input>
				</form>
			</td>
			<td>
				<?php foreach($teams as $team) {
					if($user['dream_team_id'] == $team['id'])
						echo $team['name'];
				} ?>
			</td>
			<td><?php if($user['admin'] == 1) {?><i class="fas fa-check-circle"></i><?php } ?></td>
		</tr>
<?php } ?>
	</table>
</div>
<?php
include 'content/footer.php';
?>
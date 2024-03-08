<?php
include_once 'config/functions.php';
include 'content/session.php';

// form response

// change user team
if(isset($_POST['team_id']) && isset($_POST['user_id'])) {
	$stmt = $con->prepare('UPDATE User SET team_id = ? WHERE id = ?');
	$stmt->bind_param('ii', $_POST['team_id'], $_POST['user_id']);
	$stmt->execute();
}
// reset game
if(isset($_POST['reset_game'])) {
	initialize_game($con, $MAX_GOALS_HOME, $MAX_GOALS_AWAY, $Max_GOALS_OVERTIME);
}
// next day
if(isset($_POST['next_day'])) {
	to_next_day($con);
}
//next week
if(isset($_POST['next_week'])) {
	to_next_day($con);
	to_next_day($con);
	to_next_day($con);
	to_next_day($con);
}
//compute league
if(isset($_POST['compute_league'])) {
	for($i = 0; $i < 11; ++$i) {
		to_next_day($con);
		to_next_day($con);
		to_next_day($con);
		to_next_day($con);
	}
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
<h2><?=$translator->__('Admin Page',$language)?></h2>
<div>
	<p><?=$translator->__('Functions',$language)?>:</p>
	<form method="POST" action="">
		<input type="submit" value="<?=$translator->__('Next day',$language)?>">
		<input type="hidden" name="next_day" value="1"></input>
	</form>
	<p></p>
	<form method="POST" action="">
		<input type="submit" value="<?=$translator->__('Next week',$language)?>">
		<input type="hidden" name="next_week" value="1"></input>
	</form>
	<p></p>
	<form method="POST" action="">
		<input type="submit" value="<?=$translator->__('Compute league',$language)?>">
		<input type="hidden" name="compute_league" value="1"></input>
	</form>
	<p></p>
	<form method="POST" action="">
		<input type="submit" value="<?=$translator->__('Reset game',$language)?>">
		<input type="hidden" name="reset_game" value="1"></input>
	</form>
</div>
<div>
	<p><?=$translator->__('Registered users',$language)?>:</p>
	<table>
		<tr>
			<th><?=$translator->__('Username',$language)?></th>
			<th>Email</th>
			<th><?=$translator->__('Activation',$language)?></th>
			<th>Team</th>
			<th><?=$translator->__('Dream Team',$language)?></th>
			<th><?=$translator->__('Admin',$language)?></th>
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
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
//next season
if(isset($_POST['next_season'])) {
	to_next_season($con, $MAX_GOALS_HOME, $MAX_GOALS_AWAY, $Max_GOALS_OVERTIME);
}
//bonus_goals
if(isset($_POST['bonus_goals'])) {
	$win_leader = isset($_POST['win_leader'])?1:0;
	$win_five_times = isset($_POST['win_five_times'])?1:0;
	$win_five_goals = isset($_POST['win_five_goals'])?1:0;
	$stmt = $con->prepare('UPDATE State SET win_leader = ?, win_five_times = ?, win_five_goals = ? WHERE id = 1');
	$stmt->bind_param('iii', $win_leader, $win_five_times, $win_five_goals);
	$stmt->execute();
}
//message
if(isset($_POST['message'])) {
	$stmt = $con->prepare('UPDATE State SET message = ? WHERE id = 1');
	$stmt->bind_param('s', $_POST['textfield']);
	$stmt->execute();
}
//email
if(isset($_POST['email'])) {
	$stmt = $con->prepare('UPDATE State SET admin_mail = ? WHERE id = 1');
	$stmt->bind_param('s', $_POST['email']);
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

// get state from database
$stmt = $con->prepare('SELECT * FROM State');
$stmt->execute();
$result = $stmt->get_result();
$state = $result->fetch_array();
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
	<p></p>
	<form method="POST" action="">
		<input type="submit" <?php if($state['season_over'] == 0) echo 'disabled' ?> value="<?=$translator->__('Next season',$language)?>">
		<input type="hidden" name="next_season" value="1"></input>
	</form>
</div>
<div>
	<p><?=$translator->__('Message of the day',$language)?>:</p>
	<form method="POST" action="">
		<textarea id="textfield" name="textfield" rows="5" cols="40"><?php echo $state['message'];?></textarea></br>
		<input type="submit" value="<?=$translator->__('Save',$language)?>"/>
		<input type="hidden" name="message" value="1"></input>
	</form>
</div>
<div>
	<p><?=$translator->__('Admin mail',$language)?>:</p>
	<form method="POST" action="">
		<input type="text" name="email" value="<?=$state['admin_mail']?>" onchange="this.form.submit()"></input>
	</form>
</div>
<div>
	<p><?=$translator->__('Bonus goals', $language)?>:</p>
	<form method="POST" action="">
		<div>
			<input type="checkbox" name="win_leader" id="win_leader" <?php if($state['win_leader']) echo 'checked'; ?> onchange="this.form.submit()" />
			<label for="win_leader">Sieg gegen Tabellenführer (+1 Heim)</label>
		</div>
		<div>
			<input type="checkbox" name="win_five_times" id="win_five_times" <?php if($state['win_five_times']) echo 'checked'; ?> onchange="this.form.submit()" />
			<label for="win_five_times">5 Siege in Folge (+2 Auswärts)</label>
		</div>
		<div>
			<input type="checkbox" name="win_five_goals" id="win_five_goals" <?php if($state['win_five_goals']) echo 'checked'; ?> onchange="this.form.submit()" />
			<label for="win_five_goals">Sie mit 5 Toren Unterschied (+1 Auswärts)</label>
		</div>
		<input type="hidden" name="bonus_goals" value="1"></input>
	</form>
</div>
<div>
	<p><?=$translator->__('Registered users',$language)?>:</p>
	<table>
		<tr>
			<th><?=$translator->__('Username',$language)?></th>
			<th><?=$translator->__('Email',$language)?></th>
			<th><?=$translator->__('Activation',$language)?></th>
			<th><?=$translator->__('Team',$language)?></th>
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
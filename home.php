<?php
include_once 'config/functions.php';
include 'content/session.php';

// if user has no team yet -> team selection
$stmt = $con->prepare('SELECT * FROM User WHERE id = ?');
$stmt->bind_param('i', $_SESSION['id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_array();
$stmt->close();
if($user['dream_team_id'] == 0) {
	header('Location: teamselect.php');
	exit;
}
include 'content/header.php';

// get game state
$state = get_game_day($con);

// form response

// save game data
if(isset($_POST['game_id'])) {
	$user_team = get_team_by_id($con, $user['team_id']);
	$game_day = get_game_by_id($con, $_POST['game_id']);
	$gah = array($user_team['goal_account_home_1'], $user_team['goal_account_home_2'], $user_team['goal_account_home_3']);
	$gaa = array($user_team['goal_account_away_1'], $user_team['goal_account_away_2'], $user_team['goal_account_away_3']);
	$gao = $user_team['goal_account_overtime'];

	$statement = 'UPDATE Game SET';
	$periods = array('1', '2', '3');
	foreach($periods as $period) {
		if(isset($_POST['home_team_goal_'.$period])) {
			$statement.= ' home_team_goal_'.$period.' = '.$_POST['home_team_goal_'.$period].',';
			$diff = $_POST['home_team_goal_'.$period] - $game_day['home_team_goal_'.$period];
			$gah[((int)$period) - 1] = $gah[((int)$period) - 1] - $diff;
		}
		if(isset($_POST['away_team_goal_'.$period])) {
			$statement.= ' away_team_goal_'.$period.' = '.$_POST['away_team_goal_'.$period].',';
			$diff = $_POST['away_team_goal_'.$period] - $game_day['away_team_goal_'.$period];
			$gaa[((int)$period) - 1] = $gaa[((int)$period) - 1] - $diff;
		}
	}
	if(isset($_POST['home_team_goal_overtime'])) {
		$statement.= ' home_team_goal_overtime = '.$_POST['home_team_goal_overtime'].',';
		$diff = $_POST['home_team_goal_overtime'] - $game_day['home_team_goal_overtime'];
		$gao = $gao - $diff;
	}
	if(isset($_POST['away_team_goal_overtime'])) {
		$statement.= ' away_team_goal_overtime = '.$_POST['away_team_goal_overtime'].',';
		$diff = $_POST['away_team_goal_overtime'] - $game_day['away_team_goal_overtime'];
		$gao = $gao - $diff;
	}

	if($gah[0] > 0 && $gah[1] > 0 && $gah[2] > 0 &&
	   $gaa[0] > 0 && $gaa[1] > 0 && $gaa[2] > 0 &&
	   $gao > 0)
	{
		$statement = rtrim($statement, ",");
		$statement.= ' WHERE id = ?';
		$stmt = $con->prepare($statement);
		$stmt->bind_param('i', $_POST['game_id']);
		$stmt->execute();

		$stmt = $con->prepare('UPDATE Team SET goal_account_home_1 = ?, goal_account_home_2 = ?, goal_account_home_3 = ?, goal_account_away_1 = ?, goal_account_away_2 = ?, goal_account_away_3 = ?, goal_account_overtime = ? ');
		$stmt->bind_param('iiiiiii', $gah[0], $gah[1], $gah[2], $gaa[0], $gaa[1], $gaa[2], $gao);
		$stmt->execute();
	}
	else
	{
		echo "Invalid input.";
	}
}
?>
<h2>Home Page</h2>
<p>Welcome back, <?=$_SESSION['name']?>
<?php // user has set dream team but has no team yet
if($user['team_id'] == 0) { ?>
!</p>
You are waiting for the approval for your team. Please come back later.
<?php } else { 
// user has a team
$user_team = get_team_by_id($con, $user['team_id']);
// show league table
?>
, coach of <?=$user_team['name']?></p>
<div>
	<p>Goal stats:</p>
	<table>
		<tr>
			<td>Goals home:</td>
			<td><?=$user_team['goal_account_home_1']+$user_team['goal_account_home_2']+$user_team['goal_account_home_3']?> (<?=$user_team['goal_account_home_1']?>, <?=$user_team['goal_account_home_2']?>, <?=$user_team['goal_account_home_3']?>)</td>
		</tr>
		<tr>
			<td>Goals away:</td>
			<td><?=$user_team['goal_account_away_1']+$user_team['goal_account_away_2']+$user_team['goal_account_away_3']?> (<?=$user_team['goal_account_away_1']?>, <?=$user_team['goal_account_away_2']?>, <?=$user_team['goal_account_away_3']?>)</td>
		</tr>
		<tr>
			<td>Goals goal_account_overtime:</td>
			<td><?=$user_team['goal_account_overtime']?></td>
		</tr>
	</table>
</div>
<?php
// get the leage of this use
$stmt = $con->prepare('SELECT l.* FROM League l JOIN Team t ON t.league_id = l.id WHERE t.id = ?');
$stmt->bind_param('i', $user['team_id']);
$stmt->execute();
$result = $stmt->get_result();
$user_league = $result->fetch_array();
$stmt->close();
// get next matches
$games_per_week = $user_league['name'] == 'NHL' ? 5 : 4;
$last_game_day = $user_league['last_game_day'] + $games_per_week;
$first_game_day = $last_game_day - $games_per_week;
$stmt = $con->prepare('SELECT * FROM Game WHERE game_day <= ? AND game_day > ? AND (home_team_id = ? OR away_team_id = ?)');
$stmt->bind_param('iiii', $last_game_day, $first_game_day, $user['team_id'], $user['team_id']);
$stmt->execute();
$result = $stmt->get_result();
$games = array();
while($game = $result->fetch_array())
{
	$games[] = $game;
}
$stmt->close();
?>
<div>
	<p>Upcomming matches:</p>
<?php
foreach($games as $game)
{
	$home_team = get_team_by_id($con, $game['home_team_id']);
	$away_team = get_team_by_id($con, $game['away_team_id']);
	?>
	<div class='game'>
		<form method="POST" action="">
		<table>
			<tr>
				<td></td>
				<td><?=$home_team['name']?></td>
				<td><?=$away_team['name']?></td>
			</tr>
			<tr>
				<td>1. Period</td>
				<td><?php if($game['home_team_id'] == $user['team_id']) { ?>
						<input name='home_team_goal_1' value='<?=$game['home_team_goal_1']?>'></input>
					<?php } else {
							if($state['day'] < 1) {?>
								?
					<?php } else { 
								echo $game['home_team_goal_1'];
						  } }?></td>
				<td><?php if($game['away_team_id'] == $user['team_id']) { ?>
						<input name='away_team_goal_1' value='<?=$game['away_team_goal_1']?>'></input>
					<?php } else {
							if($state['day'] < 1) {?>
								?
					<?php } else { 
							echo $game['away_team_goal_1'];
					  	  } }?></td>
			</tr>
			<tr>
				<td>2. Period</td>
				<td><?php if($game['home_team_id'] == $user['team_id']) { ?>
						<input name='home_team_goal_2' value='<?=$game['home_team_goal_2']?>'></input>
					<?php } else {
							if($state['day'] < 2) {?>
								?
					<?php } else { 
								echo $game['home_team_goal_2'];
						  } }?></td>
				<td><?php if($game['away_team_id'] == $user['team_id']) { ?>
						<input name='away_team_goal_2' value='<?=$game['away_team_goal_2']?>'></input>
					<?php } else {
							if($state['day'] < 2) {?>
								?
					<?php } else { 
							echo $game['away_team_goal_2'];
					  	  } }?></td>
			</tr>
			<tr>
				<td>3. Period</td>
				<td><?php if($game['home_team_id'] == $user['team_id']) { ?>
						<input name='home_team_goal_3' value='<?=$game['home_team_goal_3']?>'></input>
					<?php } else {
							if($state['day'] < 3) {?>
								?
					<?php } else { 
								echo $game['home_team_goal_3'];
						  } }?></td>
				<td><?php if($game['away_team_id'] == $user['team_id']) { ?>
						<input name='away_team_goal_3' value='<?=$game['away_team_goal_3']?>'></input>
					<?php } else {
							if($state['day'] < 3) {?>
								?
					<?php } else { 
							echo $game['away_team_goal_3'];
					  	  } }?></td>
			</tr>
			<tr>
				<td>Overtime</td>
				<td><?php if($game['home_team_id'] == $user['team_id']) { ?>
						<input name='home_team_goal_overtime' value='<?=$game['home_team_goal_overtime']?>'></input>
					<?php } else {
							if($state['day'] < 3) {?>
								?
					<?php } else { 
								echo $game['home_team_goal_overtime'];
						  } }?></td>
				<td><?php if($game['away_team_id'] == $user['team_id']) { ?>
						<input name='away_team_goal_overtime' value='<?=$game['away_team_goal_overtime']?>'></input>
					<?php } else {
							if($state['day'] < 3) {?>
								?
					<?php } else { 
							echo $game['away_team_goal_overtime'];
					  	  } }?></td>
			</tr>
		</table>
		<input type='submit' value='Save'></input>
		<input type='hidden' name='game_id' value='<?=$game['id']?>'></input>
		</form>
	</div>
<?php }
?>
</div>
<div>
	<p>League table:</p>
	<table>
		<tr>
			<th>#</th>
			<th>Name</th>
			<th>Win</th>
			<th>Lose</th>
			<th>Goals</th>
			<th>Points</th>
		</tr>
<?php
$stmt = $con->prepare('select * from Team where league_id like (select l.id from League l, Team t where l.id = t.league_id and t.id = ?) order by points desc;');
$stmt->bind_param('i', $user['team_id']);
$stmt->execute();
$result = $stmt->get_result();
while($team = $result->fetch_array())
{
	$teams[] = $team;
}
$stmt->close();
$index = 0;
foreach($teams as $team) {
	?>
	<tr>
		<td><?=++$index?></td>
		<td><?=$team['name']?></td>
		<td><?=$team['win']?></td>
		<td><?=$team['lose']?></td>
		<td><?=$team['goals_shot'].":".$team['goals_received']?></td>
		<td><?=$team['points']?></td>
	</tr>
<?php
}
?>
</table>
<?php
}
include 'content/footer.php';
?>
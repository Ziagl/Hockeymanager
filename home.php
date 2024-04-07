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
// playoff or playdown
$playdown = get_play_down($con, $user['team_id']);
$playoff = get_play_off($con, $user['team_id']);

// form response

// save game data
if(isset($_POST['game_id'])) {
	$user_team = get_team_by_id($con, $user['team_id']);
	$gah = array($user_team['goal_account_home_1'], $user_team['goal_account_home_2'], $user_team['goal_account_home_3']);
	$gaa = array($user_team['goal_account_away_1'], $user_team['goal_account_away_2'], $user_team['goal_account_away_3']);
	$gao = $user_team['goal_account_overtime'];

	if($playdown != null) {
		$statement = 'UPDATE PlaydownGame SET';
		$game_day = get_playdown_game_by_id($con, $_POST['game_id']);
	} else if ($playoff != null) {
		$statement =  'UPDATE PlayoffGame SET';
		$game_day = get_playoff_game_by_id($con, $_POST['game_id']);
	} else {
		$statement = 'UPDATE Game SET';
		$game_day = get_game_by_id($con, $_POST['game_id']);
	}
	$periods = array('1', '2', '3');
	$error = false;
	foreach($periods as $period) {
		if(isset($_POST['home_team_goal_'.$period])) {
			$statement.= ' home_team_goal_'.$period.' = '.$_POST['home_team_goal_'.$period].',';
			$diff = $_POST['home_team_goal_'.$period] - $game_day['home_team_goal_'.$period];
			$gah[((int)$period) - 1] = $gah[((int)$period) - 1] - $diff;
			if($user_team['goal_account_home_'.$period] - $diff < 0) $error = true;
		}
		if(isset($_POST['away_team_goal_'.$period])) {
			$statement.= ' away_team_goal_'.$period.' = '.$_POST['away_team_goal_'.$period].',';
			$diff = $_POST['away_team_goal_'.$period] - $game_day['away_team_goal_'.$period];
			$gaa[((int)$period) - 1] = $gaa[((int)$period) - 1] - $diff;
			if($user_team['goal_account_away_'.$period] - $diff < 0) $error = true;
		}
	}
	if(isset($_POST['home_team_goal_overtime'])) {
		$statement.= ' home_team_goal_overtime = '.$_POST['home_team_goal_overtime'].',';
		$diff = $_POST['home_team_goal_overtime'] - $game_day['home_team_goal_overtime'];
		$gao = $gao - $diff;
		if($user_team['goal_account_overtime'] - $diff < 0) $error = true;
	}
	if(isset($_POST['away_team_goal_overtime'])) {
		$statement.= ' away_team_goal_overtime = '.$_POST['away_team_goal_overtime'].',';
		$diff = $_POST['away_team_goal_overtime'] - $game_day['away_team_goal_overtime'];
		$gao = $gao - $diff;
		if($user_team['goal_account_overtime'] - $diff < 0) $error = true;
	}

	if($error){
		echo 'Invalid input.';
	} else {
		$statement = rtrim($statement, ",");
		$statement.= ' WHERE id = ?';
		$stmt = $con->prepare($statement);
		$stmt->bind_param('i', $_POST['game_id']);
		$stmt->execute();

		$stmt = $con->prepare('UPDATE Team SET goal_account_home_1 = ?, goal_account_home_2 = ?, goal_account_home_3 = ?, goal_account_away_1 = ?, goal_account_away_2 = ?, goal_account_away_3 = ?, goal_account_overtime = ? WHERE id = ?');
		$stmt->bind_param('iiiiiiii', $gah[0], $gah[1], $gah[2], $gaa[0], $gaa[1], $gaa[2], $gao, $user['team_id']);
		$stmt->execute();
	}
}
?>
<h2><?=$translator->__('Dashboard',$language)?></h2>
<p><?=$translator->__('Welcome back',$language)?>, <?=$_SESSION['name']?>
<?php // user has set dream team but has no team yet
if($user['team_id'] == 0) { ?>
!</p>
<?=$translator->__('You are waiting for the approval for your team. Please come back later.',$language)?>
<?php } else { 
// user has a team
$user_team = get_team_by_id($con, $user['team_id']);
// show goal stats
?>
, <?=$translator->__('coach of',$language)?> <?=$user_team['name']?></p>
<div>
	<p><?=$translator->__('Goal stats',$language)?>:</p>
	<table>
		<tr>
			<td><?=$translator->__('Goals home',$language)?>:</td>
			<td><?=$user_team['goal_account_home_1']+$user_team['goal_account_home_2']+$user_team['goal_account_home_3']?> (<?=$user_team['goal_account_home_1']?>, <?=$user_team['goal_account_home_2']?>, <?=$user_team['goal_account_home_3']?>)</td>
		</tr>
		<tr>
			<td><?=$translator->__('Goals away',$language)?>:</td>
			<td><?=$user_team['goal_account_away_1']+$user_team['goal_account_away_2']+$user_team['goal_account_away_3']?> (<?=$user_team['goal_account_away_1']?>, <?=$user_team['goal_account_away_2']?>, <?=$user_team['goal_account_away_3']?>)</td>
		</tr>
		<tr>
			<td><?=$translator->__('Goals overtime',$language)?>:</td>
			<td><?=$user_team['goal_account_overtime']?></td>
		</tr>
	</table>
</div>
<?php
// get the leage of this use
$user_league = get_league_by_id($con, $user['team_id']);

// get next matches
if($playoff != null) {
	$playoff_team = get_playoff_team_by_id($con, $user['team_id']);
	$game_day = $playoff['last_game_day'] + 1;
	$round = $playoff['last_round'] + 1;
	$stmt = $con->prepare('SELECT * FROM PlayoffGame WHERE game_day = ? AND round = ? AND (home_team_id = ? OR away_team_id = ?)');
	$stmt->bind_param('iiii', $game_day, $round, $user['team_id'], $user['team_id']);
}
else if($playdown != null) {
	$last_game_day = $playdown['last_game_day'] + 1;
	$stmt = $con->prepare('SELECT * FROM PlaydownGame WHERE game_day = ? AND (home_team_id = ? OR away_team_id = ?)');
	$stmt->bind_param('iii', $last_game_day, $user['team_id'], $user['team_id']);
}
else {
	$games_per_week = $user_league['name'] == 'NHL' ? 5 : 4;
	$last_game_day = $user_league['last_game_day'] + $games_per_week;
	$first_game_day = $last_game_day - $games_per_week;
	$stmt = $con->prepare('SELECT * FROM Game WHERE game_day <= ? AND game_day > ? AND (home_team_id = ? OR away_team_id = ?)');
	$stmt->bind_param('iiii', $last_game_day, $first_game_day, $user['team_id'], $user['team_id']);
}
$stmt->execute();
$result = $stmt->get_result();
$games = array();
while($game = $result->fetch_array())
{
	$games[] = $game;
}
$stmt->close();

// don't display remaining games if already won 4 games
if($playoff != null) {
	if($playoff_team != null && $playoff_team['win'] >= 4) {
		$games = array();
	}
}
?>
<nav>
	<div class="tab">
		<button class="tablinks" onclick="openTab(event, 'Games')"><?=$translator->__('Upcoming matches',$language)?></button>
		<button class="tablinks" onclick="openTab(event, 'Table')"><?=$translator->__('Table',$language)?></button>
		<button class="tablinks" onclick="openTab(event, 'Plan')"><?=$translator->__('League schedule',$language)?></button>
	</div>
</nav>
<div class="container">
    <div id="Games" class="tabcontent">
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
				<td><div class="image-text-wrapper"><img src='images/<?=$home_team['id']?>.png' class='team-logo'/><p><?=$home_team['name']?></p></div></td>
				<td><div class="image-text-wrapper"><img src='images/<?=$away_team['id']?>.png' class='team-logo'/><p><?=$away_team['name']?></p></div></td>
			</tr>
			<tr>
				<td>1. <?=$translator->__('Period',$language)?></td>
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
				<td>2. <?=$translator->__('Period',$language)?></td>
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
				<td>3. <?=$translator->__('Period',$language)?></td>
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
				<td><?=$translator->__('Overtime',$language)?></td>
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
		<?php if($state['day'] == 0) { ?>
		<input type='submit' value='<?=$translator->__('Save',$language)?>'></input>
		<input type='hidden' name='game_id' value='<?=$game['id']?>'></input>
		<?php } ?>
		</form>
	</div>
<?php }
?>
</div>
<?php if($playdown != null) {
?>
<div id="Table" class="tabcontent">
	<table>
		<tr>
			<th>#</th>
			<th><?=$translator->__('Name',$language)?></th>
			<th><?=$translator->__('Win',$language)?></th>
			<th><?=$translator->__('Lose',$language)?></th>
			<th><?=$translator->__('Goals',$language)?></th>
			<th><?=$translator->__('Points',$language)?></th>
		</tr>
<?php
$teams = get_team_by_points($con, $user['team_id'], 1);
$index = 0;
foreach($teams as $team) {
	?>
		<tr>
			<td><?=++$index?></td>
			<td><img src='images/'.$team['id'].'.png'/><?=$team['name']?></td>
			<td><?=$team['win']?></td>
			<td><?=$team['lose']?></td>
			<td><?=$team['goals_shot'].":".$team['goals_received']?></td>
			<td><?=$team['points']?></td>
		</tr>
<?php
}
?>
	</table>
</div>
<?php } else if ($playoff != null) { ?>
<div id="Table" class="tabcontent">
	<table>
		<tr>
			<th>#</th>
			<th><?=$translator->__('Name',$language)?></th>
			<th>1</th>
			<th>2</th>
			<th>3</th>
			<th>4</th>
			<th>5</th>
			<th>6</th>
			<th>7</th>
		</tr>
<?php
$games = playoff_games_by_league($con, $playoff);
$index = 0;
for($i = 0; $i < count($games); $i += 7) {
	$team1_wins = 0;
	$team2_wins = 0;
	?>
		<tr>
			<td><?=++$index?></td>
			<td><?=$games[$i]['team1']?></td>
			<td><?php if($playoff['last_game_day'] >= $games[$i]['game_day']) { if ($games[$i]['home_win'] > 0) {echo 'X'; $team1_wins++;} else $team2_wins++; }?></td>
			<td><?php if($playoff['last_game_day'] >= $games[$i + 1]['game_day']) { if ($games[$i + 1]['home_win'] == 0) {echo 'X'; $team1_wins++;} else $team2_wins++; }?></td>
			<td><?php if($playoff['last_game_day'] >= $games[$i + 2]['game_day']) { if ($games[$i + 2]['home_win'] > 0) {echo 'X'; $team1_wins++;} else $team2_wins++; }?></td>
			<td><?php if($playoff['last_game_day'] >= $games[$i + 3]['game_day']) { if ($games[$i + 3]['home_win'] == 0) {echo 'X'; $team1_wins++;} else $team2_wins++; }?></td>
			<td><?php if($playoff['last_game_day'] >= $games[$i + 4]['game_day']) { if ($games[$i + 4]['home_win'] > 0 && $team1_wins < 4 && $team2_wins < 4) {echo 'X'; $team1_wins++;} else $team2_wins++; }?></td>
			<td><?php if($playoff['last_game_day'] >= $games[$i + 5]['game_day']) { if ($games[$i + 5]['home_win'] == 0 && $team1_wins < 4 && $team2_wins < 4) {echo 'X'; $team1_wins++;} else $team2_wins++; }?></td>
			<td><?php if($playoff['last_game_day'] >= $games[$i + 6]['game_day']) { if ($games[$i + 6]['home_win'] > 0 && $team1_wins < 4 && $team2_wins < 4) {echo 'X'; $team1_wins++;} else $team2_wins++; }?></td>
		</tr>
<?php
	$team1_wins = 0;
	$team2_wins = 0;
	?>
		<tr>
			<td></td>
			<td><?=$games[$i]['team2']?></td>
			<td><?php if($playoff['last_game_day'] >= $games[$i]['game_day']) { if ($games[$i]['home_win'] == 0) {echo 'X'; $team2_wins++;} else $team1_wins++; }?></td>
			<td><?php if($playoff['last_game_day'] >= $games[$i + 1]['game_day']) { if ($games[$i + 1]['home_win'] > 0) {echo 'X'; $team2_wins++;} else $team1_wins++; }?></td>
			<td><?php if($playoff['last_game_day'] >= $games[$i + 2]['game_day']) { if ($games[$i + 2]['home_win'] == 0) {echo 'X'; $team2_wins++;} else $team1_wins++; }?></td>
			<td><?php if($playoff['last_game_day'] >= $games[$i + 3]['game_day']) { if ($games[$i + 3]['home_win'] > 0) {echo 'X'; $team2_wins++;} else $team1_wins++; }?></td>
			<td><?php if($playoff['last_game_day'] >= $games[$i + 4]['game_day']) { if ($games[$i + 4]['home_win'] == 0 && $team1_wins < 4 && $team2_wins < 4) {echo 'X'; $team2_wins++;} else $team1_wins++; }?></td>
			<td><?php if($playoff['last_game_day'] >= $games[$i + 5]['game_day']) { if ($games[$i + 5]['home_win'] > 0 && $team1_wins < 4 && $team2_wins < 4) {echo 'X'; $team2_wins++;} else $team1_wins++; }?></td>
			<td><?php if($playoff['last_game_day'] >= $games[$i + 6]['game_day']) { if ($games[$i + 6]['home_win'] == 0 && $team1_wins < 4 && $team2_wins < 4) {echo 'X'; $team2_wins++;} else $team1_wins++; }?></td>
		</tr>
<?php
}
?>
	</table>
</div>
<?php } ?>
<div id="Table" class="tabcontent">
	<table>
		<tr>
			<th>#</th>
			<th><?=$translator->__('Name',$language)?></th>
			<th><?=$translator->__('Win',$language)?></th>
			<th><?=$translator->__('Lose',$language)?></th>
			<th><?=$translator->__('Goals',$language)?></th>
			<th><?=$translator->__('Points',$language)?></th>
		</tr>
<?php
$teams = get_team_by_points($con, $user['team_id'], 0);
$index = 0;
foreach($teams as $team) {
	$image = "images/".$team['id'].".png";
	?>
		<tr>
			<td><?=++$index?></td>
			<td><div class="image-text-wrapper"><img src='<?=$image?>' class='team-logo'/><p><?=$team['name']?></p></div></td>
			<td><?=$team['win']?></td>
			<td><?=$team['lose']?></td>
			<td><?=$team['goals_shot'].":".$team['goals_received']?></td>
			<td><?=$team['points']?></td>
		</tr>
<?php
}
?>
	</table>
</div>
<?php if($playdown != null) { ?>
<div>
	<p><?=$translator->__('Playdown schedule',$language)?>:</p>
	<?php 
		$games = get_games_by_playdown($con, $playdown);
		foreach($games as $game_day) { ?>
	<br>
	<p><?=$translator->__('Game day',$language)?> <?=$game_day[0]['game_day']?></p>
	<table>
		<tr>
			<th>#</th>
			<th><?=$translator->__('Team',$language)?></th>
			<th><?=$translator->__('Team',$language)?></th>
			<th><?=$translator->__('Result',$language)?></th>
			<th><?=$translator->__('P1',$language)?></th>
			<th><?=$translator->__('P2',$language)?></th>
			<th><?=$translator->__('P3',$language)?></th>
			<th><?=$translator->__('Ot',$language)?></th>
		</tr>
	<?php
		$index = 0;
		foreach($game_day as $game) {
			$index++;
			$home_goals = $game['home_team_goal_1'] + $game['home_team_goal_2'] + $game['home_team_goal_3'];
			$away_goals = $game['away_team_goal_1'] + $game['away_team_goal_2'] + $game['away_team_goal_3'];
	?>
		<tr>
			<td><?=$index?></td>
			<td><?=$game['home']?></td>
			<td><?=$game['away']?></td>
			<td><?php if($game['game_day'] <= $game['last_game_day']) echo ($game['home_team_goal_1'] + $game['home_team_goal_2'] + $game['home_team_goal_3'] + $game['home_team_goal_overtime']) . " : " .  ($game['away_team_goal_1'] + $game['away_team_goal_2'] + $game['away_team_goal_3'] + $game['away_team_goal_overtime']);?></td>
			<td><?php if($game['game_day'] <= $game['last_game_day']) echo $game['home_team_goal_1'] . " : " . $game['away_team_goal_1'];?></td>
			<td><?php if($game['game_day'] <= $game['last_game_day']) echo $game['home_team_goal_2'] . " : " . $game['away_team_goal_2'];?></td>
			<td><?php if($game['game_day'] <= $game['last_game_day']) echo $game['home_team_goal_3'] . " : " . $game['away_team_goal_3'];?></td>
			<td><?php if($game['game_day'] <= $game['last_game_day'] && ($game['home_team_goal_1'] + $game['home_team_goal_2'] + $game['home_team_goal_3']) == ($game['away_team_goal_1'] + $game['away_team_goal_2'] + $game['away_team_goal_3'])) echo $game['home_team_goal_overtime'] . " : " . $game['away_team_goal_overtime'];?></td>		
		</tr>
	<?php } ?>
	</table>
	<?php } ?>
</div>
<?php } ?>
<div id="Plan" class="tabcontent">
	<?php
		$games = get_games_by_league($con, $user_league);
		foreach($games as $game_day) {
	?>
	<br>
	<p><?=$translator->__('Game day',$language)?> <?=$game_day[0]['game_day']?></p>
	<table>
		<tr>
			<th>#</th>
			<th><?=$translator->__('Team',$language)?></th>
			<th><?=$translator->__('Team',$language)?></th>
			<th><?=$translator->__('Result',$language)?></th>
			<th><?=$translator->__('P1',$language)?></th>
			<th><?=$translator->__('P2',$language)?></th>
			<th><?=$translator->__('P3',$language)?></th>
			<th><?=$translator->__('Ot',$language)?></th>
		</tr>
	<?php
		$index = 0;
		foreach($game_day as $game) {
			$index++;
			$home_goals = $game['home_team_goal_1'] + $game['home_team_goal_2'] + $game['home_team_goal_3'];
			$away_goals = $game['away_team_goal_1'] + $game['away_team_goal_2'] + $game['away_team_goal_3'];
	?>
		<tr>
			<td><?=$index?></td>
			<td><?=$game['home']?></td>
			<td><?=$game['away']?></td>
			<td><?php if($game['game_day'] <= $game['last_game_day']) echo ($game['home_team_goal_1'] + $game['home_team_goal_2'] + $game['home_team_goal_3'] + $game['home_team_goal_overtime']) . " : " .  ($game['away_team_goal_1'] + $game['away_team_goal_2'] + $game['away_team_goal_3'] + $game['away_team_goal_overtime']);?></td>
			<td><?php if($game['game_day'] <= $game['last_game_day']) echo $game['home_team_goal_1'] . " : " . $game['away_team_goal_1'];?></td>
			<td><?php if($game['game_day'] <= $game['last_game_day']) echo $game['home_team_goal_2'] . " : " . $game['away_team_goal_2'];?></td>
			<td><?php if($game['game_day'] <= $game['last_game_day']) echo $game['home_team_goal_3'] . " : " . $game['away_team_goal_3'];?></td>
			<td><?php if($game['game_day'] <= $game['last_game_day'] && ($game['home_team_goal_1'] + $game['home_team_goal_2'] + $game['home_team_goal_3']) == ($game['away_team_goal_1'] + $game['away_team_goal_2'] + $game['away_team_goal_3'])) echo $game['home_team_goal_overtime'] . " : " . $game['away_team_goal_overtime'];?></td>		
		</tr>
	<?php } ?>
	</table>
	<?php } ?>
</div>
<?php
}
include 'content/footer.php';
?>
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
?>
<div class="text-container">
<h2><?=$translator->__('Dashboard',$language)?></h2>
<p><?=$translator->__('Welcome back',$language)?>, <?=$_SESSION['name']?>
<?php // user has set dream team but has no team yet
if($user['team_id'] == 0) { ?>
!</p></div>
<?=$translator->__('You are waiting for the approval for your team. Please come back later.',$language)?>
<?php } else { 
// user has a team
$user_team = get_team_by_id($con, $user['team_id']);
// show goal stats
?>
, <?=$translator->__('coach of',$language)?> <?=$user_team['name']?></p>
<div class="image-container">
<img src='<?="images/".$user_team['id'].".png"?>' class='team-logo-big'/>
</div></div>
<div>
	<p><?=$translator->__('Goal stats',$language)?>:</p>
	<table>
		<tr>
			<td><?=$translator->__('Goals home',$language)?>:</td>
			<td><div id="goal_home"><?=$user_team['goal_account_home_1']+$user_team['goal_account_home_2']+$user_team['goal_account_home_3']?> (<?=$user_team['goal_account_home_1']?>, <?=$user_team['goal_account_home_2']?>, <?=$user_team['goal_account_home_3']?>)</div></td>
		</tr>
		<tr>
			<td><?=$translator->__('Goals away',$language)?>:</td>
			<td><div id="goal_away"><?=$user_team['goal_account_away_1']+$user_team['goal_account_away_2']+$user_team['goal_account_away_3']?> (<?=$user_team['goal_account_away_1']?>, <?=$user_team['goal_account_away_2']?>, <?=$user_team['goal_account_away_3']?>)</div></td>
		</tr>
		<tr>
			<td><?=$translator->__('Goals overtime',$language)?>:</td>
			<td><div id="goal_overtime"><?=$user_team['goal_account_overtime']?></div></td>
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
	<div class='tab'>
		<button class='tablinks' onclick='openTab(event, "Games")'><?=$translator->__('Upcoming matches',$language)?></button>
		<button class='tablinks' onclick='openTab(event, "Table")'><?=$translator->__('Table',$language)?></button>
		<button class='tablinks' onclick='openTab(event, "Plan")'><?php
			if($playoff != null) {
				echo $translator->__('Playoff schedule',$language);
			}
			else if($playdown != null) {
				echo $translator->__('Playdown schedule',$language);
			}
			else {
				echo $translator->__('League schedule',$language);
			}
		?></button>
	</div>
</nav>
<div class='container'>
    <div id='Games' class='tabcontent' style='display: none;'>
<?php
foreach($games as $game)
{
	$home_team = get_team_by_id($con, $game['home_team_id']);
	$away_team = get_team_by_id($con, $game['away_team_id']);
	?>
	<div class='game'>
		<form method='POST' action=''>
		<table>
			<tr>
				<td><?=$game['game_day']?>. <?=$translator->__('Game day',$language)?></td>
				<td><div class='image-text-wrapper'><img src='images/<?=$home_team['id']?>.png' class='team-logo-small'/><p><?=$home_team['name']?></p></div></td>
				<td><div class='image-text-wrapper'><img src='images/<?=$away_team['id']?>.png' class='team-logo-small'/><p><?=$away_team['name']?></p></div></td>
			</tr>
			<tr>
				<td>1. <?=$translator->__('Period',$language)?></td>
				<td class='goal-container'><?php if($game['home_team_id'] == $user['team_id']) { ?>
						<input name='home_team_goal_1' style='goal-input' type='number' min='0' max='10' value='<?=$game['home_team_goal_1']?>' onfocus='this.select()'></input>
					<?php } else {
							if($state['day'] < 1) {?>
								?
					<?php } else { 
								echo $game['home_team_goal_1'];
						  } }?></td>
				<td class='goal-container'><?php if($game['away_team_id'] == $user['team_id']) { ?>
						<input name='away_team_goal_1' style='goal-input' type='number' min='0' max='10' value='<?=$game['away_team_goal_1']?>' onfocus='this.select()'></input>
					<?php } else {
							if($state['day'] < 1) {?>
								?
					<?php } else { 
							echo $game['away_team_goal_1'];
					  	  } }?></td>
			</tr>
			<tr>
				<td>2. <?=$translator->__('Period',$language)?></td>
				<td class='goal-container'><?php if($game['home_team_id'] == $user['team_id']) { ?>
						<input name='home_team_goal_2' style='goal-input' type='number' min='0' max='10' value='<?=$game['home_team_goal_2']?>' onfocus='this.select()'></input>
					<?php } else {
							if($state['day'] < 2) {?>
								?
					<?php } else { 
								echo $game['home_team_goal_2'];
						  } }?></td>
				<td class='goal-container'><?php if($game['away_team_id'] == $user['team_id']) { ?>
						<input name='away_team_goal_2' style='goal-input' type='number' min='0' max='10' value='<?=$game['away_team_goal_2']?>' onfocus='this.select()'></input>
					<?php } else {
							if($state['day'] < 2) {?>
								?
					<?php } else { 
							echo $game['away_team_goal_2'];
					  	  } }?></td>
			</tr>
			<tr>
				<td>3. <?=$translator->__('Period',$language)?></td>
				<td class='goal-container'><?php if($game['home_team_id'] == $user['team_id']) { ?>
						<input name='home_team_goal_3' style='goal-input' type='number' min='0' max='10' value='<?=$game['home_team_goal_3']?>' onfocus='this.select()'></input>
					<?php } else {
							if($state['day'] < 3) {?>
								?
					<?php } else { 
								echo $game['home_team_goal_3'];
						  } }?></td>
				<td class='goal-container'><?php if($game['away_team_id'] == $user['team_id']) { ?>
						<input name='away_team_goal_3' style='goal-input' type='number' min='0' max='10' value='<?=$game['away_team_goal_3']?>' onfocus='this.select()'></input>
					<?php } else {
							if($state['day'] < 3) {?>
								?
					<?php } else { 
							echo $game['away_team_goal_3'];
					  	  } }?></td>
			</tr>
			<tr>
				<td><?=$translator->__('Overtime',$language)?></td>
				<td class='goal-container'><?php if($game['home_team_id'] == $user['team_id']) { ?>
						<input name='home_team_goal_overtime' style='goal-input' type='number' min='0' max='10' value='<?=$game['home_team_goal_overtime']?>' onfocus='this.select()'></input>
					<?php } else {
							if($state['day'] < 3) {?>
								?
					<?php } else { 
								echo $game['home_team_goal_overtime'];
						  } }?></td>
				<td class='goal-container'><?php if($game['away_team_id'] == $user['team_id']) { ?>
						<input name='away_team_goal_overtime' style='goal-input' type='number' min='0' max='10' value='<?=$game['away_team_goal_overtime']?>' onfocus='this.select()'></input>
					<?php } else {
							if($state['day'] < 3) {?>
								?
					<?php } else { 
							echo $game['away_team_goal_overtime'];
					  	  } }?></td>
			</tr>
		</table>
		<?php if($state['day'] == 0) { ?>
		<input type='hidden' name='game_id' value='<?=$game['id']?>'></input>
		<?php } ?>
		</form>
	</div>
<?php }
?>
</div>
<?php if($playdown != null) {
?>
<div id='Table' class='tabcontent' style='display: none;'>
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
			<td><div class="image-text-wrapper"><img src='<?="images/".$team['team_id'].".png"?>' class='team-logo'/><?=$team['team_name']?>test</div></td>
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
<div id='Table' class='tabcontent' style='display: none;'>
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
			<td><div class="image-text-wrapper"><img src='<?="images/".$games[$i]['team1_id'].".png"?>' class='team-logo'/><?=$games[$i]['team1']?></div></td>
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
			<td><div class="image-text-wrapper"><img src='<?="images/".$games[$i]['team2_id'].".png"?>' class='team-logo'/><?=$games[$i]['team2']?></div></td>
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
<?php } else { ?>
<div id='Table' class='tabcontent' style='display: none;'>
	<table>
		<tr>
			<th>#</th>
			<th><?=$translator->__('Name',$language)?></th>
			<th><?=$translator->__('Username',$language)?></th>
			<th class='horizontal-xs'><?=$translator->__('Win',$language)?></th>
			<th class='horizontal-xs'><?=$translator->__('Lose',$language)?></th>
			<th class='horizontal-xs'><?=$translator->__('Goals',$language)?></th>
			<th class='horizontal-xs'><?=$translator->__('Points',$language)?></th>
		</tr>
<?php
$teams = get_team_by_points($con, $user['team_id'], 0);
$index = 0;
foreach($teams as $team) {
	$image = "images/".$team['id'].".png";
	$table_playoff = 8;
	$table_relegate = 0;
	if($user_league['name'] == 'NHL') {
		$table_playoff = 16;
	}
	if($user_league['id'] == 1) {
		$table_relegate = 2;
	}
	if($user_league['division'] > 1) {
		$table_playoff = 2;
		if($user_league['division'] < 3) {
			$table_relegate = 2;
		}
	}
	?>
		<tr>
			<td <?php 
				if($index < $table_playoff) {
					echo 'class="table-playoff"';
				}
				if($index >= count($teams) - $table_relegate) {
					echo 'class="table-relegate"';
				}
			?>><?=++$index?><?php
				if($index <= $table_playoff && $user_league['division'] > 1) { ?>
					<i class="fas fa-chevron-up table-playoff"></i>
				<?php }
				else if($index <= $table_playoff) { ?>
					<i class="fas fa-trophy table-playoff"></i>
				<?php }
				if($index > count($teams) - $table_relegate) { ?>
					<i class="fas fa-chevron-down table-relegate"></i>
				<?php }
			?></td>
			<td><div class="image-text-wrapper"><img src='<?=$image?>' class='team-logo'/><p><?=$team['name']?></p></div></td>
			<td><?=$team['username']?></td>
			<td class='goal-container'><?=$team['win']?></td>
			<td class='goal-container'><?=$team['lose']?></td>
			<td class='goal-container'><?=$team['goals_shot'].":".$team['goals_received']?></td>
			<td class='goal-container'><?=$team['points']?></td>
		</tr>
<?php
}
?>
	</table>
</div>
<?php }
// display game plan for playoff, playdown or league
if($playoff != null) { 
	$games = get_games_by_playoff($con, $playoff);
} else if ($playdown != null) {
	$games = get_games_by_playdown($con, $playdown);
} else {
	$games = get_games_by_league($con, $user_league);
}
?>
<div id='Plan' class='tabcontent' style='display: none;'>
	<?php
		foreach($games as $game_day) {
	?>
	<p><?=$translator->__('Game day',$language)?> <?=$game_day[0]['game_day']?></p>
	<table>
		<tr>
			<th>#</th>
			<th><?=$translator->__('Team',$language)?></th>
			<th><?=$translator->__('Team',$language)?></th>
			<th><?=$translator->__('Result',$language)?></th>
			<th class='hidden-xs'><?=$translator->__('P1',$language)?></th>
			<th class='hidden-xs'><?=$translator->__('P2',$language)?></th>
			<th class='hidden-xs'><?=$translator->__('P3',$language)?></th>
			<th class='hidden-xs'><?=$translator->__('Ot',$language)?></th>
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
			<td><img src='images/<?=$game['home_id']?>.png' class='team-logo-small'/><?=$game['home']?></td>
			<td><img src='images/<?=$game['away_id']?>.png' class='team-logo-small'/><?=$game['away']?></td>
			<td class='goal-container'><span style="white-space: nowrap;"><?php if($game['game_day'] <= $game['last_game_day']) echo ($game['home_team_goal_1'] + $game['home_team_goal_2'] + $game['home_team_goal_3'] + ($home_goals == $away_goals ? $game['home_team_goal_overtime'] : 0) + $game['home_team_penalty_win']) . ($game['home_team_penalty_win'] == 1 ? '*' : '') . " : " .  ($game['away_team_goal_1'] + $game['away_team_goal_2'] + $game['away_team_goal_3'] + ($home_goals == $away_goals ? $game['away_team_goal_overtime'] : 0) + $game['away_team_penalty_win']) . ($game['away_team_penalty_win'] == 1 ? '*' : '');?></span></td>
			<td class='goal-container hidden-xs'><span style="white-space: nowrap;"><?php if($game['game_day'] <= $game['last_game_day']) echo $game['home_team_goal_1'] . " : " . $game['away_team_goal_1'];?></span></td>
			<td class='goal-container hidden-xs'><span style="white-space: nowrap;"><?php if($game['game_day'] <= $game['last_game_day']) echo $game['home_team_goal_2'] . " : " . $game['away_team_goal_2'];?></span></td>
			<td class='goal-container hidden-xs'><span style="white-space: nowrap;"><?php if($game['game_day'] <= $game['last_game_day']) echo $game['home_team_goal_3'] . " : " . $game['away_team_goal_3'];?></span></td>
			<td class='goal-container hidden-xs'><span style="white-space: nowrap;"><?php if($game['game_day'] <= $game['last_game_day'] && $home_goals == $away_goals) echo $game['home_team_goal_overtime'] . " : " . $game['away_team_goal_overtime'];?></span></td>		
		</tr>
	<?php } ?>
	</table>
	<?php } ?>
	<p>* <?=$translator->__('victory after penalty shootout',$language)?></p>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function(){
  $('input').on('input', function() {
    var form = $(this).closest('form');
    var formData = form.serialize();

    $.ajax({
      type: 'POST',
      url: 'home_save.php',
      data: formData,
      success: function(response) {
		const userTeam = JSON.parse(response);
		var home_sum = userTeam['goal_account_home_1'] + userTeam['goal_account_home_2'] + userTeam['goal_account_home_3'];
		var away_sum = userTeam['goal_account_away_1'] + userTeam['goal_account_away_2'] + userTeam['goal_account_away_3'];
		document.getElementById("goal_home").innerHTML = home_sum + " (" + userTeam['goal_account_home_1'] + ", " + userTeam['goal_account_home_2'] + ", " + userTeam['goal_account_home_3'] + ")";
		document.getElementById("goal_away").innerHTML = away_sum + " (" + userTeam['goal_account_away_1'] + ", " + userTeam['goal_account_away_2'] + ", " + userTeam['goal_account_away_3'] + ")";
		document.getElementById("goal_overtime").innerHTML = userTeam["goal_account_overtime"];
      },
      error: function() {
        console.log('Error while saving data.');
      }
    });
  });
});
</script>
<?php
}
include 'content/footer.php';
?>
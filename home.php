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

// get last chat messages
$chat_messages = get_messages($con, $state['chat_message_count']);
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
<?php if (strlen($state['message']) > 0) { ?>
<div>
	<p><?=$translator->__('Message from you admin',$language)?>:</p>
	<div class='admin-message'>"<?=$state['message']?>"</div>
</div>
<?php } ?>
<div>
	<p><?=$translator->__('Chat',$language)?>:</p>
	<div class='chat-container' id='chat-container'>
        <?php foreach($chat_messages as $message) { 
            $dateTime = new DateTime($message['timestamp']);
            $weekday = $dateTime->format('l');
            $time = $dateTime->format('H:i'); ?>
		<div class='chat-message chat-color-<?=($message['user_id']%10)+1?>'><?=$translator->__($weekday, $language)?> <?=$time?> <?=$message['username']?>: <?=$message['message']?></div>
        <?php } ?>
	</div>
    <form id='chat-form' method='POST' action=''>
        <div class='chat-controls'>
			<input id='chat-message' name='chat-message' class='chat-input' maxlength='250'></input>
			<input id='chat-submit' type='submit' class='chat-submit' value='<?=$translator->__('Send', $language)?>'>
        </div>
	</form>
</div>
<div>
	<p><?=$translator->__('Goal stats',$language)?>:</p>
	<table>
		<tr>
			<td><?=$translator->__('Goals home',$language)?>:</td>
			<td><div id='goal_home'><?=$user_team['goal_account_home_1']+$user_team['goal_account_home_2']+$user_team['goal_account_home_3']?> (<?=$user_team['goal_account_home_1']?>, <?=$user_team['goal_account_home_2']?>, <?=$user_team['goal_account_home_3']?>)</div></td>
		</tr>
		<tr>
			<td><?=$translator->__('Goals away',$language)?>:</td>
			<td><div id='goal_away'><?=$user_team['goal_account_away_1']+$user_team['goal_account_away_2']+$user_team['goal_account_away_3']?> (<?=$user_team['goal_account_away_1']?>, <?=$user_team['goal_account_away_2']?>, <?=$user_team['goal_account_away_3']?>)</div></td>
		</tr>
		<tr>
			<td><?=$translator->__('Goals overtime',$language)?>:</td>
			<td><div id='goal_overtime'><?=$user_team['goal_account_overtime']?></div></td>
		</tr>
		<tr>
			<td><?=$translator->__('Earned bonus goals',$language)?>:</td>
			<td><div><?=$user_team['goal_account_bonus_home'] + $user_team['goal_account_bonus_away']?> (<?=$user_team['goal_account_bonus_home']?>, <?=$user_team['goal_account_bonus_away']?>)</div></td>
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
						<input name='home_team_goal_1' type='number' min='0' max='10' value='<?=$game['home_team_goal_1']?>' <?php if($state['day'] >= 1) { echo 'disabled'; } else {?> onfocus='this.select();'<?php } ?>></input>
					<?php } else {
							if($state['day'] < 1) {?>
								?
					<?php } else { 
								echo $game['home_team_goal_1'];
						  } }?></td>
				<td class='goal-container'><?php if($game['away_team_id'] == $user['team_id']) { ?>
						<input name='away_team_goal_1' type='number' min='0' max='10' value='<?=$game['away_team_goal_1']?>' <?php if($state['day'] >= 1) { echo 'disabled'; } else {?> onfocus='this.select();'<?php } ?>></input>
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
						<input name='home_team_goal_2' type='number' min='0' max='10' value='<?=$game['home_team_goal_2']?>' <?php if($state['day'] >= 2) { echo 'disabled'; } else {?> onfocus='this.select();'<?php } ?>></input>
					<?php } else {
							if($state['day'] < 2) {?>
								?
					<?php } else { 
								echo $game['home_team_goal_2'];
						  } }?></td>
				<td class='goal-container'><?php if($game['away_team_id'] == $user['team_id']) { ?>
						<input name='away_team_goal_2' type='number' min='0' max='10' value='<?=$game['away_team_goal_2']?>' <?php if($state['day'] >= 2) { echo 'disabled'; } else {?> onfocus='this.select();'<?php } ?>></input>
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
						<input name='home_team_goal_3' type='number' min='0' max='10' value='<?=$game['home_team_goal_3']?>' <?php if($state['day'] == 3) { echo 'disabled'; } else {?> onfocus='this.select();'<?php } ?>></input>
					<?php } else {
							if($state['day'] < 3) {?>
								?
					<?php } else { 
								echo $game['home_team_goal_3'];
						  } }?></td>
				<td class='goal-container'><?php if($game['away_team_id'] == $user['team_id']) { ?>
						<input name='away_team_goal_3' type='number' min='0' max='10' value='<?=$game['away_team_goal_3']?>' <?php if($state['day'] == 3) { echo 'disabled'; } else {?> onfocus='this.select();'<?php } ?>></input>
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
						<input name='home_team_goal_overtime' type='number' min='0' max='1' value='<?=$game['home_team_goal_overtime']?>' <?php if($state['day'] == 3) { echo 'disabled'; } else {?> onfocus='this.select();'<?php } ?>></input>
					<?php } else {
							if($state['day'] < 3) {?>
								?
					<?php } else { 
								echo $game['home_team_goal_overtime'];
						  } }?></td>
				<td class='goal-container'><?php if($game['away_team_id'] == $user['team_id']) { ?>
						<input name='away_team_goal_overtime' type='number' min='0' max='1' value='<?=$game['away_team_goal_overtime']?>' <?php if($state['day'] == 3) { echo 'disabled'; } else {?> onfocus='this.select();'<?php } ?>></input>
					<?php } else {
							if($state['day'] < 3) {?>
								?
					<?php } else { 
							echo $game['away_team_goal_overtime'];
					  	  } }?></td>
			</tr>
		</table>
		<input type='hidden' name='game_id' value='<?=$game['id']?>'></input>
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
			<th class='hidden-xs'><?=$translator->__('Games',$language)?></th>
			<th><?=$translator->__('Won',$language)?></th>
			<th class='hidden-xs'><?=$translator->__('OT',$language)?></th>
			<th class='hidden-xs'><?=$translator->__('PE',$language)?></th>
			<th><?=$translator->__('Lost',$language)?></th>
			<th class='hidden-xs'><?=$translator->__('OT',$language)?></th>
			<th class='hidden-xs'><?=$translator->__('PE',$language)?></th>
			<th><?=$translator->__('Goals',$language)?></th>
			<th class='hidden-xs'><?=$translator->__('Difference',$language)?></th>
			<th><?=$translator->__('Points',$language)?></th>
		</tr>
<?php
$teams = get_team_by_points($con, $user['team_id'], 1);
$index = 0;
foreach($teams as $team) {
	?>
		<tr>
			<td><?=++$index?></td>
			<td><div class="image-text-wrapper"><img src='<?="images/".$team['team_id'].".png"?>' class='team-logo'/><?=$team['team_name']?></div></td>
			<td class='goal-container hidden-xs'><?=$team['win']+$team['win_ot']+$team['win_pe']+$team['lose']+$team['lose_ot']+$team['lose_pe']?></td>
			<td class='goal-container'><?=$team['win']?></td>
			<td class='goal-container hidden-xs'><?=$team['win_ot']?></td>
			<td class='goal-container hidden-xs'><?=$team['win_pe']?></td>
			<td class='goal-container'><?=$team['lose']?></td>
			<td class='goal-container hidden-xs'><?=$team['lose_ot']?></td>
			<td class='goal-container hidden-xs'><?=$team['lose_pe']?></td>
			<td class='goal-container'><?=$team['goals_shot'].":".$team['goals_received']?></td>
			<td class='goal-container hidden-xs'><?=$team['goals_shot'] - $team['goals_received']?></td>
			<td class='goal-container'><?=$team['points']?></td>
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
		<th><?=$translator->__('Won',$language)?></th>
		<th><?=$translator->__('Lost',$language)?></th>
	</tr>
<?php
$data =	playoff_tables_by_league($con, $playoff);
$index = 0;
foreach($data as $table) {
	$index++;
	$teamNumber = 0;
	foreach($table as $team) {
		$teamNumber++;
?>
	<tr>
		<td><?php if($teamNumber % 2 != 0) echo $index; ?></td>
		<td><div class="image-text-wrapper"><img src='<?="images/".$team[0].".png"?>' class='team-logo'/><?php echo $team[1] ?></td>
		<td><?php echo $team[2] ?></td>
		<td><?php echo $team[3] ?></td>
	</tr>
<?php
	}
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
			<th class='hidden-xs'><?=$translator->__('Games',$language)?></th>
			<th class='horizontal-xs horizontal-md'><?=$translator->__('Won',$language)?></th>
			<th class='hidden-xs'><?=$translator->__('OT',$language)?></th>
			<th class='hidden-xs'><?=$translator->__('PE',$language)?></th>
			<th class='horizontal-xs horizontal-md'><?=$translator->__('Lost',$language)?></th>
			<th class='hidden-xs'><?=$translator->__('OT',$language)?></th>
			<th class='hidden-xs'><?=$translator->__('PE',$language)?></th>
			<th class='horizontal-xs'><?=$translator->__('Goals',$language)?></th>
			<th class='hidden-xs hidden-md'><?=$translator->__('Difference',$language)?></th>
			<th class='horizontal-xs'><?=$translator->__('Points',$language)?></th>
		</tr>
<?php
$teams = get_team_by_points($con, $user['team_id'], 0);
$index = 0;
foreach($teams as $team) {
	$image = "images/".$team['id'].".png";
	$table_playoff = 8;
	$table_relegate = 0;
	/*if($user_league['name'] == 'NHL') {
		$table_playoff = 16;
	}*/
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
			<td class='goal-container hidden-xs'><?=$team['win']+$team['win_ot']+$team['win_pe']+$team['lose']+$team['lose_ot']+$team['lose_pe']?></td>
			<td class='goal-container'><?=$team['win']?></td>
			<td class='goal-container hidden-xs'><?=$team['win_ot']?></td>
			<td class='goal-container hidden-xs'><?=$team['win_pe']?></td>
			<td class='goal-container'><?=$team['lose']?></td>
			<td class='goal-container hidden-xs'><?=$team['lose_ot']?></td>
			<td class='goal-container hidden-xs'><?=$team['lose_pe']?></td>
			<td class='goal-container'><?=$team['goals_shot'].":".$team['goals_received']?></td>
			<td class='goal-container hidden-xs hidden-md'><?=$team['goals_shot'] - $team['goals_received']?></td>
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
			if(isset($game['skip']) && $game['skip'] == 1)
			{
				continue;
			}
			$index++;
			$home_goals = $game['home_team_goal_1'] + $game['home_team_goal_2'] + $game['home_team_goal_3'];
			$away_goals = $game['away_team_goal_1'] + $game['away_team_goal_2'] + $game['away_team_goal_3'];
	?>
		<tr>
			<td><?=$index?></td>
			<td><img src='images/<?=$game['home_id']?>.png' class='team-logo-small'/><?=$game['home']?></td>
			<td><img src='images/<?=$game['away_id']?>.png' class='team-logo-small'/><?=$game['away']?></td>
			<td class='goal-container'><span style="white-space: nowrap;"><?php if($game['game_day'] <= $game['last_game_day']) echo display_game_result($game);?></span></td>
			<td class='goal-container hidden-xs'><span style="white-space: nowrap;"><?php if($game['game_day'] <= $game['last_game_day']) echo $game['home_team_goal_1'] . ":" . $game['away_team_goal_1'];?></span></td>
			<td class='goal-container hidden-xs'><span style="white-space: nowrap;"><?php if($game['game_day'] <= $game['last_game_day']) echo $game['home_team_goal_2'] . ":" . $game['away_team_goal_2'];?></span></td>
			<td class='goal-container hidden-xs'><span style="white-space: nowrap;"><?php if($game['game_day'] <= $game['last_game_day']) echo $game['home_team_goal_3'] . ":" . $game['away_team_goal_3'];?></span></td>
			<td class='goal-container hidden-xs'><span style="white-space: nowrap;"><?php if($game['game_day'] <= $game['last_game_day'] && $home_goals == $away_goals) echo $game['home_team_goal_overtime'] . ":" . $game['away_team_goal_overtime'];?></span></td>		
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
	const inputField = $(this);

	if(!formData.includes('chat-message')) {
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
		  inputField.removeClass('error-input');
        },
        error: function() {
		  inputField.addClass('error-input');
        }
      });
	}
  });

  $('#chat-container').scrollTop($('#chat-container')[0].scrollHeight);

  function updateChat() {
    $.ajax({
		type: 'POST',
		url: 'chat.php',
		success: function(response) {
            const messages = JSON.parse(response);
            $('#chat-container').empty();
            messages.forEach(message => {
                const chatMessage = document.createElement('div');
                chatMessage.className = 'chat-message chat-color-' + ((message.user_id%10) + 1);
                const date = new Date(message.timestamp);
                const weekday = new Intl.DateTimeFormat('de-DE', { weekday: 'long' }).format(date);
                const time = date.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' });
                chatMessage.textContent = weekday + ' ' + time + ' ' + message.username + ': ' + message.message;

                $('#chat-container').append(chatMessage);
            });
            $('#chat-container').scrollTop($('#chat-container')[0].scrollHeight);
        }
    });
  }

  const interval = setInterval(updateChat, 15000);

  $('#chat-form').submit(function(event) {
	event.preventDefault();
    if($('#chat-message').val() !='') {
	  var formData = { message: $('#chat-message').val() };
	  $.ajax({
		type: 'POST',
		url: 'chat.php',
		data: formData,
		success: function(response) {
          $('#chat-message').val('');
          $('#chat-submit').prop('disabled', true);
          updateChat();
          setTimeout(function() {
            $('#chat-submit').prop('disabled', false);
          }, 5000);
		},
		error: function() {
		  console.log("chat error");
		}
	  });
    }
  });
});
</script>
<?php
}
include 'content/footer.php';
?>
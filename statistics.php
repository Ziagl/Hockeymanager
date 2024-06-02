<?php
include_once 'config/functions.php';
include 'content/session.php';
include 'content/header.php';
?>
<h2><?=$translator->__('Statistics',$language)?></h2>
<?php $leagues = get_all_leagues($con);?>
<nav>
	<div class='tab'>
<?php foreach($leagues as $league) {?>
		<button class='tablinks' onclick='openTab(event, "<?=$league['name']?>")'><?=$league['name']?></button>
<?php } ?>
	</div>
</nav>
<div class='container'>
<?php
    foreach($leagues as $league) {	
?>
<div id='<?=$league['name']?>' class='tabcontent' style='display: none;'>
<div class='statistic-table'>
    <p><?=$translator->__('League table',$language)?> <?=$league['name']?> (<?=$league['last_game_day']?>/<?=$league['max_game_days']?>):</p>
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
$teams = get_team_by_points_of_league($con, $league['id']);
$index = 0;
foreach($teams as $team) {
	$table_playoff = 8;
	$table_relegate = 0;
	if($league['name'] == 'NHL') {
		$table_playoff = 16;
	}
	if($league['id'] == 1) {
		$table_relegate = 2;
	}
	if($league['division'] > 1) {
		$table_playoff = 2;
		if($league['division'] < 3) {
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
				if($index <= $table_playoff && $league['division'] > 1) { ?>
					<i class="fas fa-chevron-up table-playoff"></i>
				<?php }
				else if($index <= $table_playoff) { ?>
					<i class="fas fa-trophy table-playoff"></i>
				<?php }
				if($index > count($teams) - $table_relegate) { ?>
					<i class="fas fa-chevron-down table-relegate"></i>
				<?php }
			?></td>
			<td><div class='image-text-wrapper'><img src='images/<?=$team['id']?>.png' class='team-logo'/><p><?=$team['name']?></p></div></td>
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
<?php 
$playdown = get_playdown_by_league_id($con, $league['id']);
$playoff = get_playoff_by_league_id($con, $league['id']);
if($playoff) { ?>
<div class='statistic-table'>
    <p><?=$translator->__('Playoff table',$language)?> <?=$league['name']?></p>
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
			<td><?php if($playoff['last_game_day'] >= $games[$i]['game_day']) { if ($games[$i]['home_win'] > 0) {echo display_game_result($games[$i]); $team1_wins++;} else $team2_wins++; }?></td>
			<td><?php if($playoff['last_game_day'] >= $games[$i + 1]['game_day']) { if ($games[$i + 1]['home_win'] == 0) {echo display_game_result($games[$i + 1]); $team1_wins++;} else $team2_wins++; }?></td>
			<td><?php if($playoff['last_game_day'] >= $games[$i + 2]['game_day']) { if ($games[$i + 2]['home_win'] > 0) {echo display_game_result($games[$i + 2]); $team1_wins++;} else $team2_wins++; }?></td>
			<td><?php if($playoff['last_game_day'] >= $games[$i + 3]['game_day']) { if ($games[$i + 3]['home_win'] == 0) {echo display_game_result($games[$i + 3]); $team1_wins++;} else $team2_wins++; }?></td>
			<td><?php if($playoff['last_game_day'] >= $games[$i + 4]['game_day']) { if ($games[$i + 4]['home_win'] > 0 && $team1_wins < 4 && $team2_wins < 4) {echo display_game_result($games[$i + 4]); $team1_wins++;} else $team2_wins++; }?></td>
			<td><?php if($playoff['last_game_day'] >= $games[$i + 5]['game_day']) { if ($games[$i + 5]['home_win'] == 0 && $team1_wins < 4 && $team2_wins < 4) {echo display_game_result($games[$i + 5]); $team1_wins++;} else $team2_wins++; }?></td>
			<td><?php if($playoff['last_game_day'] >= $games[$i + 6]['game_day']) { if ($games[$i + 6]['home_win'] > 0 && $team1_wins < 4 && $team2_wins < 4) {echo display_game_result($games[$i + 6]); $team1_wins++;} else $team2_wins++; }?></td>
		</tr>
<?php
	$team1_wins = 0;
	$team2_wins = 0;
	?>
		<tr>
			<td></td>
			<td><div class="image-text-wrapper"><img src='<?="images/".$games[$i]['team2_id'].".png"?>' class='team-logo'/><?=$games[$i]['team2']?></div></td>
			<td><?php if($playoff['last_game_day'] >= $games[$i]['game_day']) { if ($games[$i]['home_win'] == 0) {echo display_game_result($games[$i]); $team2_wins++;} else $team1_wins++; }?></td>
			<td><?php if($playoff['last_game_day'] >= $games[$i + 1]['game_day']) { if ($games[$i + 1]['home_win'] > 0) {echo display_game_result($games[$i + 1]); $team2_wins++;} else $team1_wins++; }?></td>
			<td><?php if($playoff['last_game_day'] >= $games[$i + 2]['game_day']) { if ($games[$i + 2]['home_win'] == 0) {echo display_game_result($games[$i + 2]); $team2_wins++;} else $team1_wins++; }?></td>
			<td><?php if($playoff['last_game_day'] >= $games[$i + 3]['game_day']) { if ($games[$i + 3]['home_win'] > 0) {echo display_game_result($games[$i + 3]); $team2_wins++;} else $team1_wins++; }?></td>
			<td><?php if($playoff['last_game_day'] >= $games[$i + 4]['game_day']) { if ($games[$i + 4]['home_win'] == 0 && $team1_wins < 4 && $team2_wins < 4) {echo display_game_result($games[$i + 4]); $team2_wins++;} else $team1_wins++; }?></td>
			<td><?php if($playoff['last_game_day'] >= $games[$i + 5]['game_day']) { if ($games[$i + 5]['home_win'] > 0 && $team1_wins < 4 && $team2_wins < 4) {echo display_game_result($games[$i + 5]); $team2_wins++;} else $team1_wins++; }?></td>
			<td><?php if($playoff['last_game_day'] >= $games[$i + 6]['game_day']) { if ($games[$i + 6]['home_win'] == 0 && $team1_wins < 4 && $team2_wins < 4) {echo display_game_result($games[$i + 6]); $team2_wins++;} else $team1_wins++; }?></td>
		</tr>
<?php
}
?>
	</table>
</div>
<?php } ?>
<?php if($playdown) { ?>
<div class='statistic-table'>
	<p><?=$translator->__('Playdown table',$language)?> <?=$league['name']?></p>
	<table>
		<tr>
			<th>#</th>
			<th><?=$translator->__('Name',$language)?></th>
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
$teams = get_team_by_points($con, $teams[count($teams)-1]['id'], 1);
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
			<td class='goal-container hidden-xs hidden-md'><?=$team['goals_shot'] - $team['goals_received']?></td>
			<td class='goal-container'><?=$team['points']?></td>
		</tr>
<?php
}
?>
	</table>
</div>
<?php } ?>
</div>
<?php } ?>
<p></p>
</div>
<?php include 'content/footer.php'; ?>
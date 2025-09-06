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
<!-- playoff -->
<?php 
$teamsLeague = get_team_by_points_of_league($con, $league['id']);
$playdown = get_playdown_by_league_id($con, $league['id']);
$playoff = get_playoff_by_league_id($con, $league['id']);
if($playoff) { ?>
<div class='statistic-table'>
    <p><?=$translator->__('Playoff table',$language)?> <?=$league['name']?></p>
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
		$teamPositions = array();
		$teamPosition = 0;
		foreach($teamsLeague as $team)
		{
			$teamPosition++;
			$teamPositions[$team['id']] = $teamPosition;
		}
		foreach($table as $team) {
?>
		<tr>
			<td><?php echo $teamPositions[$team[0]]; ?></td>
			<td><div class="image-text-wrapper"><img src='<?="images/".$team[0].".png"?>' class='team-logo'/><?php echo $team[1] ?></td>
			<td><?php echo $team[2] ?></td>
			<td><?php echo $team[3] ?></td>
		</tr>
<?php
		}
	}
?>
	</table>
<?php
	// display playoff games
	$rounds = get_games_by_playoff($con, $playoff, true);
	$round = count($rounds);
	foreach($rounds as $games) { ?>
	<p><?=$translator->__('Playoff round',$language)?> <?=$round?></p>
<?php
	foreach($games as $game_day) {
		// count skipped games
		$skipped = 0;
		foreach($game_day as $game) {
			if(isset($game['skip']) && $game['skip'] == 1)
			{
				$skipped++;
			}
		}
		// do not show game day at all if all games are skipped
		if($skipped == count($game_day)) {
			continue;
		}
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
			<td class='goal-container'><span style="white-space: nowrap;"><?php if($game['game_day'] <= $game['last_game_day'] || $round < count($rounds)) echo display_game_result($game);?></span></td>
			<td class='goal-container hidden-xs'><span style="white-space: nowrap;"><?php if($game['game_day'] <= $game['last_game_day'] || $round < count($rounds)) echo $game['home_team_goal_1'] . ":" . $game['away_team_goal_1'];?></span></td>
			<td class='goal-container hidden-xs'><span style="white-space: nowrap;"><?php if($game['game_day'] <= $game['last_game_day'] || $round < count($rounds)) echo $game['home_team_goal_2'] . ":" . $game['away_team_goal_2'];?></span></td>
			<td class='goal-container hidden-xs'><span style="white-space: nowrap;"><?php if($game['game_day'] <= $game['last_game_day'] || $round < count($rounds)) echo $game['home_team_goal_3'] . ":" . $game['away_team_goal_3'];?></span></td>
			<td class='goal-container hidden-xs'><span style="white-space: nowrap;"><?php if(($game['game_day'] <= $game['last_game_day'] || $round < count($rounds)) && $home_goals == $away_goals) echo $game['home_team_goal_overtime'] . ":" . $game['away_team_goal_overtime'];?></span></td>		
		</tr>
	<?php } ?>
	</table>
	<?php } ?>
	<?php $round--; } ?>
	<p>* <?=$translator->__('victory after penalty shootout',$language)?></p>
<?php // display playoff games end ?>
</div>
<?php } if($playdown) { ?>
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
$teams = get_team_by_points($con, $teamsLeague[count($teamsLeague)-1]['id'], 1);
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
<?php } ?>
	</table>
<?php
	// display playdown games
	$games = get_games_by_playdown($con, $playoff);
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
<?php // display playdown games end ?>
</div>
<?php } ?>
<!-- league -->
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
$player_league = $league;
$index = 0;
foreach($teamsLeague as $team) {
	$table_playoff = 8;
	$table_relegate = 0;
	/*if($league['name'] == 'NHL') {
		$table_playoff = 16;
	}*/
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
// display league table
$games = get_games_by_league($con, $player_league);
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
<?php } ?>
<p></p>
</div>
<?php include 'content/footer.php'; ?>
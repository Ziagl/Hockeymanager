<?php
include_once 'config/functions.php';
include 'content/session.php';
include 'content/header.php';
?>
<h2><?=$translator->__('Statistics',$language)?></h2>
<?php 
    $leagues = get_all_leagues($con);
    foreach($leagues as $league) {	
?>
<div>
    <p><?=$translator->__('League table',$language)?> <?=$league['name']?> (<?=$league['last_game_day']?>/<?=$league['max_game_days']?>):</p>
    <table>
		<tr>
			<th>#</th>
			<th><?=$translator->__('Name',$language)?></th>
			<th><?=$translator->__('Username',$language)?></th>
			<th class='horizontal-xs'><?=$translator->__('Won',$language)?></th>
			<th class='horizontal-xs'><?=$translator->__('Lost',$language)?></th>
			<th class='horizontal-xs'><?=$translator->__('Goals',$language)?></th>
			<th class='hidden-xs'><?=$translator->__('Difference',$language)?></th>
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
			<td class='goal-container'><?=$team['win']?></td>
			<td class='goal-container'><?=$team['lose']?></td>
			<td class='goal-container'><?=$team['goals_shot'].":".$team['goals_received']?></td>
			<td class='goal-container hidden-xs'><?=$team['goals_shot'] - $team['goals_received']?></td>
			<td class='goal-container'><?=$team['points']?></td>
		</tr>
<?php
}
?>
	</table>
</div>
<?php } ?>
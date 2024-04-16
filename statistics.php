<?php
include_once 'config/functions.php';
include 'content/session.php';

// TODO

include 'content/header.php';
?>
<h2><?=$translator->__('Statistics',$language)?></h2>
<?php 
    $leagues = get_all_leagues($con);
    foreach($leagues as $league) {
?>
<div>
    <p><?=$translator->__('League table',$language)?> <?=$league['name']?>:</p>
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
$teams = get_team_by_points_of_league($con, $league['id']);
$index = 0;
foreach($teams as $team) {
	?>
		<tr<?php
	if($index == 0)
		echo ' style="background-color: #0f0"';
	if($index > count($teams) - 3)
		echo ' style="background-color: #f00"';
?>>
			<td><?=++$index?></td>
			<td><div class='image-text-wrapper'><img src='images/<?=$team['id']?>.png' class='team-logo-small'/><p><?=$team['name']?></p></div></td>
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
<?php } ?>
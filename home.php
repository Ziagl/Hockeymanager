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
?>
<h2>Home Page</h2>
<p>Welcome back, <?=$_SESSION['name']?>
<?php // user has set dream team but has no team yet
if($user['team_id'] == 0) { ?>
!</p>
You are waiting for the approval for your team. Please come back later.
<?php } else { 
// user has a team
$stmt = $con->prepare('SELECT * FROM Team WHERE id = ?');
$stmt->bind_param('i', $user['team_id']);
$stmt->execute();
$result = $stmt->get_result();
$user_team = $result->fetch_array();
$stmt->close();
// show league table
?>
, coach of <?=$user_team['name']?></p>
<div>
	<p>Goal stats:</p>
	<table>
		<tr>
			<td>Goals home:</td>
			<td><?=$user_team['goal_account_home_1']+$user_team['goal_account_home_2']+$user_team['goal_account_home_3']?></td>
		</tr>
		<tr>
			<td>Goals away:</td>
			<td><?=$user_team['goal_account_away_1']+$user_team['goal_account_away_2']+$user_team['goal_account_away_3']?></td>
		</tr>
		<tr>
			<td>Goals goal_account_overtime:</td>
			<td><?=$user_team['goal_account_overtime']?></td>
		</tr>
	</table>
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
<?php
include_once 'config.php';

$con = mysqli_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);
$con->set_charset("utf8");
if (mysqli_connect_errno()) {
	exit('Failed to connect to MySQL: ' . mysqli_connect_error());
}



// initialize a new geam -> reset all values
function initialize_game($con, $goal_account_home, $goal_account_away)
{
    // reset teams
    $stmt = $con->prepare('UPDATE Team SET points = 0, goals_shot = 0, goals_received = 0, win = 0, lose = 0, goal_account_home = ?, goal_account_away = ?');
	$stmt->bind_param('ii', $goal_account_home, $goal_account_away);
	$stmt->execute();
}
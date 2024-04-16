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

// create response

// user has a team
$user_team = get_team_by_id($con, $user['team_id']);

echo json_encode($user_team);
?>
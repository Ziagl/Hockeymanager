<?php
include_once 'config.php';

$con = mysqli_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);
$con->set_charset("utf8");
if (mysqli_connect_errno()) {
	exit('Failed to connect to MySQL: ' . mysqli_connect_error());
}

// gets a team from database by its id
function get_team_by_id($con, $id)
{
    $stmt = $con->prepare('SELECT * FROM Team WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_team = $result->fetch_array();
    $stmt->close();

    return $user_team;
}

function to_next_day($con)
{
    // update last_game_day of League
}

// initialize a new geam -> reset all values
function initialize_game($con, $goal_account_home, $goal_account_away, $goal_account_overtime)
{
    // reset teams
    $stmt = $con->prepare('UPDATE Team SET points = 0, goals_shot = 0, goals_received = 0, win = 0, lose = 0, goal_account_home_1 = ?, goal_account_home_2 = ?, goal_account_home_3 = ?, goal_account_away_1 = ?, goal_account_away_2 = ?, goal_account_away_3 = ?, goal_account_overtime = ?');
	$stmt->bind_param('iiiiiii', $goal_account_home, $goal_account_home, $goal_account_home, $goal_account_away, $goal_account_away, $goal_account_away, $goal_account_overtime);
	$stmt->execute();

    // reset games
    $stmt = $con->prepare('TRUNCATE TABLE Game');
    $stmt->execute();

    // create calendar
    // 1. get all leagues
    $stmt = $con->prepare('SELECT * FROM League');
    $stmt->execute();
    $result = $stmt->get_result();
    while($league = $result->fetch_array()) {
        $leagues[] = $league['id'];
    }
    $stmt->close();

    // 2. get teams of leage
    foreach($leagues as $league)
    {
        $teams = array();
        $stmt = $con->prepare('SELECT * FROM Team WHERE league_id = ?');
        $stmt->bind_param('i', $league);
        $stmt->execute();
        $result = $stmt->get_result();
        while($team = $result->fetch_array()) {
            $teams[] = $team['id'];
        }
        $stmt->close();

        // 3. generate combinations
        $combinations = find_combinations($teams);
        $inverted_combinations = invert_combinations($combinations);

        // 4. create games (4 rounds 2 home, 2 away)
        $max_combinations = count($combinations);
        create_games($con, $combinations, 0);
        create_games($con, $inverted_combinations, $max_combinations);
        if($max_combinations < 20)
        {
            create_games($con, $combinations, $max_combinations * 2);
            create_games($con, $inverted_combinations, $max_combinations * 3);
        }
    }
}

// add game combinations to database
function create_games($con, $combinations, $last_game_day)
{
    foreach($combinations as $combination)
    {
        $last_game_day++;
        foreach($combination as $game)
        {
            $stmt = $con->prepare('INSERT INTO Game (game_day, home_team_id, away_team_id) VALUES (?, ?, ?)');
            $stmt->bind_param('iii', $last_game_day, $game[0], $game[1]);
            $stmt->execute();
        }
    }
}

// find all combinations for each possible team pair
function find_combinations($teams)
{
    $result = array();
    $number_of_game_days = count($teams) - 1;
    $number_of_matches_per_game_day = count($teams) / 2;
    $first_team = $teams[0];
    $rest_of_teams = $teams;
    array_splice($rest_of_teams, 0, 1);

    for($i = 0; $i < $number_of_game_days; ++$i)
    {
        $game_day = array();

        if($i % 2 == 0)
        {
            $game_day[] = array($first_team, $rest_of_teams[$i]);
        }
        else
        {
            $game_day[] = array($rest_of_teams[$i], $first_team);
        }

        for($j = 0; $j < $number_of_matches_per_game_day - 1; ++$j)
        {
            $index1 = ($i + $j + 1) % count($rest_of_teams);
            $index2 = ($i - $j - 1 + count($rest_of_teams)) % count($rest_of_teams);
            $game_day[] = array($rest_of_teams[$index1], $rest_of_teams[$index2]);
        }

        $result[] = $game_day;
    }

    shuffle($result);
    return $result;
}

// inverts a given combinations structure of games
function invert_combinations($combinations)
{
    $result = array();
    foreach($combinations as $combination)
    {
        $games = array();
        foreach($combination as $game)
        {
            $games[] = array($game[1], $game[0]);
        }
        $result[] = $games;
    }

    return $result;
}
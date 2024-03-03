<?php
include_once 'config.php';
include_once 'Translator.php';

$con = mysqli_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);
$con->set_charset("utf8");
if (mysqli_connect_errno()) {
	exit('Failed to connect to MySQL: ' . mysqli_connect_error());
}

$translator = new Translator();
$language = 'de';

// gets a team from database by id
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

function get_team_by_points($con, $id)
{
    $stmt = $con->prepare('SELECT * FROM Team WHERE league_id LIKE (SELECT l.id FROM League l, Team t WHERE l.id = t.league_id AND t.id = ?) ORDER BY points DESC, win DESC');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $teams = array();
    while($team = $result->fetch_array())
    {
        $teams[] = $team;
    }
    $stmt->close();

    return $teams;
}

// gets a league from database by id
function get_league_by_id($con, $id)
{
    $stmt = $con->prepare('SELECT l.* FROM League l JOIN Team t ON t.league_id = l.id WHERE t.id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $league = $result->fetch_array();
    $stmt->close();

    return $league;
}

function get_all_leagues($con)
{
    $stmt = $con->prepare('SELECT * FROM League');
    $stmt->execute();
    $result = $stmt->get_result();
    $leagues = array();
    while($league = $result->fetch_array()) {
        $leagues[] = $league;
    }
    $stmt->close();

    return $leagues;
}

// gets a game from database by id
function get_game_by_id($con, $id)
{
    $stmt = $con->prepare('SELECT * FROM Game WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $game = $result->fetch_array();
    $stmt->close();

    return $game;
}

function get_games_by_league($con, $league)
{
    $stmt = $con->prepare('SELECT g.*, thome.name as "home", taway.name as "away", l.last_game_day FROM Game g JOIN Team thome ON thome.id = g.home_team_id JOIN Team taway ON taway.id = g.away_team_id JOIN League l ON thome.league_id = l.id WHERE l.id = ? ORDER BY g.game_day ASC');
    $stmt->bind_param('i', $league['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $games = array();
    while($game = $result->fetch_array()){
        $games[$game['game_day']][] = $game;
    }
    $stmt->close();

    return $games;
}

function get_game_day($con)
{
    $stmt = $con->prepare('SELECT * FROM State WHERE id = 1');
    $stmt->execute();
    $result = $stmt->get_result();
    $state = $result->fetch_array();
    $stmt->close();

    return $state;
}

function get_games_of_week($con, $league)
{
    $games_per_week = $league['name'] == 'NHL' ? 5 : 4;
    $last_game_day = $league['last_game_day'] + $games_per_week;
    $first_game_day = $league['last_game_day'];
    $stmt = $con->prepare('SELECT g.* FROM Game g JOIN Team t ON t.id = g.home_team_id JOIN League l ON l.id = t.league_id WHERE game_day <= ? AND game_day > ? AND l.id = ?');
    $stmt->bind_param('iii', $last_game_day, $first_game_day, $league['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $games = array();
    while($game = $result->fetch_array())
    {
        $games[] = $game;
    }
    $stmt->close();

    return $games;
}

// proceed to next game day
function to_next_day($con)
{
    // update last_game_day of League
    $state = get_game_day($con);

    if($state['day'] == 0) {
        team_ai($con, $state);
    }

    $state['day'] = ((int)$state['day']) + 1;
    if($state['day'] > 3) {
        $state['day'] = 0;
        $state['week'] = ((int)$state['week']) + 1;
        
        update_stats($con, $state['week']);

        $stmt = $con->prepare('UPDATE League SET last_game_day = last_game_day + 4 WHERE name <> "NHL"');
        $stmt->execute();
        $stmt->close();
        $stmt = $con->prepare('UPDATE League SET last_game_day = last_game_day + 5 WHERE name = "NHL"');
        $stmt->execute();
        $stmt->close();
    }

    $stmt = $con->prepare('UPDATE State SET day = ?, week = ? WHERE id = 1');
    $stmt->bind_param('ii', $state['day'], $state['week']);
    $stmt->execute();
    $stmt->close();
}

// check if given team is not controlled by user
function is_ai_team($con, $team_id)
{
    $stmt = $con->prepare('SELECT * FROM User WHERE team_id = ?');
    $stmt->bind_param('i', $team_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $is_ai_team = true;
    if($result->fetch_array()) {
        $is_ai_team = false;
    }
    $stmt->close();

    return $is_ai_team;
}

function get_random_goals()
{
    $goals = array();

    for($i=0; $i<4; ++$i) {
        $random_value = random_int(0, 99);
        if ($random_value >= 50 && $random_value < 80) {
            $goals[] = 1;
        } else if ($random_value >= 80 && $random_value < 95) {
            $goals[] = 2;
        } else if ($random_value >= 95) {
            $goals[] = 3;
        } else {
            $goals[] = 0;
        }
    }

    return $goals;
}

// team ai that automatically sets goals
function team_ai($con, $state)
{
    $leagues = get_all_leagues($con);

    foreach($leagues as $league) {
        $games = get_games_of_week($con, $league);
        foreach($games as $game) {
            if(is_ai_team($con, $game['home_team_id'])) {
                $goals = get_random_goals();
                $stmt = $con->prepare('UPDATE Game SET home_team_goal_1 = ?, home_team_goal_2 = ?, home_team_goal_3 = ?, home_team_goal_overtime = ? WHERE id = ?');
                $stmt->bind_param('iiiii', $goals[0], $goals[1], $goals[2], $goals[3], $game['id']);
                $stmt->execute();
                $stmt->close();
            }

            if(is_ai_team($con, $game['away_team_id'])) {
                $goals = get_random_goals();
                $stmt = $con->prepare('UPDATE Game SET away_team_goal_1 = ?, away_team_goal_2 = ?, away_team_goal_3 = ?, away_team_goal_overtime = ? WHERE id = ?');
                $stmt->bind_param('iiiii', $goals[0], $goals[1], $goals[2], $goals[3], $game['id']);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
}

// get values from last week into team stats
function update_stats($con, $week)
{
    $leagues = get_all_leagues($con);

    // calculate stats for each league
    foreach($leagues as $league) {
        $games = get_games_of_week($con, $league);
        foreach($games as $game) {
            // home team
            $goals_home = $game['home_team_goal_1'] + $game['home_team_goal_2'] + $game['home_team_goal_3'];
            $overtime_home = $game['home_team_goal_overtime'];
            // away team
            $goals_away = $game['away_team_goal_1'] + $game['away_team_goal_2'] + $game['away_team_goal_3'];
            $overtime_away = $game['away_team_goal_overtime'];

            $home_win = $goals_home > $goals_away ? 1 : 0;
            $away_win = $goals_away > $goals_home ? 1 : 0;
            $home_lose = $goals_home < $goals_away ? 1 : 0;
            $away_lose = $goals_away < $goals_home ? 1 : 0;
            $draw = $home_lose == 0 && $away_lose == 0 ? 1 : 0;

            $home_points = 1;
            $away_points = 1;
            if($home_win == 1) {
                $home_points = 3;
                $away_points = 0;
            }
            if($away_win == 1) {
                $home_points = 0;
                $away_points = 3;
            }

            // if it is draw after 3 periods -> use overtime goals
            if($draw)
            {
                $home_win = $overtime_home > $overtime_away ? 1 : 0;
                $away_win = $overtime_away > $overtime_home ? 1 : 0;
                $home_lose = $overtime_home < $overtime_away ? 1 : 0;
                $away_lose = $overtime_away < $overtime_home ? 1 : 0;
                $draw = $home_lose == 0 && $away_lose == 0 ? 1 : 0;

                $home_points = 1;
                $away_points = 1;
                if($home_win == 1) {
                    $home_points = 2;
                    $away_points = 0;
                }
                if($away_win == 1) {
                    $home_points = 0;
                    $away_points = 2;
                }
            } 
            // no overtime -> restore used overtime goals
            else {
                if($overtime_home > 0) {
                    $stmt = $con->prepare('UPDATE Team SET goal_account_overtime = goal_account_overtime + ? WHERE id = ?');
                    $stmt->bind_param('ii', $overtime_home, $game['home_team_id']);
                    $stmt->execute();
                    $stmt->close();
                }
                if($overtime_away > 0) {
                    $stmt = $con->prepare('UPDATE Team SET goal_account_overtime = goal_account_overtime + ? WHERE id = ?');
                    $stmt->bind_param('ii', $overtime_away, $game['away_team_id']);
                    $stmt->execute();
                    $stmt->close();
                }
            }

            $stmt = $con->prepare('UPDATE Team SET points = points + ?, win = win + ?, lose = lose + ?, draw = draw + ?, goals_shot = goals_shot + ?, goals_received = goals_received + ? WHERE id = ?');
            $stmt->bind_param('iiiiiii', $home_points, $home_win, $home_lose, $draw, $goals_home, $goals_away, $game['home_team_id']);
            $stmt->execute();
            $stmt->close();

            $stmt = $con->prepare('UPDATE Team SET points = points + ?, win = win + ?, lose = lose + ?, draw = draw + ?, goals_shot = goals_shot + ?, goals_received = goals_received + ? WHERE id = ?');
            $stmt->bind_param('iiiiiii', $away_points, $away_win, $away_lose, $draw, $goals_away, $goals_home, $game['away_team_id']);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// initialize a new geam -> reset all values
function initialize_game($con, $goal_account_home, $goal_account_away, $goal_account_overtime)
{
    // reset state
    $stmt = $con->prepare('DELETE FROM State');
    $stmt->execute();
    $stmt->close();

    $stmt = $con->prepare('TRUNCATE TABLE State');
    $stmt->execute();
    $stmt->close();

    $stmt = $con->prepare('INSERT INTO State (day, week) VALUES (0, 0)');
    $stmt->execute();
    $stmt->close();

    // reset teams
    $stmt = $con->prepare('UPDATE Team SET points = 0, goals_shot = 0, goals_received = 0, win = 0, lose = 0, draw = 0, goal_account_home_1 = ?, goal_account_home_2 = ?, goal_account_home_3 = ?, goal_account_away_1 = ?, goal_account_away_2 = ?, goal_account_away_3 = ?, goal_account_overtime = ?');
	$stmt->bind_param('iiiiiii', $goal_account_home, $goal_account_home, $goal_account_home, $goal_account_away, $goal_account_away, $goal_account_away, $goal_account_overtime);
	$stmt->execute();
    $stmt->close();

    // reset league
    $stmt = $con->prepare('UPDATE League SET last_game_day = 0');
	$stmt->execute();
    $stmt->close();

    // reset games
    $stmt = $con->prepare('TRUNCATE TABLE Game');
    $stmt->execute();
    $stmt->close();

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
            $stmt->close();
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
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

function get_playoff_team_by_id($con, $id)
{
    $stmt = $con->prepare('SELECT * FROM PlayoffTeam WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_team = $result->fetch_array();
    $stmt->close();

    return $user_team;
}

function get_team_by_points($con, $id, $type) // type...0 league, 1 playdown
{
    if($type == 1) {
        $stmt = $con->prepare('SELECT t.*, t1.id as team_id, t1.name as team_name, u.username, (t.goals_shot - t.goals_received) as goals FROM PlaydownTeam t JOIN Team t1 ON t1.id = t.team_id LEFT JOIN User u ON u.team_id = t.team_id WHERE t.playdown_id = (SELECT id FROM Playdown WHERE team_id_1 = ? OR team_id_2 = ? OR team_id_3 = ? OR team_id_4 = ?) ORDER BY t.points DESC, goals DESC, t.win DESC');
        $stmt->bind_param('iiii', $id, $id, $id, $id);
    } else if($type == 0) {
        $stmt = $con->prepare('SELECT t.*, u.username, (t.goals_shot - t.goals_received) as goals FROM Team t LEFT JOIN User u ON u.team_id = t.id WHERE league_id LIKE (SELECT l.id FROM League l, Team t WHERE l.id = t.league_id AND t.id = ?) ORDER BY points DESC, goals DESC, win DESC');
        $stmt->bind_param('i', $id);
    }
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

function get_team_by_points_of_league($con, $league_id)
{
    $stmt = $con->prepare('SELECT t.*, u.username, (t.goals_shot - t.goals_received) as goals FROM Team t LEFT JOIN User u ON u.team_id = t.id WHERE league_id = ? ORDER BY points DESC, goals DESC, win DESC');
    $stmt->bind_param('i', $league_id);
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

function get_league_standing($con, $league_id)
{
    $stmt = $con->prepare('SELECT *, (goals_shot - goals_received) as goals FROM Team WHERE league_id = ? ORDER BY points DESC, goals DESC, win DESC');
    $stmt->bind_param('i', $league_id);
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
    $stmt = $con->prepare('SELECT * FROM League ORDER BY order_number');
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

function get_playdown_game_by_id($con, $id)
{
    $stmt = $con->prepare('SELECT * FROM PlaydownGame WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $game = $result->fetch_array();
    $stmt->close();

    return $game;
}

function get_playoff_game_by_id($con, $id)
{
    $stmt = $con->prepare('SELECT * FROM PlayoffGame WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $game = $result->fetch_array();
    $stmt->close();

    return $game;
}

function get_games_by_league($con, $league)
{
    $stmt = $con->prepare('SELECT g.*, thome.name as "home", thome.id as "home_id", taway.name as "away", taway.id as "away_id", l.last_game_day FROM Game g JOIN Team thome ON thome.id = g.home_team_id JOIN Team taway ON taway.id = g.away_team_id JOIN League l ON thome.league_id = l.id WHERE l.id = ? ORDER BY g.game_day ASC');
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

function get_games_by_playdown($con, $playdown)
{
    $stmt = $con->prepare('SELECT g.*, thome.name as "home", thome.id as "home_id",  taway.name as "away", taway.id as "away_id", p.last_game_day FROM PlaydownGame g JOIN Team thome ON thome.id = g.home_team_id JOIN Team taway ON taway.id = g.away_team_id JOIN Playdown p ON p.id = g.playdown_id WHERE playdown_id = ? ORDER BY game_day ASC');
    $stmt->bind_param('i', $playdown['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $games = array();
    while($game = $result->fetch_array()){
        $games[$game['game_day']][] = $game;
    }
    $stmt->close();

    return $games;
}

function get_games_by_playoff($con, $playoff)
{
    $stmt = $con->prepare('SELECT g.*, thome.name as "home", thome.id as "home_id",  taway.name as "away", taway.id as "away_id", p.last_game_day FROM PlayoffGame g JOIN Team thome ON thome.id = g.home_team_id JOIN Team taway ON taway.id = g.away_team_id JOIN Playoff p ON p.id = g.playoff_id WHERE playoff_id = ? ORDER BY game_day ASC');
    $stmt->bind_param('i', $playoff['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $games = array();
    while($game = $result->fetch_array()){
        $games[$game['game_day']][] = $game;
    }
    $stmt->close();

    return $games;
}

function get_playdown_by_league_id($con, $league_id)
{
    $stmt = $con->prepare('SELECT * FROM Playdown WHERE league_id_up = ?');
    $stmt->bind_param('i', $league_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $playdown = $result->fetch_array();
    $stmt->close();

    return $playdown;
}

function get_playoff_by_league_id($con, $league_id)
{
    $stmt = $con->prepare('SELECT * FROM Playoff WHERE league_id = ?');
    $stmt->bind_param('i', $league_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $playoff = $result->fetch_array();
    $stmt->close();

    return $playoff;
}

function get_all_playdown($con)
{
    $stmt = $con->prepare('SELECT * FROM Playdown');
    $stmt->execute();
    $result = $stmt->get_result();
    $playdowns = array();
    while($playdown = $result->fetch_array()) {
        $playdowns[] = $playdown;
    }
    $stmt->close();

    return $playdowns;
}

function get_games_of_playdown_and_game_day($con, $playdown, $game_day)
{
    $stmt = $con->prepare('SELECT * FROM PlaydownGame WHERE playdown_id = ? AND game_day = ?');
    $stmt->bind_param('ii', $playdown['id'], $game_day);
    $stmt->execute();
    $result = $stmt->get_result();
    $games = array();
    while($game = $result->fetch_array()) {
        $games[] = $game;
    }
    $stmt->close();

    return $games;
}

function get_games_of_playoff_and_round_and_game_day($con, $playoff, $game_day, $round)
{
    $stmt = $con->prepare('SELECT * FROM PlayoffGame WHERE playoff_id = ? AND game_day = ? AND round = ?');
    $stmt->bind_param('iii', $playoff['id'], $game_day, $round);
    $stmt->execute();
    $result = $stmt->get_result();
    $games = array();
    while($game = $result->fetch_array()) {
        $games[] = $game;
    }
    $stmt->close();

    return $games;
}

function find_sub_league($con, $league)
{
    $next_division = ((int)$league['division']) + 1;
    $stmt = $con->prepare('SELECT * FROM League WHERE country_id = ? AND division = ?');
    $stmt->bind_param('ii', $league['country_id'], $next_division);
    $stmt->execute();
    $result = $stmt->get_result();
    $league_result = $result->fetch_array();
    $stmt->close();

    return $league_result;
}

function get_play_down($con, $team_id)
{
    $stmt = $con->prepare('SELECT * FROM Playdown WHERE team_id_1 = ? OR team_id_2 = ? OR team_id_3 = ? OR team_id_4 = ?');
    $stmt->bind_param('iiii', $team_id, $team_id, $team_id, $team_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $return = $result->fetch_array();
    $stmt->close();

    return $return;
}

function get_play_off($con, $team_id)
{
    $stmt = $con->prepare('SELECT * FROM Playoff WHERE team_id_1 = ? OR team_id_2 = ? OR team_id_3 = ? OR team_id_4 = ? OR team_id_5 = ? OR team_id_6 = ? OR team_id_7 = ? OR team_id_8 = ?');
    $stmt->bind_param('iiiiiiii', $team_id, $team_id, $team_id, $team_id, $team_id, $team_id, $team_id, $team_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $return = $result->fetch_array();
    $stmt->close();

    return $return;
}

function playoff_games_by_league($con, $playoff)
{
    $round = $playoff['last_round'] + 1;
    $stmt = $con->prepare('SELECT g.*, t1.name as team1, t1.id as team1_id, t2.name as team2, t2.id as team2_id FROM PlayoffGame g JOIN Team t1 ON t1.id = g.home_team_id JOIN Team t2 ON t2.id = g.away_team_id WHERE g.round = ? AND g.playoff_id = ? ');
    $stmt->bind_param('ii', $round, $playoff['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $games = array();
    while($game = $result->fetch_array()) {
        $games[] = $game;
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
        team_ai($con);
    }

    $state['day'] = ((int)$state['day']) + 1;
    if($state['day'] > 3) {
        $state['day'] = 0;
        $state['week'] = ((int)$state['week']) + 1;
        
        $leagues = get_all_leagues($con);
        $leagues_to_create_playdown = array();
        $leagues_to_create_playoff = array();
        foreach($leagues as $league) {
            $playdown = get_playdown_by_league_id($con, $league['id']);
            $playoff = get_playoff_by_league_id($con, $league['id']);

            // end of saison -> playdown and playoff handling
            if(($playdown != null && $league['country_id'] == 1) || $playoff != null) {
                // playdown day by day
                if($playdown != null && $league['country_id'] == 1) {
                    team_ai_playdown($con, $playdown);

                    $next_game_day = $playdown['last_game_day'] + 1;
                    if($next_game_day <= $playdown['max_game_days']) {
                        update_playdown_stats($con, $next_game_day, $playdown);

                        $stmt = $con->prepare('UPDATE Playdown SET last_game_day = last_game_day + 1 WHERE id = ?');
                        $stmt->bind_param('i', $playdown['id']);
                        $stmt->execute();
                        $stmt->close();
                    }
                }

                // playoff day by day
                if($playoff != null) {
                    // move to next game day
                    if($playoff['last_game_day'] < 7) {
                        team_ai_playoff($con, $playoff);

                        // update teams
                        $round = $playoff['last_round'] + 1;
                        $game_day = $playoff['last_game_day'] + 1;
                        update_playoff_stats($con, $game_day, $round, $playoff);

                        $stmt = $con->prepare('UPDATE Playoff SET last_game_day = last_game_day + 1 WHERE id = ?');
                        $stmt->bind_param('i', $playoff['id']);
                        $stmt->execute();
                        $stmt->close();
                    } else {
                        // get result of last round
                        $games = playoff_games_by_league($con, $playoff);

                        // move to next round
                        $stmt = $con->prepare('UPDATE Playoff SET last_round = last_round + 1, last_game_day = 0 WHERE id = ?');
                        $stmt->bind_param('i', $playoff['id']);
                        $stmt->execute();
                        $stmt->close();

                        $playoff = get_playoff_by_league_id($con, $league['id']);
                        
                        // get winner teams
                        $playoff_teams = array();
                        for($i = 0; $i < count($games); $i += 7) {
                            $score_team_1 = 0;
                            $score_team_2 = 0;
                            
                            if($games[$i]['home_win'] > 0) $score_team_1++; else $score_team_2++;
                            if($games[$i + 1]['home_win'] > 0) $score_team_2++; else $score_team_1++;
                            if($games[$i + 2]['home_win'] > 0) $score_team_1++; else $score_team_2++;
                            if($games[$i + 3]['home_win'] > 0) $score_team_2++; else $score_team_1++;
                            if($games[$i + 4]['home_win'] > 0) $score_team_1++; else $score_team_2++;
                            if($games[$i + 5]['home_win'] > 0) $score_team_2++; else $score_team_1++;
                            if($games[$i + 6]['home_win'] > 0) $score_team_1++; else $score_team_2++;

                            if($score_team_1 > $score_team_2) {
                                $playoff_teams[] = get_team_by_id($con, $games[$i]['home_team_id']);
                            } else {
                                $playoff_teams[] = get_team_by_id($con, $games[$i]['away_team_id']);
                            }
                        }

                        if(count($playoff_teams) > 1) {
                            // create next games
                            $max_game_days = count($playoff_teams) - 1;

                            // create playoff games
                            $round = $playoff['last_round'] + 1;
                            for($i=0; $i < count($playoff_teams); $i+=2){
                                create_playoff_games($con, 0, $playoff['id'], $round, $playoff_teams[$i]['id'], $playoff_teams[$i+1]['id']);
                            }

                            // delete old entries and create playoff teams
                            $stmt = $con->prepare('DELETE FROM PlayoffTeam WHERE playoff_id = ?');
                            $stmt->bind_param('i', $playoff['id']);
                            $stmt->execute();
                            $stmt->close();

                            foreach($playoff_teams as $team_id) {
                                $stmt = $con->prepare('INSERT INTO PlayoffTeam (playoff_id, team_id) VALUES (?, ?)');
                                $stmt->bind_param('ii', $playoff['id'], $team_id);
                                $stmt->execute();
                                $stmt->close();
                            }
                        } else {
                            // season is over!!!!!
                            $stmt = $con->prepare('UPDATE State SET season_over = 1');
                            $stmt->execute();
                            $stmt->close();
                        }
                    }
                }
            }
            // default season handling
            else {
                // store last game day
                if($league['last_game_day'] < $league['max_game_days']) {
                    update_stats_of_league($con, $state['week'], $league);

                    if($league['name'] == 'NHL') {
                        $league['last_game_day'] = $league['last_game_day'] + 5;
                        if($league['last_game_day'] > $league['max_game_days']) {
                            $league['last_game_day'] = $league['max_game_days'];
                        }
                    } else {
                        $league['last_game_day'] = $league['last_game_day'] + 4;
                    }
                    $stmt = $con->prepare('UPDATE League SET last_game_day = ? WHERE id = ?');
                    $stmt->bind_param('ii', $league['last_game_day'], $league['id']);
                    $stmt->execute();
                    $stmt->close();
                }

                // calculate playdowns
                if($league['last_game_day'] == $league['max_game_days']) {
                    // if country is germany
                    if($league['country_id'] == 1) {
                        if($playdown == null) {
                            $sub_league = find_sub_league($con, $league);
                            if($sub_league) {
                                $leagues_to_create_playdown[] = $league;
                            }
                        }
                    }

                    // calculate playoffs
                    if($league['division'] == 1) {
                        if($playoff == null) {
                            $leagues_to_create_playoff[] = $league;
                        }
                    }
                }
            }
        }

        // now actually create playdown data
        foreach($leagues_to_create_playdown as $league){
            $sub_league = find_sub_league($con, $league);
            if($sub_league) {
                $teams = get_league_standing($con, $league['id']);
                $sub_teams = get_league_standing($con, $sub_league['id']);

                $playdown_teams = array();
                $playdown_teams[] = $teams[count($teams) - 2]['id'];
                $playdown_teams[] = $teams[count($teams) - 1]['id'];
                $playdown_teams[] = $sub_teams[0]['id'];
                $playdown_teams[] = $sub_teams[1]['id'];

                // create playdown reference table
                $max_game_days = (count($playdown_teams) - 1) * 2;
                $stmt = $con->prepare('INSERT INTO Playdown (league_id_up, league_id_down, max_game_days, last_game_day, team_id_1, team_id_2, team_id_3, team_id_4) VALUES (?, ?, ?, 0, ?, ?, ?, ?)');
                $stmt->bind_param('iiiiiii', $league['id'], $sub_league['id'], $max_game_days, $playdown_teams[0], $playdown_teams[1], $playdown_teams[2], $playdown_teams[3]);
                $stmt->execute();
                $stmt->close();
                $playdown_id = mysqli_insert_id($con);

                // create playdown games
                $combinations = find_combinations($playdown_teams);
                $inverted_combinations = invert_combinations($combinations);

                $max_combinations = count($combinations);
                create_playdown_games($con, $combinations, $playdown_id, 0);
                create_playdown_games($con, $inverted_combinations, $playdown_id, $max_combinations);

                // create playdown teams
                foreach($playdown_teams as $team_id) {
                    $stmt = $con->prepare('INSERT INTO PlaydownTeam (playdown_id, team_id) VALUES (?, ?)');
                    $stmt->bind_param('ii', $playdown_id, $team_id);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }

        // now actually create playoff data
        foreach($leagues_to_create_playoff as $league){
            $teams = get_league_standing($con, $league['id']);

            $playoff_teams = array();
            $playoff_teams[] = $teams[0]['id'];
            $playoff_teams[] = $teams[1]['id'];
            $playoff_teams[] = $teams[2]['id'];
            $playoff_teams[] = $teams[3]['id'];
            $playoff_teams[] = $teams[4]['id'];
            $playoff_teams[] = $teams[5]['id'];
            $playoff_teams[] = $teams[6]['id'];
            $playoff_teams[] = $teams[7]['id'];

            // create playoff reference table
            $max_game_days = count($playoff_teams) - 1;
            $stmt = $con->prepare('INSERT INTO Playoff (league_id, last_round, last_game_day, team_id_1, team_id_2, team_id_3, team_id_4, team_id_5, team_id_6, team_id_7, team_id_8) VALUES (?, 0, 0, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('iiiiiiiii', $league['id'], $playoff_teams[0], $playoff_teams[1], $playoff_teams[2], $playoff_teams[3], $playoff_teams[4], $playoff_teams[5], $playoff_teams[6], $playoff_teams[7]);
            $stmt->execute();
            $stmt->close();
            $playoff_id = mysqli_insert_id($con);

            // create playoff games
            create_playoff_games($con, 0, $playoff_id, 1, $playoff_teams[0], $playoff_teams[7]);
            create_playoff_games($con, 0, $playoff_id, 1, $playoff_teams[1], $playoff_teams[6]);
            create_playoff_games($con, 0, $playoff_id, 1, $playoff_teams[2], $playoff_teams[5]);
            create_playoff_games($con, 0, $playoff_id, 1, $playoff_teams[3], $playoff_teams[4]);

            // create playoff teams
            foreach($playoff_teams as $team_id) {
                $stmt = $con->prepare('INSERT INTO PlayoffTeam (playoff_id, team_id) VALUES (?, ?)');
                $stmt->bind_param('ii', $playoff_id, $team_id);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    $stmt = $con->prepare('UPDATE State SET day = ?, week = ? WHERE id = 1');
    $stmt->bind_param('ii', $state['day'], $state['week']);
    $stmt->execute();
    $stmt->close();
}

function to_next_season($con, $goal_account_home, $goal_account_away, $goal_account_overtime, $goal_account_nhl_home, $goal_account_nhl_away, $goal_account_nhl_overtime)
{
    // only for germany -> up and down of teams
    $stmt = $con->prepare('SELECT * FROM Playdown');
    $stmt->execute();
    $result = $stmt->get_result();
    $playdowns = array();
    while($playdown = $result->fetch_array()) {
        $playdowns[] = $playdown;
    }
    $stmt->close();

    $league_id = 1; // 1, 2, 3 = germany
    foreach($playdowns as $playdown) {
        // get sorted list of teams
        $stmt = $con->prepare('SELECT p.*, (p.goals_shot - p.goals_received) as goals FROM PlaydownTeam p WHERE p.playdown_id = ? ORDER BY p.points DESC, goals DESC');
        $stmt->bind_param('i', $playdown['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $teams = array();
        while($team = $result->fetch_array()) {
            $teams[] = $team;
        }
        $stmt->close();

        //first two up, second two down
        for($i=0; $i < count($teams); ++$i) {
            if($i < 2) {
                $stmt = $con->prepare('UPDATE Team SET league_id = ? WHERE id = ?');
                $stmt->bind_param('ii', $league_id, $teams[$i]['team_id']);
                $stmt->execute();
                $stmt->close();
            } else {
                $next_league_id = $league_id + 1;
                $stmt = $con->prepare('UPDATE Team SET league_id = ? WHERE id = ?');
                $stmt->bind_param('ii', $next_league_id, $teams[$i]['team_id']);
                $stmt->execute();
                $stmt->close();
            }
        }

        $league_id++;
    }

    // initialize
    initialize_game($con, $goal_account_home, $goal_account_away, $goal_account_overtime, $goal_account_nhl_home, $goal_account_nhl_away, $goal_account_nhl_overtime);
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

function get_random_goals($max_array)
{
    $goals = array();

    // generate random goals
    for($i=0; $i<3; ++$i) {
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
    //special case for overtime
    $random_value = random_int(0,99);
    if($random_value >= 50)
    {
        $goals[] = 1;
    }
    else
    {
        $goals[] = 0;
    }

    // check against max values
    for($i = 0; $i < count($max_array); ++$i) {
        if($goals[$i] > $max_array[$i]) {
            $goals[$i] = $max_array[$i];
        }
    }

    return $goals;
}

// team ai that automatically sets goals
function team_ai($con)
{
    $leagues = get_all_leagues($con);

    foreach($leagues as $league) {
        $games = get_games_of_week($con, $league);
        foreach($games as $game) {
            if(is_ai_team($con, $game['home_team_id'])) {
                $team = get_team_by_id($con, $game['home_team_id']);

                $goals = get_random_goals(array($team['goal_account_home_1'], $team['goal_account_home_2'], $team['goal_account_home_3'], $team['goal_account_overtime']));
                
                $stmt = $con->prepare('UPDATE Game SET home_team_goal_1 = ?, home_team_goal_2 = ?, home_team_goal_3 = ?, home_team_goal_overtime = ? WHERE id = ?');
                $stmt->bind_param('iiiii', $goals[0], $goals[1], $goals[2], $goals[3], $game['id']);
                $stmt->execute();
                $stmt->close();

                $stmt = $con->prepare('UPDATE Team SET goal_account_home_1 = goal_account_home_1 - ?, goal_account_home_2 = goal_account_home_2 - ?, goal_account_home_3 = goal_account_home_3 - ?, goal_account_overtime = goal_account_overtime - ? WHERE id = ?');
                $stmt->bind_param('iiiii', $goals[0], $goals[1], $goals[2], $goals[3], $team['id']);
                $stmt->execute();
                $stmt->close();
            }

            if(is_ai_team($con, $game['away_team_id'])) {
                $team = get_team_by_id($con, $game['away_team_id']);

                $goals = get_random_goals(array($team['goal_account_away_1'], $team['goal_account_away_2'], $team['goal_account_away_3'], $team['goal_account_overtime']));

                $stmt = $con->prepare('UPDATE Game SET away_team_goal_1 = ?, away_team_goal_2 = ?, away_team_goal_3 = ?, away_team_goal_overtime = ? WHERE id = ?');
                $stmt->bind_param('iiiii', $goals[0], $goals[1], $goals[2], $goals[3], $game['id']);
                $stmt->execute();
                $stmt->close();

                $stmt = $con->prepare('UPDATE Team SET goal_account_away_1 = goal_account_away_1 - ?, goal_account_away_2 = goal_account_away_2 - ?, goal_account_away_3 = goal_account_away_3 - ?, goal_account_overtime = goal_account_overtime - ? WHERE id = ?');
                $stmt->bind_param('iiiii', $goals[0], $goals[1], $goals[2], $goals[3], $team['id']);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
}

function team_ai_playdown($con, $playdown)
{
    $game_day = $playdown['last_game_day'] + 1;
    $games = get_games_of_playdown_and_game_day($con, $playdown, $game_day);
    foreach($games as $game) {
        if(is_ai_team($con, $game['home_team_id'])) {
            $team = get_team_by_id($con, $game['home_team_id']);

            $goals = get_random_goals(array($team['goal_account_home_1'], $team['goal_account_home_2'], $team['goal_account_home_3'], $team['goal_account_overtime']));
            
            $stmt = $con->prepare('UPDATE PlaydownGame SET home_team_goal_1 = ?, home_team_goal_2 = ?, home_team_goal_3 = ?, home_team_goal_overtime = ? WHERE id = ?');
            $stmt->bind_param('iiiii', $goals[0], $goals[1], $goals[2], $goals[3], $game['id']);
            $stmt->execute();
            $stmt->close();

            $stmt = $con->prepare('UPDATE Team SET goal_account_home_1 = goal_account_home_1 - ?, goal_account_home_2 = goal_account_home_2 - ?, goal_account_home_3 = goal_account_home_3 - ?, goal_account_overtime = goal_account_overtime - ? WHERE id = ?');
            $stmt->bind_param('iiiii', $goals[0], $goals[1], $goals[2], $goals[3], $team['id']);
            $stmt->execute();
            $stmt->close();
        }
        if(is_ai_team($con, $game['away_team_id'])) {
            $team = get_team_by_id($con, $game['away_team_id']);

            $goals = get_random_goals(array($team['goal_account_away_1'], $team['goal_account_away_2'], $team['goal_account_away_3'], $team['goal_account_overtime']));

            $stmt = $con->prepare('UPDATE PlaydownGame SET away_team_goal_1 = ?, away_team_goal_2 = ?, away_team_goal_3 = ?, away_team_goal_overtime = ? WHERE id = ?');
            $stmt->bind_param('iiiii', $goals[0], $goals[1], $goals[2], $goals[3], $game['id']);
            $stmt->execute();
            $stmt->close();

            $stmt = $con->prepare('UPDATE Team SET goal_account_away_1 = goal_account_away_1 - ?, goal_account_away_2 = goal_account_away_2 - ?, goal_account_away_3 = goal_account_away_3 - ?, goal_account_overtime = goal_account_overtime - ? WHERE id = ?');
            $stmt->bind_param('iiiii', $goals[0], $goals[1], $goals[2], $goals[3], $team['id']);
            $stmt->execute();
            $stmt->close();
        }
    }
}

function team_ai_playoff($con, $playoff)
{
    $game_day = $playoff['last_game_day'] + 1;
    $round = $playoff['last_round'] + 1;
    $games = get_games_of_playoff_and_round_and_game_day($con, $playoff, $game_day, $round);
    foreach($games as $game) {
        if(is_ai_team($con, $game['home_team_id'])) {
            $team = get_team_by_id($con, $game['home_team_id']);

            $goals = get_random_goals(array($team['goal_account_home_1'], $team['goal_account_home_2'], $team['goal_account_home_3'], $team['goal_account_overtime']));
            
            $stmt = $con->prepare('UPDATE PlayoffGame SET home_team_goal_1 = ?, home_team_goal_2 = ?, home_team_goal_3 = ?, home_team_goal_overtime = ? WHERE id = ?');
            $stmt->bind_param('iiiii', $goals[0], $goals[1], $goals[2], $goals[3], $game['id']);
            $stmt->execute();
            $stmt->close();

            $stmt = $con->prepare('UPDATE Team SET goal_account_home_1 = goal_account_home_1 - ?, goal_account_home_2 = goal_account_home_2 - ?, goal_account_home_3 = goal_account_home_3 - ?, goal_account_overtime = goal_account_overtime - ? WHERE id = ?');
            $stmt->bind_param('iiiii', $goals[0], $goals[1], $goals[2], $goals[3], $team['id']);
            $stmt->execute();
            $stmt->close();
        }
        if(is_ai_team($con, $game['away_team_id'])) {
            $team = get_team_by_id($con, $game['away_team_id']);

            $goals = get_random_goals(array($team['goal_account_away_1'], $team['goal_account_away_2'], $team['goal_account_away_3'], $team['goal_account_overtime']));

            $stmt = $con->prepare('UPDATE PlayoffGame SET away_team_goal_1 = ?, away_team_goal_2 = ?, away_team_goal_3 = ?, away_team_goal_overtime = ? WHERE id = ?');
            $stmt->bind_param('iiiii', $goals[0], $goals[1], $goals[2], $goals[3], $game['id']);
            $stmt->execute();
            $stmt->close();

            $stmt = $con->prepare('UPDATE Team SET goal_account_away_1 = goal_account_away_1 - ?, goal_account_away_2 = goal_account_away_2 - ?, goal_account_away_3 = goal_account_away_3 - ?, goal_account_overtime = goal_account_overtime - ? WHERE id = ?');
            $stmt->bind_param('iiiii', $goals[0], $goals[1], $goals[2], $goals[3], $team['id']);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// update playdown team stats
function update_playdown_stats($con, $playdown_game_day, $playdown)
{
    $games = get_games_of_playdown_and_game_day($con, $playdown, $playdown_game_day);
    foreach($games as $game) {
        $stats = compute_stats_for_game($con, $game, 'PlaydownGame');

        $stmt = $con->prepare('UPDATE PlaydownTeam SET points = points + ?, win = win + ?, win_ot = win_ot + ?, win_pe = win_pe + ?, lose = lose + ?, lose_ot = lose_ot + ?, lose_pe= lose_pe + ?, goals_shot = goals_shot + ?, goals_received = goals_received + ? WHERE team_id = ?');
        $stmt->bind_param('iiiiiiiiii', $stats['home_points'], $stats['home_win'], $stats['home_win_ot'], $stats['home_win_pe'], $stats['home_lose'], $stats['home_lose_ot'], $stats['home_lose_pe'], $stats['goals_home'], $stats['goals_away'], $game['home_team_id']);
        $stmt->execute();
        $stmt->close();

        $stmt = $con->prepare('UPDATE PlaydownTeam SET points = points + ?, win = win + ?, win_ot = win_ot + ?, win_pe = win_pe + ?, lose = lose + ?, lose_ot = lose_ot + ?, lose_pe = lose_pe + ?, goals_shot = goals_shot + ?, goals_received = goals_received + ? WHERE team_id = ?');
        $stmt->bind_param('iiiiiiiiii', $stats['away_points'], $stats['away_win'], $stats['away_win_ot'], $stats['away_win_pe'], $stats['away_lose'], $stats['away_lose_ot'], $stats['away_lose_pe'], $stats['goals_away'], $stats['goals_home'], $game['away_team_id']);
        $stmt->execute();
        $stmt->close();
    }
}

// update playoff team stats
function update_playoff_stats($con, $playoff_game_day, $playoff_round, $playoff)
{
    $games = get_games_of_playoff_and_round_and_game_day($con, $playoff, $playoff_game_day, $playoff_round);
    foreach($games as $game) {
        $stats = compute_stats_for_game($con, $game, 'PlayoffGame');

        $stmt = $con->prepare('UPDATE PlayoffTeam SET win = win + ?, win_ot = win_ot + ?, win_pe = win_pe + ?, lose = lose + ?, lose_ot = lose_ot + ?, lose_pe = lose_pe + ?, goals_shot = goals_shot + ?, goals_received = goals_received + ? WHERE team_id = ?');
        $stmt->bind_param('iiiiiiiii', $stats['home_win'], $stats['home_win_ot'], $stats['home_win_pe'], $stats['home_lose'], $stats['home_lose_ot'], $stats['home_lose_pe'], $stats['goals_home'], $stats['goals_away'], $game['home_team_id']);
        $stmt->execute();
        $stmt->close();

        $stmt = $con->prepare('UPDATE PlayoffTeam SET win = win + ?, win_ot = win_ot + ?, win_pe = win_pe + ?, lose = lose + ?, lose_ot = lose_ot + ?, lose_pe = lose_pe + ?, goals_shot = goals_shot + ?, goals_received = goals_received + ? WHERE team_id = ?');
        $stmt->bind_param('iiiiiiiii', $stats['away_win'], $stats['away_win_ot'], $stats['away_win_pe'], $stats['away_lose'], $stats['away_lose_ot'], $stats['away_lose_pe'], $stats['goals_away'], $stats['goals_home'], $game['away_team_id']);
        $stmt->execute();
        $stmt->close();

        if($stats['home_win'] > 0) {
            $stmt = $con->prepare('UPDATE PlayoffGame SET home_win = 1 WHERE id = ?');
            $stmt->bind_param('i', $game['id']);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// get values from last week into team stats
function update_stats_of_league($con, $week, $league)
{
    $state = get_game_day($con);
    $games = get_games_of_week($con, $league);

    $leader = -1;   // id of leading team or -1
    if($state['win_leader'] == 1)
    {
        // set id of current league leader team
        $teams = get_team_by_points($con, $games[0]['home_team_id'], 0);
        $leader = $teams[0]['id'];
    }

    foreach($games as $game) {
        $stats = compute_stats_for_game($con, $game, 'Game');

        $stmt = $con->prepare('UPDATE Team SET points = points + ?, win = win + ?, win_ot = win_ot + ?, win_pe = win_pe + ?, lose = lose + ?, lose_ot = lose_ot + ?, lose_pe = lose_pe + ?, goals_shot = goals_shot + ?, goals_received = goals_received + ? WHERE id = ?');
        $stmt->bind_param('iiiiiiiiii', $stats['home_points'], $stats['home_win'], $stats['home_win_ot'], $stats['home_win_pe'], $stats['home_lose'], $stats['home_lose_ot'], $stats['home_lose_pe'], $stats['goals_home'], $stats['goals_away'], $game['home_team_id']);
        $stmt->execute();
        $stmt->close();

        $stmt = $con->prepare('UPDATE Team SET points = points + ?, win = win + ?, win_ot = win_ot + ?, win_pe = win_pe + ?, lose = lose + ?, lose_ot = lose_ot + ?, lose_pe = lose_pe + ?, goals_shot = goals_shot + ?, goals_received = goals_received + ? WHERE id = ?');
        $stmt->bind_param('iiiiiiiiii', $stats['away_points'], $stats['away_win'], $stats['away_win_ot'], $stats['away_win_pe'], $stats['away_lose'], $stats['away_lose_ot'], $stats['away_lose_pe'], $stats['goals_away'], $stats['goals_home'], $game['away_team_id']);
        $stmt->execute();
        $stmt->close();

        // adds two away goals if won five time in row
        if($state['win_five_times'] == 1)
        {
            if($stats['home_win'] == 1 || $stats['home_win_ot'] == 1 || $stats['home_win_pe'] == 1)
            {
                $team = get_team_by_id($con, $game['home_team_id']);

                if($team['win_counter'] == 4)
                {
                    $stmt = $con->prepare('UPDATE Team SET win_counter = 0, goal_account_bonus_away = goal_account_bonus_away + 2, goal_account_away_1 = goal_account_away_1 + 2 WHERE id = ?');
                    $stmt->bind_param('i', $team['id']);
                    $stmt->execute();
                    $stmt->close();
                }
                else
                {
                    $stmt = $con->prepare('UPDATE Team SET win_counter = win_counter + 1 WHERE id = ?');
                    $stmt->bind_param('i', $team['id']);
                    $stmt->execute();
                    $stmt->close();
                }

                $stmt = $con->prepare('UPDATE Team SET win_counter = 0 WHERE id = ?');
                $stmt->bind_param('i', $game['away_team_id']);
                $stmt->execute();
                $stmt->close();
            }
            if($stats['away_win'] == 1 || $stats['away_win_ot'] == 1 || $stats['away_win_pe'] == 1)
            {
                $team = get_team_by_id($con, $game['away_team_id']);

                if($team['win_counter'] == 4)
                {
                    $stmt = $con->prepare('UPDATE Team SET win_counter = 0, goal_account_bonus_away = goal_account_bonus_away + 2, goal_account_away_1 = goal_account_away_1 + 2 WHERE id = ?');
                    $stmt->bind_param('i', $team['id']);
                    $stmt->execute();
                    $stmt->close();
                }
                else
                {
                    $stmt = $con->prepare('UPDATE Team SET win_counter = win_counter + 1 WHERE id = ?');
                    $stmt->bind_param('i', $team['id']);
                    $stmt->execute();
                    $stmt->close();
                }

                $stmt = $con->prepare('UPDATE Team SET win_counter = 0 WHERE id = ?');
                $stmt->bind_param('i', $game['home_team_id']);
                $stmt->execute();
                $stmt->close();
            }
        }

        // adds a home goal if team won against leader
        if($state['win_leader'] == 1)
        {
            if(($stats['home_win'] == 1 || $stats['home_win_ot'] == 1 || $stats['home_win_pe'] == 1) && $game['away_team_id'] == $leader)
            {
                $stmt = $con->prepare('UPDATE Team SET goal_account_bonus_home = goal_account_bonus_home + 1, goal_account_home_1 = goal_account_home_1 + 1 WHERE id = ?');
                $stmt->bind_param('i', $game['home_team_id']);
                $stmt->execute();
                $stmt->close();
            }
            if(($stats['away_win'] == 1 || $stats['away_win_ot'] == 1 || $stats['away_win_pe'] == 1) && $game['home_team_id'] == $leader)
            {
                $stmt = $con->prepare('UPDATE Team SET goal_account_bonus_home = goal_account_bonus_home + 1, goal_account_home_1 = goal_account_home_1 + 1 WHERE id = ?');
                $stmt->bind_param('i', $game['away_team_id']);
                $stmt->execute();
                $stmt->close();
            }
        }

        // adds an away goal if team won with 5 or more goals difference
        if($state['win_five_goals'] == 1)
        {
            if($stats['home_win'] == 1)
            {
                if($stats['goals_home'] - $stats['goals_away'] >= 5)
                {
                    $stmt = $con->prepare('UPDATE Team SET goal_account_bonus_away = goal_account_bonus_away + 1, goal_account_away_1 = goal_account_away_1 + 1 WHERE id = ?');
                    $stmt->bind_param('i', $game['home_team_id']);
                    $stmt->execute();
                    $stmt->close();
                }
            }
            if($stats['away_win'] == 1)
            {
                if($stats['goals_away'] - $stats['goals_home'] >= 5)
                {
                    $stmt = $con->prepare('UPDATE Team SET goal_account_bonus_away = goal_account_bonus_away + 1, goal_account_away_1 = goal_account_away_1 + 1 WHERE id = ?');
                    $stmt->bind_param('i', $game['away_team_id']);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
    }
}

function compute_stats_for_game($con, $game, $tableName)
{
    // home team
    $goals_home = $game['home_team_goal_1'] + $game['home_team_goal_2'] + $game['home_team_goal_3'];
    $overtime_home = $game['home_team_goal_overtime'];
    // away team
    $goals_away = $game['away_team_goal_1'] + $game['away_team_goal_2'] + $game['away_team_goal_3'];
    $overtime_away = $game['away_team_goal_overtime'];

    $home_win = $goals_home > $goals_away ? 1 : 0;
    $home_win_ot = 0;
    $home_win_pe = 0;
    $away_win = $goals_away > $goals_home ? 1 : 0;
    $away_win_ot = 0;
    $away_win_pe = 0;
    $home_lose = $goals_home < $goals_away ? 1 : 0;
    $home_lose_ot = 0;
    $home_lose_pe = 0;
    $away_lose = $goals_away < $goals_home ? 1 : 0;
    $away_lose_ot = 0;
    $away_lose_pe = 0;
    $draw = 0;

    if($home_win == 1 && $away_lose == 1) {
        $home_points = 3;
        $away_points = 0;
    }
    else if($away_win == 1 && $home_lose == 1) {
        $home_points = 0;
        $away_points = 3;
    } else {
        $draw = 1;
    }

    // if it is draw after 3 periods -> use overtime goals
    if($draw == 1)
    {
        $home_win_ot = $overtime_home > $overtime_away ? 1 : 0;
        $away_win_ot = $overtime_away > $overtime_home ? 1 : 0;
        $home_lose_ot = $overtime_home < $overtime_away ? 1 : 0;
        $away_lose_ot = $overtime_away < $overtime_home ? 1 : 0;
        $draw = 0;

        if($home_win_ot == 1 && $away_lose_ot == 1) {
            $home_points = 2;
            $away_points = 1;
        }
        else if($away_win_ot == 1 && $home_lose_ot == 1) {
            $home_points = 1;
            $away_points = 2;
        } else {
            $draw = 1;
        }

        // if it is draw after overtime -> random winner
        if($draw == 1)
        {
            // TODO add shootout here
            if(50 > random_int(0, 99))
            {
                $home_win_pe = 1;
                $home_lose_pe = 0;
                $away_win_pe = 0;
                $away_lose_pe = 1;

                $home_points = 2;
                $away_points = 1;

                $stmt = $con->prepare('UPDATE '.$tableName.' SET home_team_penalty_win = 1 WHERE id = ?');
                $stmt->bind_param('i', $game['id']);
                $stmt->execute();
                $stmt->close();
            }
            else
            {
                $home_win_pe = 0;
                $home_lose_pe = 1;
                $away_win_pe = 1;
                $away_lose_pe = 0;

                $home_points = 1;
                $away_points = 2;

                $stmt = $con->prepare('UPDATE '.$tableName.' SET away_team_penalty_win = 1 WHERE id = ?');
                $stmt->bind_param('i', $game['id']);
                $stmt->execute();
                $stmt->close();
            }

            $draw = 0;
        }
    }

    $return = array();
    $return['home_points'] = $home_points;
    $return['home_win'] = $home_win;
    $return['home_win_ot'] = $home_win_ot;
    $return['home_win_pe'] = $home_win_pe;
    $return['home_lose'] = $home_lose;
    $return['home_lose_ot'] = $home_lose_ot;
    $return['home_lose_pe'] = $home_lose_pe;
    $return['goals_home'] = $goals_home;
    $return['away_points'] = $away_points;
    $return['away_win'] = $away_win;
    $return['away_win_ot'] = $away_win_ot;
    $return['away_win_pe'] = $away_win_pe;
    $return['away_lose'] = $away_lose;
    $return['away_lose_ot'] = $away_lose_ot;
    $return['away_lose_pe'] = $away_lose_pe;
    $return['goals_away'] = $goals_away;

    return $return;
}

function reset_state($con)
{
    $stmt = $con->prepare('DELETE FROM State');
    $stmt->execute();
    $stmt->close();

    $stmt = $con->prepare('TRUNCATE TABLE State');
    $stmt->execute();
    $stmt->close();

    $stmt = $con->prepare('INSERT INTO State (day, week, season_over, win_leader, win_five_times, win_five_goals) VALUES (0, 0, 0, 1, 1, 1)');
    $stmt->execute();
    $stmt->close();
}

function reset_playdown($con)
{
    $stmt = $con->prepare('TRUNCATE TABLE Playdown');
    $stmt->execute();
    $stmt->close();

    $stmt = $con->prepare('TRUNCATE TABLE PlaydownGame');
    $stmt->execute();
    $stmt->close();

    $stmt = $con->prepare('TRUNCATE TABLE PlaydownTeam');
    $stmt->execute();
    $stmt->close();
}

function reset_playoff($con)
{
    $stmt = $con->prepare('TRUNCATE TABLE Playoff');
    $stmt->execute();
    $stmt->close();

    $stmt = $con->prepare('TRUNCATE TABLE PlayoffGame');
    $stmt->execute();
    $stmt->close();

    $stmt = $con->prepare('TRUNCATE TABLE PlayoffTeam');
    $stmt->execute();
    $stmt->close();
}

function reset_league($con, $goal_account_home, $goal_account_away, $goal_account_overtime, $goal_account_nhl_home, $goal_account_nhl_away, $goal_account_nhl_overtime)
{
    // reset teams
    $stmt = $con->prepare('UPDATE Team SET points = 0, goals_shot = 0, goals_received = 0, win = 0, win_ot = 0, win_pe = 0, lose = 0, lose_ot = 0, lose_pe = 0, goal_account_bonus_home = 0, goal_account_bonus_away = 0, win_counter = 0, goal_account_home_1 = ?, goal_account_home_2 = ?, goal_account_home_3 = ?, goal_account_away_1 = ?, goal_account_away_2 = ?, goal_account_away_3 = ?, goal_account_overtime = ?');
	$stmt->bind_param('iiiiiii', $goal_account_home, $goal_account_home, $goal_account_home, $goal_account_away, $goal_account_away, $goal_account_away, $goal_account_overtime);
	$stmt->execute();
    $stmt->close();

    $stmt = $con->prepare('UPDATE Team SET points = 0, goals_shot = 0, goals_received = 0, win = 0, win_ot = 0, win_pe = 0, lose = 0, lose_ot = 0, lose_pe = 0, goal_account_bonus_home = 0, goal_account_bonus_away = 0, win_counter = 0, goal_account_home_1 = ?, goal_account_home_2 = ?, goal_account_home_3 = ?, goal_account_away_1 = ?, goal_account_away_2 = ?, goal_account_away_3 = ?, goal_account_overtime = ? WHERE league_id = 9');
	$stmt->bind_param('iiiiiii', $goal_account_nhl_home, $goal_account_nhl_home, $goal_account_nhl_home, $goal_account_nhl_away, $goal_account_nhl_away, $goal_account_nhl_away, $goal_account_nhl_overtime);
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
}

function create_calendar($con)
{
    // 1. get all leagues
    $stmt = $con->prepare('SELECT * FROM League');
    $stmt->execute();
    $result = $stmt->get_result();
    while($league = $result->fetch_array()) {
        $leagues[] = $league['id'];
    }
    $stmt->close();

    // 2. get teams of league
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

        // set max game days
        $league_array['id'] = $league;
        $games = get_games_by_league($con, $league_array);
        $number_of_games = count($games);
        $stmt = $con->prepare('UPDATE League SET max_game_days = ? WHERE id = ?');
        $stmt->bind_param('ii', $number_of_games, $league);
        $stmt->execute();
        $stmt->close();
    }
}

// initialize a new geam -> reset all values
function initialize_game($con, $goal_account_home, $goal_account_away, $goal_account_overtime, $goal_account_nhl_home, $goal_account_nhl_away, $goal_account_nhl_overtime)
{
    // reset state
    reset_state($con);

    // reset league
    reset_league($con, $goal_account_home, $goal_account_away, $goal_account_overtime, $goal_account_nhl_home, $goal_account_nhl_away, $goal_account_nhl_overtime);

    // reset playdowns
    reset_playdown($con);

    // reset playoffs
    reset_playoff($con);

    // create calendar
    create_calendar($con);
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

function create_playdown_games($con, $combinations, $playdown_id, $last_game_day)
{
    foreach($combinations as $combination)
    {
        $last_game_day++;
        foreach($combination as $game)
        {
            $stmt = $con->prepare('INSERT INTO PlaydownGame (game_day, playdown_id, home_team_id, away_team_id) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('iiii', $last_game_day, $playdown_id, $game[0], $game[1]);
            $stmt->execute();
            $stmt->close();
        }
    }
}

function create_playoff_games($con, $last_game_day, $playoff_id, $round, $team_id_1, $team_id_2)
{
    for($i = 0; $i < 7; ++$i) {
        $last_game_day++;
        $stmt = $con->prepare('INSERT INTO PlayoffGame (game_day, playoff_id, home_team_id, away_team_id, round) VALUES (?, ?, ?, ?, ?)');
        if($i % 2 == 0) {
            $stmt->bind_param('iiiii', $last_game_day, $playoff_id, $team_id_1, $team_id_2, $round);
        } else {
            $stmt->bind_param('iiiii', $last_game_day, $playoff_id, $team_id_2, $team_id_1, $round);
        }
        $stmt->execute();
        $stmt->close();
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

function display_game_result($game)
{
    $home = $game['home_team_goal_1'] + $game['home_team_goal_2'] + $game['home_team_goal_3'];
    $away = $game['away_team_goal_1'] + $game['away_team_goal_2'] + $game['away_team_goal_3'];
    if($home == $away) {
        $home = $game['home_team_goal_1'] + $game['home_team_goal_2'] + $game['home_team_goal_3'] + $game['home_team_goal_overtime'];
        $away = $game['away_team_goal_1'] + $game['away_team_goal_2'] + $game['away_team_goal_3'] + $game['away_team_goal_overtime'];
        if($home == $away) {
            if($game['home_win'] == 1) {
                return ($home+1).'*:'.$away;
            } else {
                return $home.':'.($away+1).'*';
            }
        } else {
            return $home.':'.$away;
        }
    } else {
        return $home.':'.$away;
    }
}

// chat  functions
function add_message($con, $user_id, $message)
{
    $stmt = $con->prepare('INSERT INTO Chat (timestamp, user_id, message) VALUES (now(), ?, ?)');
    $stmt->bind_param('is', $user_id, $message);
    $stmt->execute();
    $stmt->close();
}

function get_messages($con, $max_count)
{
    $stmt = $con->prepare('SELECT c.*, u.username FROM Chat c JOIN User u ON u.id = c.user_id ORDER BY c.id DESC LIMIT ?');
    $stmt->bind_param('i', $max_count);
    $stmt->execute();
    $result = $stmt->get_result();
    $messages = array();
    while($message = $result->fetch_array()) {
        $messages[] = $message;
    }
    $messages = array_reverse($messages);
    $stmt->close();

    return $messages;
}
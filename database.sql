-- phpMyAdmin SQL Dump
-- version 4.9.11
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Erstellungszeit: 03. Jun 2024 um 18:29
-- Server-Version: 10.6.16-MariaDB-0ubuntu0.22.04.1-log
-- PHP-Version: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `d03f724a`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `Chat`
--

CREATE TABLE `Chat` (
  `id` int(11) NOT NULL,
  `timestamp` datetime NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `Country`
--

CREATE TABLE `Country` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `Game`
--

CREATE TABLE `Game` (
  `id` int(11) NOT NULL,
  `game_day` int(11) NOT NULL,
  `home_team_id` int(11) NOT NULL,
  `away_team_id` int(11) NOT NULL,
  `home_team_goal_1` int(11) NOT NULL,
  `home_team_goal_2` int(11) NOT NULL,
  `home_team_goal_3` int(11) NOT NULL,
  `home_team_goal_overtime` int(11) NOT NULL,
  `away_team_goal_1` int(11) NOT NULL,
  `away_team_goal_2` int(11) NOT NULL,
  `away_team_goal_3` int(11) NOT NULL,
  `away_team_goal_overtime` int(11) NOT NULL,
  `home_team_penalty_win` int(11) NOT NULL,
  `away_team_penalty_win` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `League`
--

CREATE TABLE `League` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `division` int(11) NOT NULL,
  `order_number` int(11) NOT NULL,
  `country_id` int(11) NOT NULL,
  `max_game_days` int(11) NOT NULL,
  `last_game_day` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `Playdown`
--

CREATE TABLE `Playdown` (
  `id` int(11) NOT NULL,
  `league_id_up` int(11) NOT NULL,
  `league_id_down` int(11) NOT NULL,
  `max_game_days` int(11) NOT NULL,
  `last_game_day` int(11) NOT NULL,
  `team_id_1` int(11) NOT NULL,
  `team_id_2` int(11) NOT NULL,
  `team_id_3` int(11) NOT NULL,
  `team_id_4` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `PlaydownGame`
--

CREATE TABLE `PlaydownGame` (
  `id` int(11) NOT NULL,
  `game_day` int(11) NOT NULL,
  `playdown_id` int(11) NOT NULL,
  `home_team_id` int(11) NOT NULL,
  `away_team_id` int(11) NOT NULL,
  `home_team_goal_1` int(11) NOT NULL,
  `home_team_goal_2` int(11) NOT NULL,
  `home_team_goal_3` int(11) NOT NULL,
  `home_team_goal_overtime` int(11) NOT NULL,
  `away_team_goal_1` int(11) NOT NULL,
  `away_team_goal_2` int(11) NOT NULL,
  `away_team_goal_3` int(11) NOT NULL,
  `away_team_goal_overtime` int(11) NOT NULL,
  `home_team_penalty_win` int(11) NOT NULL,
  `away_team_penalty_win` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `PlaydownTeam`
--

CREATE TABLE `PlaydownTeam` (
  `id` int(11) NOT NULL,
  `playdown_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `points` int(11) NOT NULL,
  `goals_shot` int(11) NOT NULL,
  `goals_received` int(11) NOT NULL,
  `win` int(11) NOT NULL,
  `win_ot` int(11) NOT NULL,
  `win_pe` int(11) NOT NULL,
  `lose` int(11) NOT NULL,
  `lose_ot` int(11) NOT NULL,
  `lose_pe` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `Playoff`
--

CREATE TABLE `Playoff` (
  `id` int(11) NOT NULL,
  `league_id` int(11) NOT NULL,
  `last_round` int(11) NOT NULL,
  `last_game_day` int(11) NOT NULL,
  `team_id_1` int(11) NOT NULL,
  `team_id_2` int(11) NOT NULL,
  `team_id_3` int(11) NOT NULL,
  `team_id_4` int(11) NOT NULL,
  `team_id_5` int(11) NOT NULL,
  `team_id_6` int(11) NOT NULL,
  `team_id_7` int(11) NOT NULL,
  `team_id_8` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `PlayoffGame`
--

CREATE TABLE `PlayoffGame` (
  `id` int(11) NOT NULL,
  `round` int(11) NOT NULL,
  `game_day` int(11) NOT NULL,
  `playoff_id` int(11) NOT NULL,
  `home_team_id` int(11) NOT NULL,
  `away_team_id` int(11) NOT NULL,
  `home_team_goal_1` int(11) NOT NULL,
  `home_team_goal_2` int(11) NOT NULL,
  `home_team_goal_3` int(11) NOT NULL,
  `home_team_goal_overtime` int(11) NOT NULL,
  `away_team_goal_1` int(11) NOT NULL,
  `away_team_goal_2` int(11) NOT NULL,
  `away_team_goal_3` int(11) NOT NULL,
  `away_team_goal_overtime` int(11) NOT NULL,
  `home_team_penalty_win` int(11) NOT NULL,
  `away_team_penalty_win` int(11) NOT NULL,
  `home_win` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `PlayoffTeam`
--

CREATE TABLE `PlayoffTeam` (
  `id` int(11) NOT NULL,
  `playoff_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `win` int(11) NOT NULL,
  `win_ot` int(11) NOT NULL,
  `win_pe` int(11) NOT NULL,
  `lose` int(11) NOT NULL,
  `lose_ot` int(11) NOT NULL,
  `lose_pe` int(11) NOT NULL,
  `goals_shot` int(11) NOT NULL,
  `goals_received` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `State`
--

CREATE TABLE `State` (
  `id` int(11) NOT NULL,
  `week` int(11) NOT NULL DEFAULT 0,
  `day` int(11) NOT NULL DEFAULT 0,
  `season_over` int(11) NOT NULL,
  `win_leader` tinyint(1) NOT NULL,
  `win_five_times` tinyint(1) NOT NULL,
  `win_five_goals` tinyint(1) NOT NULL,
  `message` text NOT NULL,
  `admin_mail` varchar(255) NOT NULL,
  `chat_message_count` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `Team`
--

CREATE TABLE `Team` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `league_id` int(11) NOT NULL,
  `points` int(11) NOT NULL,
  `goals_shot` int(11) NOT NULL,
  `goals_received` int(11) NOT NULL,
  `win` int(11) NOT NULL,
  `win_ot` int(11) NOT NULL,
  `win_pe` int(11) NOT NULL,
  `lose` int(11) NOT NULL,
  `lose_ot` int(11) NOT NULL,
  `lose_pe` int(11) NOT NULL,
  `goal_account_home_1` int(11) NOT NULL,
  `goal_account_home_2` int(11) NOT NULL,
  `goal_account_home_3` int(11) NOT NULL,
  `goal_account_away_1` int(11) NOT NULL,
  `goal_account_away_2` int(11) NOT NULL,
  `goal_account_away_3` int(11) NOT NULL,
  `goal_account_overtime` int(11) NOT NULL,
  `goal_account_bonus_home` int(11) NOT NULL,
  `goal_account_bonus_away` int(11) NOT NULL,
  `win_counter` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `User`
--

CREATE TABLE `User` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `activation_code` varchar(50) NOT NULL,
  `admin` tinyint(1) NOT NULL DEFAULT 0,
  `team_id` int(11) NOT NULL,
  `dream_team_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `Chat`
--
ALTER TABLE `Chat`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `Country`
--
ALTER TABLE `Country`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `Game`
--
ALTER TABLE `Game`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `League`
--
ALTER TABLE `League`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `Playdown`
--
ALTER TABLE `Playdown`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `PlaydownGame`
--
ALTER TABLE `PlaydownGame`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `PlaydownTeam`
--
ALTER TABLE `PlaydownTeam`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `Playoff`
--
ALTER TABLE `Playoff`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `PlayoffGame`
--
ALTER TABLE `PlayoffGame`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `PlayoffTeam`
--
ALTER TABLE `PlayoffTeam`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `State`
--
ALTER TABLE `State`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `Team`
--
ALTER TABLE `Team`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `User`
--
ALTER TABLE `User`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `Chat`
--
ALTER TABLE `Chat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `Country`
--
ALTER TABLE `Country`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `Game`
--
ALTER TABLE `Game`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `League`
--
ALTER TABLE `League`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `Playdown`
--
ALTER TABLE `Playdown`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `PlaydownGame`
--
ALTER TABLE `PlaydownGame`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `PlaydownTeam`
--
ALTER TABLE `PlaydownTeam`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `Playoff`
--
ALTER TABLE `Playoff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `PlayoffGame`
--
ALTER TABLE `PlayoffGame`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `PlayoffTeam`
--
ALTER TABLE `PlayoffTeam`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `State`
--
ALTER TABLE `State`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `Team`
--
ALTER TABLE `Team`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `User`
--
ALTER TABLE `User`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- phpMyAdmin SQL Dump
-- version 5.1.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Erstellungszeit: 10. Jun 2021 um 08:42
-- Server-Version: 10.4.18-MariaDB
-- PHP-Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `autobackup`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `automatedbackup_cron`
--

CREATE TABLE `automatedbackup_cron` (
  `id` int(11) NOT NULL,
  `laststart` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  `lastend` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  `status` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Daten für Tabelle `automatedbackup_cron`
--

INSERT INTO `automatedbackup_cron` (`id`, `laststart`, `lastend`, `status`) VALUES
(1, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `automatedbackup_logs`
--

CREATE TABLE `automatedbackup_logs` (
  `id` int(11) UNSIGNED NOT NULL,
  `createdtime` datetime DEFAULT NULL,
  `filename` varchar(255) CHARACTER SET latin1 NOT NULL,
  `filetype` varchar(255) CHARACTER SET latin1 NOT NULL,
  `filesize` varchar(255) CHARACTER SET latin1 NOT NULL,
  `path` varchar(255) CHARACTER SET latin1 NOT NULL,
  `deleted` int(1) NOT NULL,
  `type` varchar(255) CHARACTER SET latin1 NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `automatedbackup_settings`
--

CREATE TABLE `automatedbackup_settings` (
  `id` int(11) NOT NULL,
  `key` varchar(255) CHARACTER SET latin1 NOT NULL,
  `value` text CHARACTER SET utf8 COLLATE utf8_estonian_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `automatedbackup_settings`
--

INSERT INTO `automatedbackup_settings` (`id`, `key`, `value`) VALUES
(1, 'localbackup_status', 'Active'),
(2, 'localbackup_database', 'on'),
(3, 'localbackup_files', ''),
(4, 'localbackup_frequency', '1'),
(5, 'localbackup_number', '6'),
(6, 'localbackup_directory', ''),
(7, 'emailreport_status', 'Active'),
(8, 'emailreport_email', ''),
(9, 'emailreport_backuptype', 'localbackup|##|ftpbackup'),
(10, 'emailreport_subject', 'Database Backup for (%s) Created'),
(11, 'emailreport_body', 'Database Backup (%s) has been created successfully - %s                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                '),
(12, 'frequency_unit', 'days'),
(13, 'specific_time', '02:00'),
(14, 'next_triger_time', ''),
(15, 'emailreport_login_email', ''),
(16, 'emailreport_login_subject', 'User has logged in at %s'),
(17, 'emailreport_login_body', ' User <strong> %s </strong>  has logged in at %s'),
(18, 'autobackup_unique_key', 'WsYCKmVMbBfwGPjAqaBm9pwyej9Znd5b');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `automatedbackup_users`
--

CREATE TABLE `automatedbackup_users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_admin` tinyint(1) NOT NULL,
  `user_access_key` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `automatedbackup_users`
--

INSERT INTO `automatedbackup_users` (`id`, `username`, `password`, `is_admin`, `user_access_key`) VALUES
(1, 'super_admin', '$2y$10$pwkuCl35IP6ZzC62wpOxlOHw07kHJyV2OKELiO5wCtu/cxL9LnW1G', 1, '8x/A?D*G-KaPdSgVkYp3s6v9y$B&E)H+MbQeThWmZq4t7w!z%C*F-JaNcRfUjXn2'),
(3, 'admin', '$2y$10$pwkuCl35IP6ZzC62wpOxlOHw07kHJyV2OKELiO5wCtu/cxL9LnW1G', 0, 'WmZq4t7w!z%C&F)J@NcRfUjXn2r5u8x/A?D(G-KaPdSgVkYp3s6v9y$B&E)H@MbQ');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `automatedbackup_users_logs`
--

CREATE TABLE `automatedbackup_users_logs` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `ip` varchar(100) NOT NULL,
  `signed_in` datetime NOT NULL,
  `signed_out` datetime NOT NULL,
  `status` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `automatedbackup_cron`
--
ALTER TABLE `automatedbackup_cron`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `automatedbackup_logs`
--
ALTER TABLE `automatedbackup_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `automatedbackup_settings`
--
ALTER TABLE `automatedbackup_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `automatedbackup_users`
--
ALTER TABLE `automatedbackup_users`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `automatedbackup_users_logs`
--
ALTER TABLE `automatedbackup_users_logs`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `automatedbackup_cron`
--
ALTER TABLE `automatedbackup_cron`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT für Tabelle `automatedbackup_logs`
--
ALTER TABLE `automatedbackup_logs`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `automatedbackup_settings`
--
ALTER TABLE `automatedbackup_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT für Tabelle `automatedbackup_users`
--
ALTER TABLE `automatedbackup_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT für Tabelle `automatedbackup_users_logs`
--
ALTER TABLE `automatedbackup_users_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

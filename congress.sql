-- phpMyAdmin SQL Dump
-- version 4.6.4
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 22, 2017 at 08:23 PM
-- Server version: 5.7.14
-- PHP Version: 5.6.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `congress`
--

-- --------------------------------------------------------

--
-- Table structure for table `house_ sponsors`
--

CREATE TABLE `house_ sponsors` (
  `congress` int(11) NOT NULL,
  `bill` int(11) NOT NULL,
  `bioguide_id` int(6) UNSIGNED ZEROFILL NOT NULL,
  `bioguide_prefix` tinyint(4) NOT NULL,
  `type` tinyint(4) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `house_subjects`
--

CREATE TABLE `house_subjects` (
  `congress` int(11) NOT NULL,
  `bill` int(11) NOT NULL,
  `subject` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `house_votes`
--

CREATE TABLE `house_votes` (
  `congress` int(11) NOT NULL,
  `session` int(11) NOT NULL,
  `vote_id` int(11) NOT NULL,
  `category` varchar(255) NOT NULL,
  `requires` tinytext NOT NULL,
  `question` text,
  `bill` int(11) DEFAULT NULL,
  `sponsor` int(11) DEFAULT NULL,
  `subject` int(11) DEFAULT NULL,
  `date` varchar(255) NOT NULL,
  `result` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `house_vote_values`
--

CREATE TABLE `house_vote_values` (
  `congress` int(11) NOT NULL,
  `session` int(11) NOT NULL,
  `vote_id` int(11) NOT NULL,
  `bioguide_id` int(6) UNSIGNED ZEROFILL NOT NULL,
  `bioguide_prefix` tinyint(4) NOT NULL,
  `value` tinyint(4) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `representatives`
--

CREATE TABLE `representatives` (
  `bioguide_id` int(6) UNSIGNED ZEROFILL NOT NULL,
  `bioguide_prefix` tinyint(4) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `took_office` int(11) NOT NULL,
  `birthday` date NOT NULL,
  `gender` tinytext NOT NULL,
  `state` tinytext NOT NULL,
  `district` tinyint(4) NOT NULL,
  `party` tinytext NOT NULL,
  `govtrack_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `senate_sponsors`
--

CREATE TABLE `senate_sponsors` (
  `congress` int(11) NOT NULL,
  `bill` int(11) NOT NULL,
  `bioguide_id` int(6) UNSIGNED ZEROFILL NOT NULL,
  `bioguide_prefix` tinyint(4) NOT NULL,
  `type` tinyint(4) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `senate_subjects`
--

CREATE TABLE `senate_subjects` (
  `congress` int(11) NOT NULL,
  `bill` int(11) NOT NULL,
  `subject` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `senate_votes`
--

CREATE TABLE `senate_votes` (
  `congress` int(11) NOT NULL,
  `session` int(11) NOT NULL,
  `vote_id` int(11) NOT NULL,
  `category` varchar(255) NOT NULL,
  `requires` tinytext NOT NULL,
  `title` text,
  `bill` int(11) DEFAULT NULL,
  `sponsor` int(11) DEFAULT NULL,
  `subject` int(11) DEFAULT NULL,
  `date` varchar(255) NOT NULL,
  `result` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `senate_vote_values`
--

CREATE TABLE `senate_vote_values` (
  `congress` int(11) NOT NULL,
  `session` int(11) NOT NULL,
  `vote_id` int(11) NOT NULL,
  `senator` int(11) NOT NULL,
  `value` tinyint(4) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `senators`
--

CREATE TABLE `senators` (
  `bioguide_id` int(6) UNSIGNED ZEROFILL NOT NULL,
  `bioguide_prefix` tinyint(4) NOT NULL,
  `voter_id` int(11) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `took_office` int(11) NOT NULL,
  `birthday` date NOT NULL,
  `gender` tinytext NOT NULL,
  `state` tinytext NOT NULL,
  `party` tinytext NOT NULL,
  `govtrack_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `subject_keys`
--

CREATE TABLE `subject_keys` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `house_ sponsors`
--
ALTER TABLE `house_ sponsors`
  ADD PRIMARY KEY (`congress`,`bill`,`bioguide_id`,`bioguide_prefix`);

--
-- Indexes for table `house_subjects`
--
ALTER TABLE `house_subjects`
  ADD PRIMARY KEY (`congress`,`bill`,`subject`);

--
-- Indexes for table `house_votes`
--
ALTER TABLE `house_votes`
  ADD PRIMARY KEY (`congress`,`session`,`vote_id`);

--
-- Indexes for table `house_vote_values`
--
ALTER TABLE `house_vote_values`
  ADD PRIMARY KEY (`congress`,`session`,`vote_id`,`bioguide_id`,`bioguide_prefix`),
  ADD KEY `bioguide_id` (`bioguide_id`),
  ADD KEY `bioguide_prefix` (`bioguide_prefix`),
  ADD KEY `congress` (`congress`);

--
-- Indexes for table `representatives`
--
ALTER TABLE `representatives`
  ADD PRIMARY KEY (`bioguide_id`,`bioguide_prefix`),
  ADD KEY `bioguide_id` (`bioguide_id`),
  ADD KEY `bioguide_prefix` (`bioguide_prefix`);

--
-- Indexes for table `senate_sponsors`
--
ALTER TABLE `senate_sponsors`
  ADD PRIMARY KEY (`congress`,`bill`,`bioguide_id`,`bioguide_prefix`);

--
-- Indexes for table `senate_subjects`
--
ALTER TABLE `senate_subjects`
  ADD PRIMARY KEY (`congress`,`bill`,`subject`);

--
-- Indexes for table `senate_votes`
--
ALTER TABLE `senate_votes`
  ADD PRIMARY KEY (`congress`,`session`,`vote_id`);

--
-- Indexes for table `senate_vote_values`
--
ALTER TABLE `senate_vote_values`
  ADD PRIMARY KEY (`congress`,`session`,`vote_id`,`senator`),
  ADD KEY `senator` (`senator`),
  ADD KEY `congress` (`congress`);

--
-- Indexes for table `senators`
--
ALTER TABLE `senators`
  ADD PRIMARY KEY (`bioguide_id`,`bioguide_prefix`);

--
-- Indexes for table `subject_keys`
--
ALTER TABLE `subject_keys`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `subject_keys`
--
ALTER TABLE `subject_keys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- phpMyAdmin SQL Dump
-- version 3.3.2deb1ubuntu1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Sep 03, 2014 at 01:07 PM
-- Server version: 5.1.73
-- PHP Version: 5.3.2-1ubuntu4.22

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `report-card`
--

-- --------------------------------------------------------

--
-- Table structure for table `cards`
--

CREATE TABLE IF NOT EXISTS `cards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(100) COLLATE utf8_bin NOT NULL,
  `group_slug` varchar(100) COLLATE utf8_bin NOT NULL,
  `title` varchar(250) COLLATE utf8_bin NOT NULL,
  `description` text COLLATE utf8_bin NOT NULL,
  `date_expire` date DEFAULT NULL,
  `grade_average` float NOT NULL,
  `grades` int(11) NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;

--
-- Dumping data for table `cards`
--


-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

CREATE TABLE IF NOT EXISTS `grades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cards_id` int(11) NOT NULL,
  `grade` tinyint(4) NOT NULL,
  `ip` varchar(30) COLLATE utf8_bin NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `cards_id` (`cards_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;

--
-- Dumping data for table `grades`
--

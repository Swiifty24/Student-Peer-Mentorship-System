-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 16, 2025 at 12:04 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tutor`
--

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `courseID` int(11) NOT NULL,
  `courseName` varchar(255) NOT NULL,
  `subjectArea` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`courseID`, `courseName`, `subjectArea`, `description`) VALUES
(1, 'Calculus I', 'Mathematics', NULL),
(2, 'Differential Equations', 'Mathematics', NULL),
(3, 'Introduction to Python', 'Computer Science', NULL),
(4, 'Data Structures', 'Computer Science', NULL),
(5, 'General Chemistry', 'Science', NULL),
(6, 'World History to 1500', 'Humanities', NULL),
(7, 'Liberal Arts', 'Debating', NULL),
(8, 'Liberal Arts', 'Debating 2', NULL),
(9, 'Liberal Arts', 'Legal Fallacies 1', NULL),
(10, 'Liberal Arts', 'Social science 1', NULL),
(11, 'Gaming', 'Mobile Legends', NULL),
(12, 'Liberal Arts', 'gaming', NULL),
(13, 'Web Development', 'WD121', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `enrollmentID` int(11) NOT NULL,
  `studentID` int(11) NOT NULL,
  `tutorUserID` int(11) NOT NULL,
  `courseID` int(11) NOT NULL,
  `status` enum('pending','confirmed','declined','completed','cancelled') DEFAULT 'pending',
  `sessionDetails` text DEFAULT NULL,
  `requestDate` datetime NOT NULL,
  `sessionDate` date DEFAULT NULL,
  `sessionTime` time DEFAULT NULL,
  `studentNote` text DEFAULT NULL,
  `tutorNote` text DEFAULT NULL,
  `requestedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `respondedAt` timestamp NULL DEFAULT NULL,
  `completedAt` timestamp NULL DEFAULT NULL,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `updatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`enrollmentID`, `studentID`, `tutorUserID`, `courseID`, `status`, `sessionDetails`, `requestDate`, `sessionDate`, `sessionTime`, `studentNote`, `tutorNote`, `requestedAt`, `respondedAt`, `completedAt`, `createdAt`, `updatedAt`) VALUES
(1, 2, 1, 3, 'declined', 'asdasd', '2025-10-30 03:45:08', NULL, NULL, NULL, NULL, '2025-12-15 23:22:53', NULL, NULL, '2025-12-15 23:22:53', '2025-12-15 23:22:53'),
(2, 1, 2, 4, 'pending', 'i want to learn', '2025-10-30 11:39:48', NULL, NULL, NULL, NULL, '2025-12-15 23:22:53', NULL, NULL, '2025-12-15 23:22:53', '2025-12-15 23:22:53'),
(3, 7, 1, 3, 'declined', 'SASDASD', '2025-11-02 19:36:51', NULL, NULL, NULL, NULL, '2025-12-15 23:22:53', NULL, NULL, '2025-12-15 23:22:53', '2025-12-15 23:22:53'),
(4, 8, 2, 6, 'declined', 'prolly 7pm is good', '2025-11-03 04:26:27', NULL, NULL, NULL, NULL, '2025-12-15 23:22:53', NULL, NULL, '2025-12-15 23:22:53', '2025-12-15 23:22:53'),
(5, 1, 2, 4, 'declined', 'adasdasda', '2025-11-03 07:44:25', NULL, NULL, NULL, NULL, '2025-12-15 23:22:53', NULL, NULL, '2025-12-15 23:22:53', '2025-12-15 23:22:53'),
(6, 1, 2, 4, 'declined', 'auysgduaysd', '2025-11-03 08:12:22', NULL, NULL, NULL, NULL, '2025-12-15 23:22:53', NULL, NULL, '2025-12-15 23:22:53', '2025-12-15 23:22:53'),
(7, 9, 2, 4, 'pending', 'kaskjad', '2025-11-04 16:34:24', NULL, NULL, NULL, NULL, '2025-12-15 23:22:53', NULL, NULL, '2025-12-15 23:22:53', '2025-12-15 23:22:53'),
(8, 1, 2, 4, 'declined', 'asdasd', '2025-11-11 07:53:06', NULL, NULL, NULL, NULL, '2025-12-15 23:22:53', NULL, NULL, '2025-12-15 23:22:53', '2025-12-15 23:22:53'),
(9, 1, 2, 4, 'declined', '12314', '2025-11-11 07:57:32', NULL, NULL, NULL, NULL, '2025-12-15 23:22:53', NULL, NULL, '2025-12-15 23:22:53', '2025-12-15 23:22:53');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notificationID` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `relatedID` int(11) DEFAULT NULL,
  `isRead` tinyint(1) DEFAULT 0,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notificationID`, `userID`, `type`, `message`, `relatedID`, `isRead`, `createdAt`) VALUES
(1, 14, 'account', 'Welcome to PeerMentor, TestUser! Your email has been verified successfully. You can now access all features of the platform.', NULL, 1, '2025-12-14 10:23:02');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `reviewID` int(11) NOT NULL,
  `enrollmentID` int(11) NOT NULL,
  `tutorUserID` int(11) NOT NULL,
  `studentUserID` int(11) NOT NULL,
  `rating` int(11) NOT NULL COMMENT 'Rating from 1 to 5',
  `comment` text DEFAULT NULL,
  `reviewDate` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tutorcourses`
--

CREATE TABLE `tutorcourses` (
  `userID` int(11) NOT NULL,
  `courseID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tutorcourses`
--

INSERT INTO `tutorcourses` (`userID`, `courseID`) VALUES
(1, 3),
(2, 4),
(2, 6),
(4, 4),
(6, 3),
(6, 4),
(9, 4),
(9, 5),
(9, 11),
(10, 4),
(16, 1),
(16, 2),
(16, 8),
(17, 4),
(17, 8);

-- --------------------------------------------------------

--
-- Table structure for table `tutorprofiles`
--

CREATE TABLE `tutorprofiles` (
  `tutorProfileID` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `tutorBio` text DEFAULT NULL,
  `availabilityDetails` varchar(255) DEFAULT NULL,
  `hourlyRate` decimal(10,2) DEFAULT 0.00,
  `rating` decimal(3,2) DEFAULT 0.00,
  `totalRatings` int(11) DEFAULT 0,
  `isActive` tinyint(1) DEFAULT 1,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `updatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tutorprofiles`
--

INSERT INTO `tutorprofiles` (`tutorProfileID`, `userID`, `tutorBio`, `availabilityDetails`, `hourlyRate`, `rating`, `totalRatings`, `isActive`, `createdAt`, `updatedAt`) VALUES
(1, 17, 'I am now a tutor', 'December 25, 2025', 0.00, 0.00, 0, 1, '2025-12-15 23:34:17', '2025-12-15 23:34:17');

-- --------------------------------------------------------

--
-- Table structure for table `tutorprofiles_backup`
--

CREATE TABLE `tutorprofiles_backup` (
  `tutorProfileID` int(11) NOT NULL DEFAULT 0,
  `userID` int(11) NOT NULL,
  `tutorBio` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `availabilityDetails` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hourlyRate` decimal(10,2) DEFAULT 0.00,
  `rating` decimal(3,2) DEFAULT 0.00,
  `totalRatings` int(11) DEFAULT 0,
  `isActive` tinyint(1) DEFAULT 1,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `updatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `userID` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `verificationToken` varchar(255) DEFAULT NULL,
  `isEmailVerified` tinyint(1) DEFAULT 0,
  `emailNotifications` tinyint(1) DEFAULT 1,
  `tokenExpiry` datetime DEFAULT NULL,
  `firstName` varchar(100) NOT NULL,
  `lastName` varchar(100) NOT NULL,
  `creationDate` datetime NOT NULL,
  `isActive` tinyint(1) NOT NULL DEFAULT 1,
  `isTutorNow` tinyint(1) NOT NULL DEFAULT 0,
  `isStudentNow` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`userID`, `email`, `password`, `verificationToken`, `isEmailVerified`, `emailNotifications`, `tokenExpiry`, `firstName`, `lastName`, `creationDate`, `isActive`, `isTutorNow`, `isStudentNow`) VALUES
(10, 'emman@gmail.com', '$2y$10$Rx/Bo0YKPxFO6rBDdzxfG.b357dI9PolZ1DS1l8owevKLPeiHXpKe', NULL, 1, 1, NULL, 'emman', 'javier', '2025-11-30 10:38:28', 1, 1, 1),
(11, 'emmanuelpjavier2403@gmail.com', '$2y$10$zd4.lyrEWXqrWY3UiIymuuCo6HjLe2LRvTEOQQ8.V8EWMW7ycSl1y', NULL, 1, 1, NULL, 'Emmanuel PJ', 'Javier', '2025-12-14 05:10:36', 1, 0, 1),
(12, 'luis@gmail.com', '$2y$10$7cC8WkVl14FbX1E4.mpDOeM9UTx5KA5k8tAZWFm7eshnKEh00oW12', NULL, 1, 1, NULL, 'Luis', 'Alfaro', '2025-12-14 05:37:14', 1, 0, 1),
(14, 'test@example.com', '$2y$10$B0FcQJk/8IAo1uYqv0/JxeKJO9Rvodu/vqLR5W9MMh7WQfIMLYZ0K', NULL, 1, 1, NULL, 'TestUser', 'EmailTest', '2025-12-14 11:17:00', 1, 0, 1),
(15, 'testuser@example.com', '$2y$10$40BczQnnVrabeFp/VLpi5..uQ5A3tBq1URLjcuwmd5RE8F8zrPLgm', NULL, 1, 1, NULL, 'Test', 'User', '2025-12-14 18:35:06', 1, 0, 1),
(16, 'testusertwo@example.com', '$2y$10$4J.HdNPR0r479HtoQnTEU.29kOIKAjfs6KpE74OoAhEm3O0d.47H.', NULL, 1, 1, NULL, 'Test', 'User Two', '2025-12-15 13:09:04', 1, 1, 1),
(17, 'mailTest@gmail.com', '$2y$10$BnlxxxUQ1Oo/H2hEjlQDheHNF11ZwazuxEgLB25lW5fTJuVGHfHEi', NULL, 1, 1, NULL, 'Mail', 'Test', '2025-12-15 23:55:19', 1, 1, 1),
(18, 'mailerTester@gmail.com', '$2y$10$AM5a3RafjNcxyIhmz0GShexHLyh7hae8GsB/g6kquZs2dbqSj/mXS', 'ae2c482866619792b1a94f6c125c2ca89147f83f58386b191cc9f344b4ab5e48', 1, 1, '2025-12-17 00:43:26', 'Mailer', 'Tester', '2025-12-16 00:43:25', 1, 0, 1),
(19, 'mailerTester2@gmail.com', '$2y$10$T2w6ziKdjlb0fpumapp3du8vZro5XZQTWmJUbaTPtDy2p6vKLE..a', '572f98ca7cd62380575cab38554e6295ad9357056cba2488be6df2c69d81fd78', 1, 1, '2025-12-17 00:44:17', 'Mail2', 'Test2', '2025-12-16 00:44:17', 1, 0, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`courseID`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`enrollmentID`),
  ADD KEY `tutorUserID` (`tutorUserID`),
  ADD KEY `studentUserID` (`studentID`),
  ADD KEY `courseID` (`courseID`),
  ADD KEY `idx_tutor` (`tutorUserID`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notificationID`),
  ADD KEY `idx_user_read` (`userID`,`isRead`),
  ADD KEY `idx_created` (`createdAt`),
  ADD KEY `idx_notifications_user_read` (`userID`,`isRead`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`reviewID`),
  ADD UNIQUE KEY `idx_unique_review` (`enrollmentID`,`studentUserID`),
  ADD KEY `idx_tutorUserID` (`tutorUserID`),
  ADD KEY `idx_studentUserID` (`studentUserID`),
  ADD KEY `idx_reviews_tutor` (`tutorUserID`);

--
-- Indexes for table `tutorcourses`
--
ALTER TABLE `tutorcourses`
  ADD PRIMARY KEY (`userID`,`courseID`),
  ADD KEY `courseID` (`courseID`);

--
-- Indexes for table `tutorprofiles`
--
ALTER TABLE `tutorprofiles`
  ADD PRIMARY KEY (`tutorProfileID`),
  ADD UNIQUE KEY `unique_user_tutor` (`userID`),
  ADD KEY `idx_active` (`isActive`),
  ADD KEY `idx_rating` (`rating`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`userID`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_verification_token` (`verificationToken`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `courseID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `enrollmentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notificationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `reviewID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tutorprofiles`
--
ALTER TABLE `tutorprofiles`
  MODIFY `tutorProfileID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `userID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`enrollmentID`) REFERENCES `enrollments` (`enrollmentID`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`tutorUserID`) REFERENCES `users` (`userID`),
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`studentUserID`) REFERENCES `users` (`userID`);

--
-- Constraints for table `tutorprofiles`
--
ALTER TABLE `tutorprofiles`
  ADD CONSTRAINT `tutorprofiles_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

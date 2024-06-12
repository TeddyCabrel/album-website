CREATE DATABASE IF NOT EXISTS `phpgallery_advanced` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `phpgallery_advanced`;

CREATE TABLE IF NOT EXISTS `accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `display_name` varchar(50) NOT NULL,
  `role` enum('Admin','Member') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

INSERT INTO `accounts` (`id`, `email`, `password`, `display_name`, `role`) VALUES
(1, 'admin@example.com', '$2y$10$ZU7Jq5yZ1U/ifeJoJzvLbenjRyJVkSzmQKQc.X0KDPkfR3qs/iA7O', 'Admin', 'Admin');

CREATE TABLE IF NOT EXISTS `collections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(50) NOT NULL,
  `description` varchar(255) NOT NULL,
  `acc_id` int(11) DEFAULT NULL,
  `public` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `media` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `filepath` varchar(255) NOT NULL,
  `uploaded_date` datetime NOT NULL DEFAULT current_timestamp(),
  `type` varchar(10) NOT NULL,
  `thumbnail` varchar(255) NOT NULL DEFAULT '',
  `approved` tinyint(1) NOT NULL DEFAULT 1,
  `public` tinyint(1) NOT NULL DEFAULT 1,
  `acc_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

INSERT INTO `media` (`id`, `title`, `description`, `filepath`, `uploaded_date`, `type`, `thumbnail`, `approved`, `public`, `acc_id`) VALUES
(1, 'Abandoned Building', '', 'media/images/abandoned-building.jpg', '2022-10-26 00:00:00', 'image', '', 1, 1, 1),
(2, 'Road', 'Going down the only road I\'ve even known.', 'media/images/road.jpg', '2022-10-26 00:00:00', 'image', '', 1, 1, 1),
(3, 'Stars', 'A wonderful view of the night sky.', 'media/images/stars.jpg', '2022-10-26 00:00:00', 'image', '', 1, 1, NULL),
(4, 'Sample Video', 'This is just a sample video.', 'media/videos/sample.mp4', '2022-10-26 00:00:00', 'video', '', 1, 1, 1),
(5, 'Sample Audio', 'This is just a sample audio.', 'media/audios/sample.mp3', '2022-10-26 00:00:00', 'audio', '', 1, 1, 1),
(6, 'Beach', 'Awesome day at the beach!', 'media/images/beach.jpg', '2022-10-26 00:00:00', 'image', '', 1, 1, NULL);

CREATE TABLE IF NOT EXISTS `media_collections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `collection_id` int(11) NOT NULL,
  `media_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_id` (`collection_id`,`media_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `media_likes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `media_id` int(11) NOT NULL,
  `acc_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
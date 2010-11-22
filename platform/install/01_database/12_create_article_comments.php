<?php
/**
 * Table used for article comments
 */
	SERIA_BASE::db()->exec('
		CREATE TABLE ' . SERIA_PREFIX . '_article_comments (
			id INT PRIMARY KEY NOT NULL,
			articleId INT NOT NULL,
			universe VARCHAR(50) NOT NULL,
			author_name VARCHAR(100),
			author_email VARCHAR(100),
			author_id INT,
			created_date DATETIME NOT NULL,
			altered_date DATETIME,
			subject VARCHAR(200),
			message TEXT, 
			index(articleId, universe, created_date)
		)
	');
?>
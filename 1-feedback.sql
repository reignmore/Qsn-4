-- (A) FEEDBACK
CREATE TABLE `feedback` (
  `feedback_id` bigint(20) NOT NULL,
  `feedback_title` varchar(255) NOT NULL,
  `feedback_desc` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `feedback`
  ADD PRIMARY KEY (`feedback_id`);

ALTER TABLE `feedback`
  MODIFY `feedback_id` bigint(20) NOT NULL AUTO_INCREMENT;

-- (B) FEEDBACK QUESTIONS
CREATE TABLE `feedback_questions` (
  `feedback_id` bigint(20) NOT NULL,
  `question_id` bigint(20) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` varchar(1) NOT NULL DEFAULT 'R'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `feedback_questions`
  ADD PRIMARY KEY (`feedback_id`,`question_id`);

-- (C) FEEDBACK FROM USERS
CREATE TABLE `feedback_users` (
  `user_id` bigint(20) NOT NULL,
  `feedback_id` bigint(20) NOT NULL,
  `question_id` bigint(20) NOT NULL,
  `feedback_value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `feedback_users`
  ADD PRIMARY KEY (`user_id`,`feedback_id`,`question_id`);

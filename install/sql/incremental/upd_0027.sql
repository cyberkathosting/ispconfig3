ALTER TABLE  `mail_user` ADD  `autoresponder_subject` VARCHAR(255) NOT NULL DEFAULT 'Out of office reply' AFTER  `autoresponder_end_date`;

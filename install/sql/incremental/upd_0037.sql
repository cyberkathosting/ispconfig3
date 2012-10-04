-- --------------------------------------------------------

ALTER TABLE `client` ADD `limit_cgi` ENUM( 'n', 'y' ) NOT NULL DEFAULT 'n' AFTER `web_php_options`,
                     ADD `limit_ssi` ENUM( 'n', 'y' ) NOT NULL DEFAULT 'n' AFTER `limit_cgi`,
                     ADD `limit_perl` ENUM( 'n', 'y' ) NOT NULL DEFAULT 'n' AFTER `limit_ssi`,
                     ADD `limit_ruby` ENUM( 'n', 'y' ) NOT NULL DEFAULT 'n' AFTER `limit_perl`,
                     ADD `limit_python` ENUM( 'n', 'y' ) NOT NULL DEFAULT 'n' AFTER `limit_ruby`,
                     ADD `force_suexec` ENUM( 'n', 'y' ) NOT NULL DEFAULT 'y' AFTER `limit_python`,
                     ADD `limit_hterror` ENUM( 'n', 'y' ) NOT NULL DEFAULT 'n' AFTER `force_suexec`,
                     ADD `limit_wildcard` ENUM( 'n', 'y' ) NOT NULL DEFAULT 'n' AFTER `limit_hterror`,
                     ADD `limit_ssl` ENUM( 'n', 'y' ) NOT NULL DEFAULT 'n' AFTER `limit_wildcard`;

ALTER TABLE `client_template` ADD `limit_cgi` ENUM( 'n', 'y' ) NOT NULL DEFAULT 'n' AFTER `web_php_options`,
                              ADD `limit_ssi` ENUM( 'n', 'y' ) NOT NULL DEFAULT 'n' AFTER `limit_cgi`,
                              ADD `limit_perl` ENUM( 'n', 'y' ) NOT NULL DEFAULT 'n' AFTER `limit_ssi`,
                              ADD `limit_ruby` ENUM( 'n', 'y' ) NOT NULL DEFAULT 'n' AFTER `limit_perl`,
                              ADD `limit_python` ENUM( 'n', 'y' ) NOT NULL DEFAULT 'n' AFTER `limit_ruby`,
                              ADD `force_suexec` ENUM( 'n', 'y' ) NOT NULL DEFAULT 'y' AFTER `limit_python`,
                              ADD `limit_hterror` ENUM( 'n', 'y' ) NOT NULL DEFAULT 'n' AFTER `force_suexec`,
                              ADD `limit_wildcard` ENUM( 'n', 'y' ) NOT NULL DEFAULT 'n' AFTER `limit_hterror`,
                              ADD `limit_ssl` ENUM( 'n', 'y' ) NOT NULL DEFAULT 'n' AFTER `limit_wildcard`;



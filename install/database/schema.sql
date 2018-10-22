/*
 *  This file is part of SNEP.
 *
 *  SNEP is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Lesser General Public License as
 *  published by the Free Software Foundation, either version 3 of
 *  the License, or (at your option) any later version.
 *
 *  SNEP is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Lesser General Public License for more details.
 *
 *  You should have received a copy of the GNU Lesser General Public License
 *  along with SNEP.  If not, see <http://www.gnu.org/licenses/lgpl.txt>.
 */

/**
 * Database structure
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2014 OpenS Tecnologia
 * @author    Opens Tecnologia <desenvolvimento@opens.com.br>
 */

--
-- Table structure for table `expr_alias`
--
CREATE TABLE IF NOT EXISTS expr_alias (
    `aliasid` INTEGER PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


--
-- Table structure for table `expr_alias_expression`
--
CREATE TABLE IF NOT EXISTS expr_alias_expression (
    `aliasid` INTEGER NOT NULL,
    `expression` VARCHAR(200) NOT NULL,
    FOREIGN KEY (`aliasid`) REFERENCES expr_alias(`aliasid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `date_alias`
--

CREATE TABLE IF NOT EXISTS `date_alias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8;

--
-- Table structure for table `date_alias_list`
--
CREATE TABLE IF NOT EXISTS `date_alias_list` (
  `dateid` int(11) NOT NULL,
  `date` varchar(10) DEFAULT NULL,
  `timerange` varchar(11) DEFAULT '00:00-23:59',
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8;

--
-- Table structure for table `regras_negocio`
--
CREATE TABLE IF NOT EXISTS regras_negocio (
  id integer PRIMARY KEY auto_increment,
  prio integer NOT NULL default 0,
  `desc` varchar(255) default NULL,
  origem text NOT NULL,
  destino text NOT NULL,
  validade text NOT NULL,
  diasDaSemana varchar(30) NOT NULL DEFAULT "sun,mon,tue,wed,thu,fri,sat",
  record boolean NOT NULL default false,
  ativa boolean NOT NULL default true,
  mailing boolean NOT NULL default false,
  from_dialer boolean NOT NULL default false,
  type enum('incoming','outgoing','others') NOT NULL DEFAULT 'others',
  dates_alias varchar(20)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


--
-- Table structure for table `regras_negocio_actions`
--
CREATE TABLE IF NOT EXISTS regras_negocio_actions (
  regra_id integer NOT NULL,
  prio integer NOT NULL,
  `action` varchar(250) NOT NULL,
  PRIMARY KEY(regra_id, prio),
  FOREIGN KEY (regra_id) REFERENCES regras_negocio(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


--
-- Table structure for table `regras_negocio_actions_config`
--
CREATE TABLE IF NOT EXISTS regras_negocio_actions_config (
  regra_id integer NOT NULL,
  prio integer NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY(regra_id,prio,`key`),
  FOREIGN KEY (regra_id, prio) REFERENCES regras_negocio_actions (regra_id, prio) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


--
-- Table structure for table `registry`
--
CREATE TABLE IF NOT EXISTS `registry` (
    `context` VARCHAR(50),
    `key` VARCHAR(30),
    `value` VARCHAR(250),
    PRIMARY KEY (`context`,`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


--
-- Table structure for table `ccustos`
--
CREATE TABLE IF NOT EXISTS `ccustos` (
  `codigo` char(7) NOT NULL,
  `tipo` char(1) NOT NULL,
  `nome` varchar(40) NOT NULL,
  `descricao` varchar(250) default NULL,
  PRIMARY KEY  (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


--
-- Table structure for table `cdr`
--
CREATE TABLE IF NOT EXISTS `cdr` (
  `calldate` datetime NOT NULL default '0000-00-00 00:00:00',
  `clid` varchar(80) NOT NULL default '',
  `src` varchar(80) NOT NULL default '',
  `dst` varchar(80) NOT NULL default '',
  `dcontext` varchar(80) NOT NULL default '',
  `channel` varchar(80) NOT NULL default '',
  `dstchannel` varchar(80) NOT NULL default '',
  `lastapp` varchar(80) NOT NULL default '',
  `lastdata` varchar(80) NOT NULL default '',
  `duration` int(11) NOT NULL default '0',
  `billsec` int(11) NOT NULL default '0',
  `disposition` varchar(45) NOT NULL default '',
  `amaflags` int(20) NOT NULL default '0',
  `accountcode` varchar(20) NOT NULL default '',
  `uniqueid` varchar(32) NOT NULL default '',
  `userfield` varchar(255) NOT NULL default '',
  KEY `calldate` (`calldate`),
  KEY `dst` (`dst`),
  KEY `accountcode` (`accountcode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


--
-- Table structure for table `grupos`
--
CREATE TABLE IF NOT EXISTS `grupos` (
  `cod_grupo` integer NOT NULL auto_increment,
  `nome` varchar(30) NOT NULL,
  UNIQUE KEY `cod_grupo` (`cod_grupo`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

--
-- Table structure for table `peers`
--
CREATE TABLE IF NOT EXISTS `peers` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(80) NOT NULL default '',
  `password` VARCHAR(12) NOT NULL,
  `accountcode` varchar(20) default NULL,
  `amaflags` varchar(13) default NULL,
  `callgroup` varchar(10) default NULL,
  `callerid` varchar(80) default NULL,
  `canreinvite` char(3) default 'no',
  `context` varchar(80) default NULL,
  `defaultip` varchar(15) default NULL,
  `dtmfmode` varchar(7) default NULL,
  `fromuser` varchar(80) default NULL,
  `fromdomain` varchar(80) default NULL,
  `fullcontact` varchar(80) default NULL,
  `host` varchar(31) NOT NULL default '',
  `insecure` varchar(4) default NULL,
  `language` char(2) default 'br',
  `mailbox` varchar(50) default NULL,
  `md5secret` varchar(80) default '',
  `nat` varchar(100) NOT NULL default 'no',
  `deny` varchar(95) default NULL,
  `permit` varchar(95) default NULL,
  `mask` varchar(95) default NULL,
  `pickupgroup` integer default NULL,
  `port` varchar(5) NOT NULL default '',
  `qualify` char(5) default NULL,
  `restrictcid` char(1) default NULL,
  `rtptimeout` char(3) default NULL,
  `rtpholdtimeout` char(3) default NULL,
  `secret` varchar(80) default NULL,
  `type` varchar(6) NOT NULL default 'friend',
  `defaultuser` varchar(80) NOT NULL default '',
  `disallow` varchar(100) default 'all',
  `allow` varchar(100) default 'ulaw;alaw;gsm',
  `musiconhold` varchar(100) default NULL,
  `regseconds` int(11) NOT NULL default '0',
  `ipaddr` varchar(45) NOT NULL default '',
  `regexten` varchar(80) NOT NULL default '',
  `cancallforward` varchar(3) default 'yes',
  `setvar` varchar(100) NOT NULL default '',
  `email` varchar(255) default NULL,
  `canal` varchar(255) default NULL,
  `call-limit` varchar(4) default '1',
  `incominglimit` varchar(4) default NULL,
  `outgoinglimit` varchar(4) default NULL,
  `usa_vc` varchar(4) NOT NULL default 'no',
  `peer_type` char(1) NOT NULL default 'R',
  `credits` int(11) default NULL,
  `authenticate` boolean not null default false,
  `subscribecontext` varchar(40) default NULL,
  `trunk` varchar(3) NOT NULL,
  `time_total` int(11) default NULL,
  `time_chargeby` char(1) default NULL,
  `regserver` varchar(20) default NULL,
  `dnd` BOOL NOT NULL DEFAULT '0',
  `sigame` VARCHAR( 20 ) NULL ,
  `directmedia` varchar(10),
  `lastms` int(11) NOT NULL,
  `callbackextension` VARCHAR(250) default NULL,
  `useragent` VARCHAR(250) default NULL,
  `blf` VARCHAR(3) default NULL,
  `disabled` BOOLEAN DEFAULT false,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `name_2` (`name`),
  FOREIGN KEY (`pickupgroup`) REFERENCES grupos(`cod_grupo`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


--
-- Table structure for table `services_log`
--
CREATE TABLE IF NOT EXISTS `services_log` (
  `date` datetime NOT NULL,
  `peer` varchar(80) NOT NULL,
  `service` varchar(50) NOT NULL,
  `state` tinyint(1) NOT NULL,
  `status` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


--
-- Table structure for table `queue_log`
--
CREATE TABLE IF NOT EXISTS `queue_log` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `time` char(26) default NULL,
  `callid` varchar(32) NOT NULL default '',
  `queuename` varchar(32) NOT NULL default '',
  `agent` varchar(32) NOT NULL default '',
  `event` varchar(32) NOT NULL default '',
  `data1` varchar(100) NOT NULL default '',
  `data2` varchar(100) NOT NULL default '',
  `data3` varchar(100) NOT NULL default '',
  `data4` varchar(100) NOT NULL default '',
  `data5` varchar(100) NOT NULL default '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


--
-- Table structure for table `queue_members`
--
CREATE TABLE IF NOT EXISTS `queue_members` (
  `uniqueid` int(10) unsigned NOT NULL auto_increment,
  `membername` varchar(40) default NULL,
  `queue_name` varchar(128) default NULL,
  `interface` varchar(128) default NULL,
  `penalty` int(11) default NULL,
  `paused` int(2) default NULL,
  PRIMARY KEY  (`uniqueid`),
  UNIQUE KEY `queue_interface` (`queue_name`,`interface`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


--
-- Table structure for table `queue_peers`
--
CREATE TABLE IF NOT EXISTS `queue_peers` (
  `fila` varchar(80) NOT NULL default '',
  `ramal` int(11) NOT NULL,
  PRIMARY KEY  (`ramal`,`fila`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


--
-- Table structure for table `queues`
--
CREATE TABLE IF NOT EXISTS `queues` (
  `id` integer auto_increment,
  `name` varchar(128) NOT NULL,
  `musiconhold` varchar(128) default NULL,
  `announce` varchar(128) default NULL,
  `context` varchar(128) default NULL,
  `timeout` int(11) default NULL,
  `monitor_type` tinyint(1) default NULL,
  `monitor_format` varchar(128) default NULL,
  `queue_youarenext` varchar(128) default NULL,
  `queue_thereare` varchar(128) default NULL,
  `queue_callswaiting` varchar(128) default NULL,
  `queue_holdtime` varchar(128) default NULL,
  `queue_minutes` varchar(128) default NULL,
  `queue_seconds` varchar(128) default NULL,
  `queue_lessthan` varchar(128) default NULL,
  `queue_thankyou` varchar(128) default NULL,
  `queue_reporthold` varchar(128) default NULL,
  `announce_frequency` int(11) default NULL,
  `announce_round_seconds` int(11) default NULL,
  `announce_holdtime` varchar(128) default NULL,
  `retry` int(11) default NULL,
  `wrapuptime` int(11) default NULL,
  `maxlen` int(11) default NULL,
  `ringinuse` BOOL NOT NULL DEFAULT '1',
  `servicelevel` int(11) default NULL,
  `strategy` varchar(128) default NULL,
  `joinempty` varchar(128) default NULL,
  `leavewhenempty` varchar(128) default NULL,
  `eventmemberstatus` tinyint(1) default NULL,
  `eventwhencalled` tinyint(1) default NULL,
  `reportholdtime` tinyint(1) default NULL,
  `memberdelay` int(11) default NULL,
  `weight` int(11) default NULL,
  `timeoutrestart` tinyint(1) default NULL,
  `periodic_announce` varchar(50) default NULL,
  `periodic_announce_frequency` int(11) default NULL,
  `max_call_queue` int(11) default '0',
  `max_time_call` int(11) default '0',
  `alert_mail` varchar(80) default NULL,
  PRIMARY KEY  (`id`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `queues_agent`
--
CREATE TABLE IF NOT EXISTS `queues_agent` (
  `agent_id` int(11) NOT NULL,
  `queue` varchar(80) NOT NULL,
  `penalty` VARCHAR(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


--
-- Table structure for table `sounds`
--
CREATE TABLE IF NOT EXISTS `sounds` (
  `arquivo` varchar(50) NOT NULL,
  `descricao` varchar(80) NOT NULL,
  `data` datetime default NULL,
  `tipo` char(3) NOT NULL default 'AST',
  `secao` varchar(30) NOT NULL,
  `language` varchar(5) NOT NULL default 'pt_BR',
  PRIMARY KEY  (`arquivo`,`tipo`,`secao`,`language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `trunks`
--
CREATE TABLE IF NOT EXISTS `trunks` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(80) NOT NULL default '',
  `accountcode` varchar(20) default NULL,
  `callerid` varchar(80) default NULL,
  `context` varchar(80) default NULL,
  `dtmfmode` varchar(7) default NULL,
  `insecure` varchar(20) default NULL,
  `secret` varchar(80) default NULL,
  `username` varchar(80) default NULL,
  `allow` varchar(100) default 'g729;ilbc;gsm;ulaw;alaw',
  `channel` varchar(255) default NULL,
  `type` varchar(200) default NULL,
  `trunktype` char(1) NOT NULL,
  `host` varchar(31) default NULL,
  `trunk_redund` int(11) default NULL,
  `time_total` int(11) default NULL,
  `time_chargeby` char(1) default NULL,
  `time_initial_date` int(11) default NULL,
  `dialmethod` VARCHAR(6) NOT NULL DEFAULT 'NORMAL',
  `id_regex` VARCHAR(255) NULL,
  `map_extensions` BOOLEAN DEFAULT FALSE,
  `reverse_auth` BOOLEAN DEFAULT TRUE,
  `dtmf_dial` BOOLEAN NOT NULL DEFAULT FALSE,
  `dtmf_dial_number` VARCHAR(50) DEFAULT NULL,
  `domain` VARCHAR( 250 ) NOT NULL,
  `technology` VARCHAR( 20 ) NOT NULL,
  `telco` INT(10) DEFAULT NULL,
  `disabled` BOOLEAN DEFAULT false,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


--
-- Table structure for table `time_history`
--
CREATE TABLE IF NOT EXISTS `time_history` (
  `id` integer NOT NULL auto_increment,
  `owner` integer NOT NULL,
  `year` integer NOT NULL,
  `month` integer,
  `day` integer,
  `used` integer NOT NULL default '0',
  `changed` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `owner_type` char(1) NOT NULL default 'T',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;



--
-- Table structure for table `voicemail_messages`
--
CREATE TABLE IF NOT EXISTS `voicemail_messages` (
  `id` int(11) NOT NULL auto_increment,
  `msgnum` int(11) NOT NULL default '0',
  `dir` varchar(80) default '',
  `context` varchar(80) default '',
  `macrocontext` varchar(80) default '',
  `callerid` varchar(40) default '',
  `origtime` varchar(40) default '',
  `duration` varchar(20) default '',
  `mailboxuser` varchar(80) default '',
  `mailboxcontext` varchar(80) default '',
  `recording` longblob,
  PRIMARY KEY  (`id`),
  KEY `dir` (`dir`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


--
-- Table structure for table `voicemail_users`
--
CREATE TABLE IF NOT EXISTS `voicemail_users` (
  `uniqueid` int(11) NOT NULL auto_increment,
  `customer_id` varchar(11) NOT NULL default '0',
  `context` varchar(50) default '',
  `mailbox` varchar(11) NOT NULL default '0',
  `password` varchar(10) NOT NULL default '0',
  `fullname` varchar(150) NOT NULL default '',
  `email` varchar(50) NOT NULL default '',
  `pager` varchar(50) default '',
  `tz` varchar(10) NOT NULL default 'central24',
  `attach` varchar(4) NOT NULL default 'yes',
  `saycid` varchar(4) NOT NULL default 'yes',
  `dialout` varchar(10) default '',
  `callback` varchar(10) default '',
  `review` varchar(4) NOT NULL default 'no',
  `operator` varchar(4) NOT NULL default 'no',
  `envelope` varchar(4) NOT NULL default 'no',
  `sayduration` varchar(4) NOT NULL default 'no',
  `saydurationm` tinyint(4) NOT NULL default '1',
  `sendvoicemail` varchar(4) NOT NULL default 'no',
  `delete` varchar(4) NOT NULL default 'no',
  `nextaftercmd` varchar(4) NOT NULL default 'yes',
  `forcename` varchar(4) NOT NULL default 'no',
  `forcegreetings` varchar(4) NOT NULL default 'no',
  `hidefromdir` varchar(4) NOT NULL default 'yes',
  `stamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`uniqueid`),
  KEY `mailbox_context` (`mailbox`,`context`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



--
-- Table structure for table `core_cnl_country`
--
CREATE TABLE IF NOT EXISTS `core_cnl_country` (
    `id` integer primary key,
    `name` varchar(30) not null,
    `code_2` varchar(2) not null,
    `code_3` varchar(3) not null,
    `language` varchar(5) not null,
    `locale` varchar(5) not null
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


--
-- Table structure for table `core_cnl_state`
--
CREATE TABLE IF NOT EXISTS core_cnl_state (
    `id` char(2),
    `name` varchar(30) not null,
    `country` integer,
    primary key (`id`,`country`),
    foreign key (`country`) references core_cnl_country(`id`) on update cascade on delete restrict
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


--
-- Table structure for table `core_cnl_city`
--
CREATE TABLE IF NOT EXISTS core_cnl_city (
    `id` integer auto_increment,
    `name` varchar(50) not null,
    `state` varchar(2),
    primary key (`id`,`state`),
    foreign key (`state`) references core_cnl_state(`id`) on update cascade on delete restrict
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


--
-- Table structure for table `core_cnl_prefix`
--
CREATE TABLE IF NOT EXISTS core_cnl_prefix (
    `id` char(10),
    `city` integer,
    `country` integer,
    `latitud` varchar(8),
    `longitud` varchar(8),
    `hemisphere` varchar(5),
    primary key (`id`,`country`),
    foreign key (`city`) references core_cnl_city(`id`) on update cascade on delete restrict,
    foreign key (`country`) references core_cnl_country(`id`) on update cascade on delete restrict
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



--
-- Table structure for table `core_country`
--
CREATE TABLE IF NOT EXISTS `core_country` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(36) NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  `active` tinyint(1) NOT NULL,
  `name` varchar(128) NOT NULL,
  `acronym` varchar(4) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


--
-- Table structure for table `core_state`
--
CREATE TABLE IF NOT EXISTS `core_state` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(36) NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  `active` tinyint(1) NOT NULL,
  `country_id` int(11) NOT NULL,
  `acronym` varchar(4) NOT NULL,
  `name` varchar(128) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acronym` (`acronym`),
  UNIQUE KEY `name` (`name`),
  KEY `core_state_d860be3c` (`country_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


--
-- Table structure for table `core_city`
--
CREATE TABLE IF NOT EXISTS `core_city` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(36) NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  `active` tinyint(1) NOT NULL,
  `state_id` int(11) NOT NULL,
  `name` varchar(128) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `core_city_5654bf12` (`state_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

--
-- Table structure for table `contacts_group`
--
CREATE TABLE IF NOT EXISTS `contacts_group` (
  `id` integer NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


--
-- Table structure for table `contacts_names`
--
CREATE TABLE IF NOT EXISTS `contacts_names` (
  `id` integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(80) NOT NULL,
  `email` varchar(80) DEFAULT NULL,
  `address` varchar(100),
  `id_state` char(2),
  `id_city` int(11),
  `cep` varchar(8),
  `group` integer NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  CONSTRAINT contacts_group_fk FOREIGN KEY (`group`) REFERENCES contacts_group(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
  ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


--
-- Table structure for table `contacts_phone`
--
CREATE TABLE IF NOT EXISTS `contacts_phone` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contact_id` int NOT NULL,
  `phone` varchar(15) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `contact_id` (`contact_id`),
  CONSTRAINT `contacts_phone_refs_contact_id` FOREIGN KEY (`contact_id`) REFERENCES `contacts_names` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


--
-- Table structure for table `profiles`
--
CREATE TABLE IF NOT EXISTS `profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


--
-- Table structure for table `permissions`
--
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` int(11) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


--
-- Table structure for table `users`
--
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(45) NOT NULL,
  `password` VARCHAR(45) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `dashboard` text NOT NULL ,
  `profile_id` INT NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `profile_id` (`profile_id`),
  CONSTRAINT `fk_user_profile` FOREIGN KEY (`profile_id` ) REFERENCES `profiles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


--
-- Table structure for table `profiles_permissions`
--
CREATE TABLE IF NOT EXISTS `profiles_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `permission_id` varchar(256) NOT NULL,
  `profile_id` INT NOT NULL,
  `allow` tinyint(1) NOT NULL default '0',
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `profile_id` (`profile_id`),
  CONSTRAINT `fk_user_profiles` FOREIGN KEY (`profile_id` ) REFERENCES `profiles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


--
-- Table structure for table `users_permissions`
--
CREATE TABLE IF NOT EXISTS `users_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `permission_id` varchar(256) NOT NULL,
  `allow` tinyint(1) NOT NULL default '0',
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_user_users` FOREIGN KEY (`user_id` ) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `users_queues_permissions`
--
CREATE TABLE IF NOT EXISTS `users_queues_permissions` (
	  `id` integer NOT NULL auto_increment,
	  `user_id` int(11) NOT NULL,
	  `queue_id` int(11) NOT NULL,
	  PRIMARY KEY  (`id`),
	  CONSTRAINT `fk_id_user_p` FOREIGN KEY (`user_id` ) REFERENCES `users` (`id`),
	  CONSTRAINT `fk_id_queue_p` FOREIGN KEY (`queue_id` ) REFERENCES `queues` (`id`)
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

--
-- Table structure for table `core_binds`
--
CREATE TABLE IF NOT EXISTS `core_binds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `peer_name` varchar(80) NOT NULL,
  `type` enum('bound','nobound') NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `peer_name` (`peer_name`),
  CONSTRAINT `binds_refs_peer_name` FOREIGN KEY (`peer_name`) REFERENCES `peers` (`name`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `binds_peer_refs_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `core_binds_exceptions`
--
CREATE TABLE IF NOT EXISTS `core_binds_exceptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `exception` text NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `binds_peer_refs_user_id_exception` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `core_groups`
--
CREATE TABLE IF NOT EXISTS `core_groups` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL,
     PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `core_peer_groups`
--
CREATE TABLE IF NOT EXISTS `core_peer_groups` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `peer_id` int(11) NOT NULL,
    `group_id` int(11) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `group_id` (`group_id`),
    KEY `peer_id` (`peer_id`),
    CONSTRAINT `core_peer_groups_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `core_groups` (`id`) ON DELETE CASCADE,
    CONSTRAINT `core_peer_groups_ibfk_2` FOREIGN KEY (`peer_id`) REFERENCES `peers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8  ;

--
-- Table structure for table `password_recovery`
--
CREATE TABLE IF NOT EXISTS `password_recovery` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `code` varchar(10) NOT NULL,
  `created` datetime NOT NULL,
  `expiration` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `password_recovery_refs_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


--
-- Table structure for table `core_notifications`
--
CREATE TABLE IF NOT EXISTS `core_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_itc` int(11),
  `title` varchar(255) NOT NULL,
  `from` varchar(128) DEFAULT "Opens",
  `message` text NOT NULL,
  `creation_date` datetime NOT NULL,
  `read` boolean DEFAULT false,
  `reading_date` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


--
-- Table structure for table `core_config`
--
CREATE TABLE IF NOT EXISTS `core_config` (
  `id` INTEGER PRIMARY KEY AUTO_INCREMENT,
  `config_module` VARCHAR (255) NOT NULL,
  `config_name` VARCHAR (255) NOT NULL,
  `config_value` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



--
-- Table structure for table `itc_register`
--
CREATE TABLE IF NOT EXISTS `itc_register` (
  `uuid` varchar(36) NOT NULL,
  `client_key` varchar(60) NOT NULL,
  `api_key` varchar(72) NOT NULL,
  `created` datetime NOT NULL,
  `registered_itc` boolean DEFAULT false,
  `noregister` boolean DEFAULT false,
  PRIMARY KEY (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


--
-- Table structure for table `itc_consumers`
--
CREATE TABLE IF NOT EXISTS `itc_consumers` (
  `id_distro` int NOT NULL,
  `id_service` int NOT NULL,
  `name_service` varchar(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `logs_users`
--
CREATE TABLE IF NOT EXISTS `logs_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `datetime` datetime NOT NULL,
  `ip` varchar(15) NOT NULL,
  `user` varchar(30) NOT NULL,
  `action` varchar(30) NOT NULL,
  `description` varchar(255) NOT NULL,
  `table` varchar(30) NOT NULL,
  `registerid` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexing table `cdr`
--
CREATE INDEX cdr_clid ON cdr (clid(30));
CREATE INDEX cdr_src ON cdr (src(30));
CREATE INDEX cdr_dst ON cdr (dst(30));
CREATE INDEX cdr_dcontext ON cdr (dcontext(30));
CREATE INDEX cdr_channel ON cdr (channel(30));
CREATE INDEX cdr_dstchannel ON cdr (dstchannel(50));
CREATE INDEX cdr_lastapp ON cdr (lastapp(30));
CREATE INDEX cdr_lastdata ON cdr (lastdata(50));
CREATE INDEX cdr_disposition ON cdr (disposition(30));
CREATE INDEX cdr_accountcode ON cdr (accountcode(20));
CREATE INDEX cdr_uniqueid ON cdr (uniqueid(32));
CREATE INDEX cdr_userfield ON cdr (userfield(120));

--
-- Indexing table 'queue_log'
--
CREATE INDEX queue_log_time ON queue_log (time);
CREATE INDEX queue_log_callid ON queue_log (callid);
CREATE INDEX queue_log_queuename ON queue_log (queuename);
CREATE INDEX queue_log_agent ON queue_log (agent);
CREATE INDEX queue_log_event ON queue_log (event);

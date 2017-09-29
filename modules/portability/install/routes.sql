INSERT INTO `regras_negocio` VALUES
('',0,'Saidas Celular Local - TIM','X','RX:5534148|.','00:00:00-23:59:59','sun,mon,tue,wed,thu,fri,sat',1,1,0,0,'outgoing','0'),
('',0,'Saidas Celular Local - VIVO','X','RX:5531548|.,RX:5532048|.,RX:5532348|.','00:00:00-23:59:59','sun,mon,tue,wed,thu,fri,sat',1,1,0,0,'outgoing','0'),
('',0,'Saidas Celular Local - OI','X','RX:5533148|.,RX:5533548|.,RX:5531448|.','00:00:00-23:59:59','sun,mon,tue,wed,thu,fri,sat',0,1,0,0,'outgoing','0'),
('',0,'Saidas Celular Local - CLARO','X','RX:5532148|.','00:00:00-23:59:59','sun,mon,tue,wed,thu,fri,sat',0,1,0,0,'outgoing','0'),
('',0,'Saidas Celular Local - Outras Operadoras','X','RX:5537748|.,RX:5531248|.,RX:5535148|.','00:00:00-23:59:59','sun,mon,tue,wed,thu,fri,sat',0,1,0,0,'outgoing','0'),
('',0,'Saidas Celular DDD - TIM','X','RX:55341|.','00:00:00-23:59:59','sun,mon,tue,wed,thu,fri,sat',1,1,0,0,'outgoing','0'),
('',0,'Saidas Celular DDD - VIVO','X','RX:55315|.,RX:55320|.,RX:55323|.','00:00:00-23:59:59','sun,mon,tue,wed,thu,fri,sat',1,1,0,0,'outgoing','0'),
('',0,'Saidas Celular DDD - OI','X','RX:55331|.,RX:55335|.,RX:55314|.','00:00:00-23:59:59','sun,mon,tue,wed,thu,fri,sat',0,1,0,0,'outgoing','0'),
('',0,'Saidas Celular DDD - CLARO','X','RX:55321|.','00:00:00-23:59:59','sun,mon,tue,wed,thu,fri,sat',0,1,0,0,'outgoing','0'),
('',0,'Saidas Celular DDD - Outras Operadoras','X','RX:55377|.,RX:55312|.,RX:55351|.','00:00:00-23:59:59','sun,mon,tue,wed,thu,fri,sat',0,1,0,0,'outgoing','0'),
('',1,'Saidas Celular Local - Consulta Portabilidade','RX:XXXX','AL:2','00:00:00-23:59:59','sun,mon,tue,wed,thu,fri,sat',0,1,0,0,'outgoing','0'),
('',1,'Saidas Celular DDD - Consulta Portabilidade','RX:XXXX','AL:4','00:00:00-23:59:59','sun,mon,tue,wed,thu,fri,sat',0,1,0,0,'outgoing','0');

--
-- Table structure for table `portability_cache`
--
CREATE TABLE IF NOT EXISTS portability_cache (
    `id` INTEGER PRIMARY KEY AUTO_INCREMENT,
    `phone` VARCHAR(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE  `dns_rr` MODIFY COLUMN `type` enum('A','AAAA','ALIAS','CNAME','HINFO','MX','NAPTR','NS','PTR','RP','SPF','SRV','TXT') default NULL;

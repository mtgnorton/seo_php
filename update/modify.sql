# 系统更新sql demo,每个sql语句以;结尾换行分隔

ALTER TABLE `seo_php`.`gathers` 
ADD COLUMN `no_regular_url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '忽略的网址正则,一行一条';

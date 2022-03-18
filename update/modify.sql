# 系统更新sql demo,每个sql语句以;结尾换行分隔



alter table gathers add no_regular_url text default null comment "忽略的网址正则,一行一条" after regular_url;

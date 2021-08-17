SEO管理系统
===============
框架版本
> Laravel 6.20.27
>Laravel-admin 1.8.11
> PHP >= 7.2.5
> Mysql 5.6
> 函数: pcntl_alarm,exec
> 扩展: imagick


## nginx和php配置
- 修改php-fpm.conf中的request_terminate_timeout为1200,重启php,用于放开采集时的时间限制
-  nginx配置文件如`/www/server/panel/vhost/nginx/seo.grayvip.com.conf`及其父目录需要读写权限
- php会执行的命令有`sudo nginx -s reload 2>&1`,用于域名解析后重启nginx,`sudo git reset --hard;sudo git pull 2>&1`,用于代码更新

## 部署
- 入口文件为/public下的index.php

- storage目录需要写入权限

- 开启伪静态 

- 复制.env.example -> .env, 并配置好数据库和redis连接

- 根目录下执行 `composer install --no-scripts`, 安装对应包

- 根目录下执行 `php artisan vendor:publish --tag=laravel-admin-chartjs`, 发布chartjs

- 根目录下执行 `php artisan key:generate`, 生成APP_KEY

- 根目录下执行 `php artisan storage:link`, 生成存储连接

- 根目录下执行 `php artisan admin:install`, 安装相关数据库

- 根目录下执行 `php artisan db:seed`, 执行数据填充

- 将该命令`* * * * * cd 项目目录 && php artisan schedule:run`加入crontab调度中, 每分钟执行一次, 以www用户运行

- 将命令: `cd 项目目录 && php artisan queue:work --tries=3 --timeout=3000` 加入supervisor中, 以www用户运行

## 配置
#### .env文件
(参照.env.example)

| 字段名  | 描述 | 例 |
| ------------- | ------------- | ------------- |
| APP_NAME  | 项目名称  | laravel  |
| APP_ENV  | 环境  | 本地: local, 线上: production  |
| APP_KEY  | 项目key  | base64:JQSRPglByKntG+7pUikfdFl1PhEhc1Pse2B9Ek543xU=  |
| APP_DEBUG  | 是否开启debug  | true  |
| APP_URL  | 项目地址  | http://localhost  |
| AUTH_DOMAIN  | 授权域名  |  用于授权,必填,否则系统无法是使用 |
| OFFICIAL_DOMAIN  | 官网域名  |  必填,否则系统无法更新 |
| NGINX_VHOST_PATH  | nginx虚拟域名配置文件路径  |  必填,否则系统无法使用域名解析 |
| LOG_CHANNEL  | 日志记录类型  | stack  |
| DB_CONNECTION  | 数据库类型  | mysql  |
| DB_HOST  | 数据库地址  | 127.0.0.1  |
| DB_PORT  | 数据库端口  | 3306  |
| DB_DATABASE  | 数据库名称  | xiaoyang  |
| DB_USERNAME  | 数据库用户名  | root  |
| DB_PASSWORD  | 数据库密码  | root  |
| CACHE_DRIVER  | 缓存驱动  | redis  |
| SESSION_DRIVER  | SESSION驱动  | file  |
| QUEUE_CONNECTION  | 队列连接  | redis  |
| REDIS_HOST  | redis地址  | 127.0.0.1  |
| REDIS_PORT  | redis端口  | 6379  |
| REDIS_PASSWORD  | redis密码  |   |
....

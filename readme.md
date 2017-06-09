基于 FreeSWITCH 的录音与转码平台，支持 G729, G711 转码，并且支持多接口接入部署。

![screenshot](./script/screenshot.png)

#### 服务器环境


- PHP框架:  Yaf 2.3.5
- 操作系统: CentOS 7.2
- 数据库 :  MariaDB 5.5
- PBX系统： FreeSWITCH 1.6.15

#### 功能与特性

- 支持 G729 转码
- 实时录音查询系统
- 支持多 sofia 接口接入
- 支持网关独立拨号路由表
- 网关数据统计报表查询
- 采用 restful 接口存储话单
- 采用 mod_json_cdr 模块推送话单


#### 相关目录说明:
```
cdr             通话记录 API 接口，安装位置 /var/cdr
www             后台 Web 管理系统，安装位置 /var/www
config          Nginx、PHP 、MySQL 和 FreeSWITCH 的配置文件
script          MySQL 数据库表 SQL 文件
package         Yaf 框架以及 G729 模块相关软件包
```

#### 默认 Web 后台账号/密码:
```
Account : admin
Password: pbxmon
```

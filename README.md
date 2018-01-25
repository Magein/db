### 数据库操作类
    1. 仅支持mysql驱动(mysql、mariadb)
    2. 初级版，不要用在生产环境
     
    Connect.php 为数据库连接类，不在其里面设置其他属性，如实际项目数据库中的表前缀，
    
### 更新日志
    
    18-1-25：
        1. 优化cennect连接配置，确保参数不会缺少
        2. 优化insert update delete 执行成功返回值
        3. 修复insert数据中携带单引号导致sql语句执行失败    
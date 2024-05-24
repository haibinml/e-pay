## 彩虹易支付同步更新
[彩虹易支付官方文档](https://www.kancloud.cn/net909/epay/2590520)

安装项目到当前目录
```
git clone https://github.com/mrlihx/epay.git ./
```
国内服务器用
```
git clone https://ghproxy.com/https://github.com/mrlihx/epay.git ./
```
更新项目
```
git pull
```

### Nginx 伪静态
```
location / {
 if (!-e $request_filename) {
   rewrite ^/(.[a-zA-Z0-9\-\_]+).html$ /index.php?mod=$1 last;
 }
 rewrite ^/pay/(.*)$ /pay.php?s=$1 last;
}
location ^~ /plugins {
  deny all;
}
location ^~ /includes {
  deny all;
}
```






# epay_usdt

---   

## 介绍

一款适用于原版彩虹易支付的USDT（TRC20）收款插件，收到的货币直接到自己钱包，不经过任何第三方。

本源码仅供个人学习研究所用，任何人或组织用作他用，产生的任何后果 责任自负。

## 使用方法

本插件部分资源依赖于海外服务，在中国大陆内使用可能会产生一些意外的错误，请注意。

1. 新增一种支付方式，调用值必须设置为`usdt`，显示名称无所谓，支持设备选`PC+Mobile`
2. 将本项目直接打包下载，随后解压得到一个文件夹，再将文件夹重命名为`usdt`；然后将整个文件夹上传到易支付网站的`plugins`目录。
3. 登录易支付后台，刷新支付插件之后便能看到一个`USDT 收款插件`；随后便能按照正常流程添加支付通道。  
   **配置密钥解释：**
   ```
    # USDT-TRC20 收款地址
        收款的TRON钱包地址
    # 交易汇率（CNY）
        人民币和USDT-TRC20的兑换汇率，如果填的是AUTO，将自动从网站（coinmarketcap.com）获取市场汇率；如果需要自定义，请填入一个大于0的浮点数。
    # 超时时长（秒）
        订单支付的最大时长，推荐填1200（20分钟），该数字过小过大都会存在问题。 
    ```
4. 添加回调监控(宝塔环境)
    - 拿到服务器php的执行路径，不出意外的话都是`/usr/bin/php`
    - 拿到插件文件`cron.php`对于服务器的绝对路径，比如我的是：`/www/wwwroot/epay.xxxxxx.com/plugins/usdt/cron.php`
    - 需要监控的通道ID，比如我的是`1`，在支付后台支付通道可见。
    - 将三个数据使用空格连起来，得到完整的监控脚本：
       ```bash
       /usr/bin/php /www/wwwroot/epay.xxxxxx.com/plugins/usdt/cron.php 1
       ```
    - 宝塔计划任务，添加一个shell脚本，执行周期为`1分钟`，脚本内容便是上面的监控脚本，添加即可。
    - 手动执行一次，如果日志没有发现报错则运行正常。

---  




插件拉卡拉免输-基础版，是在原拉卡拉免输版插件基础上修复了一些问题


登录易支付后台，刷新支付插件之后便能看到一个拉卡拉免输-基础版收款插件；支持微信支付、支付宝支付、银联支付。随后便能按照正常流程添加支付通道。

相关配置信息可前往 https://lakala.vercel.app 登陆拉卡拉账号免抓包获取

域名被🚧, 自行挂🪜或反向代理后访问

配置密钥解释：
店铺名：随便填写
商户号：拉卡拉下发的商户号, 822开头
终端号/设备号：拉卡拉提供的设备号, 大写字母+7位数字
授权密钥：32位授权密钥

添加回调监控(Linux宝塔环境)【Windows宝塔环境还需修改路径】
拿到服务器php的执行路径，不出意外的话都是/usr/bin/php
拿到插件文件cron.php对于服务器的绝对路径，比如我的是：/www/wwwroot/epay.xxxxxx.com/plugins/lklcode/cron.php
需要监控的通道ID，比如我的是1，在支付后台支付通道可见。
将三个数据使用空格连起来，得到完整的监控脚本：
    bash /www/wwwroot/epay.xxxxxx.com/aks.sh -t 5 -m “ /www/server/php/74/bin/php /www/wwwroot/epay.xxxxxx.com/plugins/lklcode/cron.php 1 “
宝塔计划任务，添加一个shell脚本，执行周期为1分钟，脚本内容便是上面的监控脚本，添加即可。
手动执行一次，如果日志没有发现报错则运行正常。


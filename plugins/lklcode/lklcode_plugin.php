<?php

class lklcode_plugin
{
  static public $info = [
    'name' => 'lklcode', //支付插件英文名称，需和目录名称一致，不能有重复
    'showname' => '拉卡拉免输-基础版', //支付插件显示名称
    'author' => '', //支付插件作者
    'link' => '', //支付插件作者链接
    'types' => ['alipay', 'wxpay', 'bank'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
    'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
      'appurl' => [
        'name' => '店铺名',
        'type' => 'input',
        'note' => '收款时收银台显示的店铺名(自定义)',
      ],
      'appid' => [
        'name' => '商户号',
        'type' => 'input',
        'note' => '拉卡拉下发的商户号',
      ],
      'appmchid' => [
        'name' => '终端号',
        'type' => 'input',
        'note' => '拉卡拉下发的终端号',
      ],
      'appkey' => [
        'name' => '鉴权密钥',
        'type' => 'input',
        'note' => '拉卡拉账号授权密钥, 一段时间后会过期失效, 高级版插件可自动更新',
      ],
    ],
    'select' => null,
    'note' => '<p>相关配置信息可前往<a href="https://lakala.vercel.app" target="_blank">拉卡拉 - 支付插件</a>登陆拉卡拉账号免抓包获取</p>', //支付密钥填写说明
    'bindwxmp' => false, //是否支持绑定微信公众号
    'bindwxa' => false, //是否支持绑定微信小程序
  ];

  public static function submit()
  {
    global $siteurl, $channel, $order, $ordername, $sitename, $conf;

    return ['type' => 'jump', 'url' => '/pay/' . $order['typename'] . '/' . TRADE_NO . '/?sitename=' . $sitename];
  }

  public static function mapi()
  {
    global $siteurl, $channel, $order, $conf, $device, $mdevice;

    $typename = $order['typename'];
    return self::$typename();
  }

  //支付宝下单
  public static function alipay()
  {
    try {
      $code_url = self::qrcode();
    } catch (Exception $ex) {
      return ['type' => 'error', 'msg' => '支付宝支付下单失败！' . $ex->getMessage()];
    }

    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
      return ['type' => 'page', 'page' => 'wxopen'];
    } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient') !== false) {
      return ['type' => 'qrcode', 'page' => 'alipay_wap', 'url' => $code_url];
    } elseif (self::isMobile() && !isset($_GET['qrcode'])) {
      return ['type' => 'qrcode', 'page' => 'alipay_qrcode', 'url' => $code_url];
    } else {
      return ['type' => 'qrcode', 'page' => 'alipay_qrcode', 'url' => $code_url];
    }
  }

  //微信下单
  public static function wxpay()
  {
    try {
      $code_url = self::qrcode();
    } catch (Exception $ex) {
      return ['type' => 'error', 'msg' => '微信支付下单失败！' . $ex->getMessage()];
    }

    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
      return ['type' => 'jump', 'url' => $code_url];
    } elseif (self::isMobile() && !isset($_GET['qrcode'])) {
      return ['type' => 'qrcode', 'page' => 'wxpay_wap', 'url' => $code_url];
    } else {
      return ['type' => 'qrcode', 'page' => 'wxpay_qrcode', 'url' => $code_url];
    }
  }

  //云闪付下单
  public static function bank()
  {
    try {
      $code_url = self::qrcode();
    } catch (Exception $ex) {
      return ['type' => 'error', 'msg' => '云闪付下单失败！' . $ex->getMessage()];
    }

    return ['type' => 'qrcode', 'page' => 'bank_qrcode', 'url' => $code_url];
  }


  static public function qrcode()
  {
    global $siteurl, $channel, $order, $ordername, $sitename, $conf;

    // 订单发起接口
    $appname = $channel['appurl'];
    $apiurl = 'https://wallet.lakala.com/m/a/code/generate';
    // 当前时间
    $atime = time();
    // 订单过期时间
    $btime = '180';
    // 格式化当前时间
    $dtime = date("YmdHis", $atime);

    // 构造订单发起数据
    $data = array(
      "reqData" => array(
        "shopNo" => $channel['appid'],
        "termNo" => $channel['appmchid'],
        "shopName" => $channel['appurl'],
        "type" => "MICROCODE",
        "expireTime" => $btime,
        "orderField" => array(
          "amount" => $order['realmoney'] * 100,
          "exterMerOrderNo" => "",
          "exterOrderSource" => "",
          "subject" => "",
          "description" => "",
          "orderRemark" => TRADE_NO
        ),
        "txnField" => array(
          "outTradeNo" => TRADE_NO,
          "operatorId" => "",
          "amount" => $order['realmoney'] * 100,
          "remark" => TRADE_NO
        ),
        "snAutoExpireFlag" => ""
      ),
      "ver" => "1.0.0",
      "sign" => "",
      "timestamp" => $dtime,
      "reqId" => "",
      "rnd" => ""
    );

    $body = json_encode($data);

    // 设置请求头
    $headers = array(
      "Authorization: " . $channel['appkey'],
      "Content-Length: " . strlen($body),
      "Content-Type: application/json;charset=utf-8",
      "X-Client-PV: lKL_APP",
      "User-Agent: okhttp/4.9.2",
      "X-FORWARDED-FOR: 110.75.139.5",
      "CLIENT-IP: 110.75.139.5",
      "Host: wallet.lakala.com"
    );

    // 向上游发起订单创建请求
    $ch = curl_init($apiurl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);

    if (!empty($response)) {
      $response_data = json_decode($response, true);
      if ($response_data['retCode'] === "000000") {
        if (isset($response_data['respData']['url'])) {
          $skm_url = $response_data['respData']['url'];
        } else {
          throw new Exception('未返回收款链接');
        }
      } else {
        throw new Exception('[' . $response_data['retCode'] . '] 异常错误: ' . $response_data['retMsg']);
      }
    } else {
      throw new Exception('上游订单无法创建');
    }

    return $skm_url;
  }

  // 状态监控-计划任务版
  static public function btjk($payid, array $channel)
  {
    global $DB;
    //当前时间
    $atime = time();
    // 格式化当前时间
    $dtime = date("YmdHis", $atime);
    $ch = curl_init();

    $data = [
      "outOrgCode" => "37001010012",
      "outSysCode" => "MOBILE_PLATFORM",
      "reqTime" => $dtime,
      "version" => "3.0",
      "signType" => null,
      "sign" => null,
      "reqData" => [
        "merchantNo" => $channel['appid'],
        "termNo" => $channel['appmchid'],
        "outTradeNo" => $payid,
        "tradeNo" => null,
        "outOrderNo" => null,
        "outOrderSource" => null
      ]
    ];

    $body = json_encode($data);

    $headers = [
      "Authorization: " . $channel['appkey'],
      "Content-Length: " . strlen($body),
      "Content-Type: application/json;charset=utf-8",
      "User-Agent: okhttp/4.9.2",
      "X-Client-PV: lKL_APP",
      "X-FORWARDED-FOR: 110.75.139.5",
      "CLIENT-IP: 110.75.139.5",
      "Host: wallet.lakala.com"
    ];

    curl_setopt_array($ch, [
      CURLOPT_URL => "https://wallet.lakala.com/m/a/transv3/query",
      CURLOPT_POST => 1,
      CURLOPT_POSTFIELDS => $body,
      CURLOPT_HTTPHEADER => $headers,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_SSL_VERIFYPEER => false
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    if (empty($response)) {
      return "异常错误，请联系开发者";
    }

    // 处理返回响应
    $response_data = json_decode($response, true);
    if ($response_data['code'] === "BBS00000") {
      if ($response_data['respData']['tradeStateDesc'] === "交易成功") {
        $order = $DB->getRow('select * from pre_order where trade_no = ? limit 1', [$payid]);
        if (empty($order)) {
          return "订单" . $payid . "不存在或已过期";
        }
        $payid = daddslashes($payid);
        processNotify($order, $payid);
        return "订单" . $payid . "已完成";
      } else {
        return "订单状态复查中";
      }
    } elseif ($response_data['code'] === "BBS11114") {
      return "订单未支付";
    } else {
      return "异常错误";
    }
  }

  // 检测是否为手机端
  static private function isMobile()
  {
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    $mobileAgents = [
      'Mobile',
      'Android',
      'iPhone',
      'iPad',
      'Windows Phone',
      'BlackBerry',
      'Nokia',
      'Sony',
      'Symbian',
      'Opera Mini',
    ];
    foreach ($mobileAgents as $agent) {
      if (strpos($userAgent, $agent) !== false) {
        return true;
      }
    }
    return false;
  }


  //同步回调
  static public function return()
  {
    global $channel, $order;

    $payid = $_GET['payid']; //商户订单号
    $sign = $_GET['sign']; //校验签名，计算方式 = md5（payid+appkey）

    if (!$payid || !$sign)
      return ['type' => 'error', 'data' => '参数不完整'];

    $_sign = md5($payid . $channel['appkey']);
    if ($_sign !== $sign)
      return ['type' => 'error', 'data' => '签名校验失败'];

    $out_trade_no = daddslashes($payid);
    processReturn($order, $out_trade_no);
  }

}  ];

  static public function submit()
  {
    global $siteurl, $channel, $order, $ordername, $sitename, $conf;

    // 订单发起接口
    $appname = $channel['appurl'];
    $apiurl = 'https://wallet.lakala.com/m/a/code/generate';
    // 当前时间
    $atime = time();
    // 订单过期时间
    $btime = '180';
    // 格式化当前时间
    $dtime = date("YmdHis", $atime);

    // 构造订单发起数据
    $data = array(
      "reqData" => array(
        "shopNo" => $channel['appid'],
        "termNo" => $channel['appmchid'],
        "shopName" => $channel['appurl'],
        "type" => "MICROCODE",
        "expireTime" => $btime,
        "orderField" => array(
          "amount" => $order['realmoney'] * 100,
          "exterMerOrderNo" => "",
          "exterOrderSource" => "",
          "subject" => "",
          "description" => "",
          "orderRemark" => TRADE_NO
        ),
        "txnField" => array(
          "outTradeNo" => TRADE_NO,
          "operatorId" => "",
          "amount" => $order['realmoney'] * 100,
          "remark" => TRADE_NO
        ),
        "snAutoExpireFlag" => ""
      ),
      "ver" => "1.0.0",
      "sign" => "",
      "timestamp" => $dtime,
      "reqId" => "",
      "rnd" => ""
    );

    $body = json_encode($data);

    // 设置请求头
    $headers = array(
      "Authorization: " . $channel['appkey'],
      "Content-Length: " . strlen($body),
      "Content-Type: application/json;charset=utf-8",
      "X-Client-PV: lKL_APP",
      "User-Agent: okhttp/4.9.2",
      "X-FORWARDED-FOR: 110.75.139.5",
      "CLIENT-IP: 110.75.139.5",
      "Host: wallet.lakala.com"
    );

    // 向上游发起订单创建请求
    $ch = curl_init($apiurl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);

    if (empty($response)) {
      echo "上游接口无响应，请重试或联系管理员";
      exit;
    }

    // 处理返回响应
    $response_data = json_decode($response, true);
    if ($response_data['retCode'] === "000000") {
      $skmurl = $response_data['respData']['url'];
      if ($order['typename'] == "alipay") {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
          return ['type' => 'page', 'page' => 'wxopen'];
        } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient') !== false) {
          return ['type' => 'qrcode', 'page' => 'alipay_wap', 'url' => $skmurl];
        } else {
          if (self::isMobile()) {
            return ['type' => 'qrcode', 'page' => 'alipay_qrcode', 'url' => $skmurl];
          } else {
            return ['type' => 'qrcode', 'page' => 'alipay_qrcode', 'url' => $skmurl];
          }
        }
      } else if ($order['typename'] == "wxpay") {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
          return ['type' => 'scheme', 'page' => 'wxpay_mini', 'url' => $skmurl];
        }
        if (self::isMobile()) {
          return ['type' => 'qrcode', 'page' => 'wxpay_wap', 'url' => $skmurl];
        } else {
          return ['type' => 'qrcode', 'page' => 'wxpay_qrcode', 'url' => $skmurl];
        }
      } else {
        return ['type' => 'qrcode', 'page' => 'bank_qrcode', 'url' => $skmurl];
      }
    } else {
      echo "异常错误" . $response_data['retCode'] . "：" . $response_data['retMsg'];
      exit;
    }
  }

  // 状态监控-计划任务版
  static public function btjk($payid, array $channel)
  {
    global $DB;
    //当前时间
    $atime = time();
    // 格式化当前时间
    $dtime = date("YmdHis", $atime);
    $ch = curl_init();

    $data = [
      "outOrgCode" => "37001010012",
      "outSysCode" => "MOBILE_PLATFORM",
      "reqTime" => $dtime,
      "version" => "3.0",
      "signType" => null,
      "sign" => null,
      "reqData" => [
        "merchantNo" => $channel['appid'],
        "termNo" => $channel['appmchid'],
        "outTradeNo" => $payid,
        "tradeNo" => null,
        "outOrderNo" => null,
        "outOrderSource" => null
      ]
    ];

    $body = json_encode($data);

    $headers = [
      "Authorization: " . $channel['appkey'],
      "Content-Length: " . strlen($body),
      "Content-Type: application/json;charset=utf-8",
      "User-Agent: okhttp/4.9.2",
      "X-Client-PV: lKL_APP",
      "X-FORWARDED-FOR: 110.75.139.5",
      "CLIENT-IP: 110.75.139.5",
      "Host: wallet.lakala.com"
    ];

    curl_setopt_array($ch, [
      CURLOPT_URL => "https://wallet.lakala.com/m/a/transv3/query",
      CURLOPT_POST => 1,
      CURLOPT_POSTFIELDS => $body,
      CURLOPT_HTTPHEADER => $headers,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_SSL_VERIFYPEER => false
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    if (empty($response)) {
      return "异常错误，请联系开发者";
    }

    // 处理返回响应
    $response_data = json_decode($response, true);
    if ($response_data['code'] === "BBS00000") {
      if ($response_data['respData']['tradeStateDesc'] === "交易成功") {
        $order = $DB->getRow('select * from pre_order where trade_no = ? limit 1', [$payid]);
        if (empty($order)) {
          return "订单" . $payid . "不存在或已过期";
        }
        $payid = daddslashes($payid);
        processNotify($order, $payid);
        return "订单" . $payid . "已完成";
      } else {
        return "订单状态复查中";
      }
    } elseif ($response_data['code'] === "BBS11114") {
      return "订单未支付";
    } else {
      return "异常错误";
    }
  }

  // 检测是否为手机端
  static private function isMobile()
  {
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    $mobileAgents = [
      'Mobile',
      'Android',
      'iPhone',
      'iPad',
      'Windows Phone',
      'BlackBerry',
      'Nokia',
      'Sony',
      'Symbian',
      'Opera Mini',
    ];
    foreach ($mobileAgents as $agent) {
      if (strpos($userAgent, $agent) !== false) {
        return true;
      }
    }
    return false;
  }


  //同步回调
  static public function return ()
  {
    global $channel, $order;

    $payid = $_GET['payid']; //商户订单号
    $sign = $_GET['sign']; //校验签名，计算方式 = md5（payid+appkey）

    if (!$payid || !$sign)
      return ['type' => 'error', 'data' => '参数不完整'];

    $_sign = md5($payid . $channel['appkey']);
    if ($_sign !== $sign)
      return ['type' => 'error', 'data' => '签名校验失败'];

    $out_trade_no = daddslashes($payid);
    processReturn($order, $out_trade_no);
  }

}

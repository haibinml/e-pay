<?php
define('CURR_PATH', dirname(__DIR__));
require CURR_PATH . '/../includes/common.php';
require CURR_PATH . '/lklcode/lklcode_plugin.php';

if (function_exists("set_time_limit")) {
    @set_time_limit(0);
}

$id      = intval($argv[1]);
$channel = $DB->getRow('SELECT * FROM pre_channel WHERE id = ? LIMIT 1', [$id]);
if (!$channel) {
    exit("错误：该支付通道不存在\n");
}
if ($channel['plugin'] != 'lklcode') {
    exit("错误：该支付通道不可监控\n");
}

$sotime = date('Y-m-d H:i:s');

// 检索订单数据
$query = "SELECT trade_no FROM pre_order WHERE addtime >= DATE_SUB('{$sotime}', INTERVAL 5 MINUTE) AND status = 0 AND channel = '{$id}'";
$result = $DB->query($query);
$orders = $result->fetchAll(PDO::FETCH_ASSOC);

if (empty($orders)) {
    echo "当前暂无须监控订单\n";
} else {
    foreach ($orders as $order) {
        $tradeNo = $order['trade_no'];
        $result = lklcode_plugin::btjk($tradeNo, $channel);
        echo $result . "\n";
    }
}

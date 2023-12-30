<?php
include("../includes/common.php");
if($islogin2==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
$title='邀请好友';
include './head.php';
?>
<style>
    .fixed-table-toolbar,.fixed-table-pagination{padding: 15px;}
</style>
<div id="content" class="app-content" role="main">
    <div class="app-content-body ">

        <div class="bg-light lter b-b wrapper-md hidden-print">
            <h1 class="m-n font-thin h3">邀请好友</h1>
        </div>

        <div class="wrapper-md control">
            <?php if(isset($msg)){?>
                <div class="alert alert-info">
                    <?php echo $msg?>
                </div>
            <?php }?>
            <?php if($userrow['aff']!=1){?>
                <div class="alert alert-info">
                    <?php echo "没有邀请权限。"?>
                </div>
                <?php exit(); } ?>
            <div class="col-md-2">
                <div class="panel panel-default">
                    <?php
                    $lists = $DB->getAll("SELECT * FROM pre_user WHERE `ref_uid`='$uid'");
                    $total_money = 0;
                    foreach ($lists as $list){
                        $uuid = $list['uid'];
                        $records = $DB->getAll("SELECT * FROM pre_record WHERE `trade_no`='$uuid' AND `type`='下级分成'");
                        $total_umoney = 0;
                        foreach ($records as $record){
                            if ($record['action'] == 1){
                                $total_umoney += $record['money'];
                            }else if ($record['action'] == 2){
                                $total_umoney -= $record['money'];
                            }
                        }
                        $total_money += $total_umoney;
                        $table_list .= "<tr><td>".$uuid."</td><td>".round($total_umoney, 2). "</td></tr>";
                    }
                    ?>
                    <div class="panel-heading font-bold">
                        <h3 class="panel-title">总佣金：<?php echo round($total_money, 2) ?></h3>
                    </div>
                    <table class="table table-striped">
                        <tr>
                            <th>下级商户</th>
                            <th>佣金</th>
                        </tr>
                        <?php echo $table_list ?>
                    </table>

                </div>
            </div>
            <div class="col-md-10">
                <div class="panel panel-default">
                    <div class="panel-heading font-bold">
                        <h3 class="panel-title">订单分成明细</h3>
                    </div>
                    <form onsubmit="return searchSubmit()" method="GET" class="form-inline" id="searchToolbar">
                        <div class="alert alert-success text-md">
                            <p><?php echo "订单分成比例：".($conf['commission_rate']*100)."%，邀请地址：".$siteurl."user/reg.php?ref=".$uid ?></p>
                        </div>
                        <div class="form-group" style="display: none;">
                            <select class="form-control" name="type">
                                <option value="1">操作类型</option>
                                <option value="2">变更金额</option>
                                <option value="3">关联订单号</option>
                            </select>
                        </div>
                        <div class="form-group" id="searchword" style="display: none;">
                            <input type="text" class="form-control" name="kw" placeholder="搜索内容" style="min-width: 300px;">
                        </div>
                        <div class="form-group" style="display: none;">
                            <button class="btn btn-primary" type="submit"><i class="fa fa-search"></i> 搜索</button>
                            <a href="javascript:searchClear()" class="btn btn-default"><i class="fa fa-refresh"></i> 重置</a>
                        </div>
                    </form>
                    <table id="listTable">
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'foot.php';?>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.min.js"></script>
<script src="<?php echo $cdnpublic?>bootstrap-table/1.20.2/bootstrap-table.min.js"></script>
<script src="<?php echo $cdnpublic?>bootstrap-table/1.20.2/extensions/page-jump-to/bootstrap-table-page-jump-to.min.js"></script>
<script src="../assets/js/custom.js"></script>
<script>
    $(document).ready(function(){
        updateToolbar();
        const defaultPageSize = 30;
        const pageNumber = typeof window.$_GET['pageNumber'] != 'undefined' ? parseInt(window.$_GET['pageNumber']) : 1;
        const pageSize = typeof window.$_GET['pageSize'] != 'undefined' ? parseInt(window.$_GET['pageSize']) : defaultPageSize;

        $("#listTable").bootstrapTable({
            url: 'ajax2.php?act=affrecordList',
            pageNumber: pageNumber,
            pageSize: pageSize,
            kw: "下级分成",
            classes: 'table table-striped table-hover table-bordered',
            columns: [
                {
                    field: 'trade_no',
                    title: '下级商户号',
                    formatter: function(value, row, index) {
                        return value?'<a href="./affrecord.php?type=3&kw='+value+'">'+value+'</a>':'无';
                    }
                },
                {
                    field: 'money',
                    title: '金额',
                    formatter: function(value, row, index) {
                        return (row.action==2?'- ':'+ ')+value;
                    }
                },
                {
                    field: 'oldmoney',
                    title: '变更前金额'
                },
                {
                    field: 'newmoney',
                    title: '变更后金额'
                },
                {
                    field: 'date',
                    title: '时间'
                },

            ],
        })
    })
</script>
define(['jquery', 'bootstrap', 'backend', 'table', 'form','upload'], function ($, undefined, Backend, Table, Form,Upload) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                searchFormVisible: true,
                extend: {
                    index_url: 'finance/supply_settle/index' + location.search,
                    // detail_url: 'finance/supply_settle/detail',
                    table: 'wholesale_customer',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'email', title: __('供应商名称')},
                        {field: 'email', title: __('供应商ID'),visible:false},
                        {field: 'id', title: __('供应商账期'),},
                        {field: 'customer_name', title: __('本期待结算金额'),operate:false},
                        {field: 'customer_name', title: __('总待结算金额'),operate:false},
                        {field: 'customer_name', title: __('采购负责人')},
                        {field: 'status', title: __('状态'),custom: { 1: 'danger', 2: 'success', 3: 'orange', 4: 'warning', 5: 'purple', 6: 'primary' , 7: 'primary'}, searchList: { 1: '新建', 2: '待审核', 3: '待付款', 4: '待上传发票', 5: '已完成',6:'已拒绝' ,7:'已取消'},formatter: Table.api.formatter.status},
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, buttons: [
                                {
                                    name: 'edit',
                                    text: '',
                                    title: __('Edit'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-pencil',
                                    url: 'finance/pay_order/edit',
                                    extend: 'data-area = \'["80%","70%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        if (Config.customer_edit == 1) {//有权限 或者创建人为当前人
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                },
                                 {
                                  name: 'log',
                                  text: '结算记录',
                                  title: __('结算记录'),
                                  extend: 'data-area = \'["80%","70%"]\'',
                                  classname: 'btn btn-xs btn-primary btn-dialog',
                                  icon: 'fa fa-list',
                                  url: 'finance/pay_order/detail',
                                  callback: function (data) {
                                      Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                  },
                                  visible: function (row) {
                                      //返回true时按钮显示,返回false隐藏
                                      return true;
                                  }
                              },
                                ],
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            // 导入按钮事件
            Upload.api.plupload($('.btn-import'), function (data, ret) {
                Fast.api.ajax({
                    url: 'customer/wholesale_customer/import',
                    data: { file: data.url },
                }, function (data, ret) {
                    layer.msg('导入成功！！', { time: 3000, icon: 6 }, function () {
                        location.reload();
                    });

                });
            });


            //批量导出xls
            $('.btn-batch-export-xls').click(function () {
                var ids = Table.api.selectedids(table);
                if (ids.length > 0) {
                    window.open(Config.moduleurl + '/customer/wholesale_customer/batch_export_xls?ids=' + ids, '_blank');
                } else {
                    var options = table.bootstrapTable('getOptions');
                    var search = options.queryParams({});
                    var filter = search.filter;
                    var op = search.op;
                    window.open(Config.moduleurl + '/customer/wholesale_customer/batch_export_xls?filter=' + filter + '&op=' + op, '_blank');
                }

            });
        },
        detail: function () {
            // 初始化表格参数配置
            Table.api.init();
            
            //绑定事件
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var panel = $($(this).attr("href"));
                if (panel.size() > 0) {
                    Controller.table[panel.attr("id")].call(this);
                    $(this).on('click', function (e) {
                        $($(this).attr("href")).find(".btn-refresh").trigger("click");
                    });
                }
                //移除绑定的事件
                $(this).unbind('shown.bs.tab');
            });
            
            //必须默认触发shown.bs.tab事件
            $('ul.nav-tabs li.active a[data-toggle="tab"]').trigger("shown.bs.tab");
        },
        table: {
            first: function () {
                // 表格1
                var table1 = $("#table1");
                table1.bootstrapTable({
                    url: 'finance/supply_settle/table1',
                    toolbar: '#toolbar1',
                    sortName: 'id',
                    search: false,
                    columns: [
                        [
                            {checkbox: true, },
                            {field: 'id', title: '采购单号'},
                            {field: 'id', title: '采购单名称'},
                            {field: 'id', title: '付款类型'},
                            {field: 'id', title: '采购批次'},
                            {field: 'imagewidth', title: __('采购批次数量')},
                            {field: 'imageheight', title: __('采购单价')},
                            {field: 'mimetype', title: __('采购金额')},
                            {field: 'mimetype', title: __('预付金额')},
                            {field: 'mimetype', title: __('已支付金额')},
                            {field: 'mimetype', title: __('待结算金额')},
                            {field: 'mimetype', title: __('运费')},
                            {field: 'mimetype', title: __('入库数量')},
                            {field: 'mimetype', title: __('入库金额')},
                            {field: 'mimetype', title: __('退货数量')},
                            {field: 'mimetype', title: __('退货金额')},
                            {field: 'mimetype', title: __('结算周期')},
                        ]
                    ]
                });

                // 为表格1绑定事件
                Table.api.bindevent(table1);
            },
            second: function () {
                // 表格2
                var table2 = $("#table2");
                table2.bootstrapTable({
                    url: 'finance/supply_settle/table2',
                    extend: {
                        index_url: '',
                        add_url: '',
                        edit_url: '',
                        del_url: '',
                        multi_url: '',
                        table: '',
                    },
                    toolbar: '#toolbar2',
                    sortName: 'id',
                    search: false,
                    columns: [
                        [
                            {field: 'id', title: '结算单号'},
                            {field: 'title', title: __('供应商')},
                            {field: 'url', title: __('结算金额')},
                            {field: 'ip', title: __('结算账期时间')},
                            {field: 'status', title: __('状态'),custom: { 1: 'danger', 2: 'success', 3: 'orange', 4: 'warning', 5: 'purple', 6: 'primary' , 7: 'primary'}, searchList: { 1: '新建', 2: '待审核', 3: '待付款', 4: '待上传发票', 5: '已完成',6:'已拒绝' ,7:'已取消'},formatter: Table.api.formatter.status},
                            {field: 'ip', title: __('结算时间')}, 
                            {
                                field: 'operate', title: __('Operate'), table: table2, events: Table.api.events.operate, buttons: [
                                    {
                                        name: 'edit',
                                        text: '',
                                        title: __('查看结算单详情'),
                                        classname: 'btn btn-xs btn-success btn-dialog',
                                        icon: 'fa fa-pencil',
                                        url: 'finance/pay_order/edit',
                                        extend: 'data-area = \'["80%","70%"]\'',
                                        callback: function (data) {
                                            Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                        },
                                        visible: function (row) {
                                            if (Config.customer_edit == 1) {//有权限 或者创建人为当前人
                                                return true;
                                            } else {
                                                return false;
                                            }
                                        }
                                    },
                                    ],
                                formatter: Table.api.formatter.operate
                            }
                        ]
                    ]
                });

                // 为表格2绑定事件
                Table.api.bindevent(table2);
            }
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
define(['jquery', 'bootstrap', 'backend', 'table', 'form','upload'], function ($, undefined, Backend, Table, Form,Upload) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                searchFormVisible: true,
                extend: {
                    index_url: 'finance/stock_parameter/index' + location.search,
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
                        {field: 'id', title: __('ID'),},
                        {field: 'createtime', title: __('日期'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'mobile', title: __('入库金额（￥）'), visible: false},
                        {field: 'customer_name', title: __('出库金额（￥）')},
                        {field: 'mobile', title: __('余额（￥）')},
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, buttons: [
                                 {
                                  name: 'detail',
                                  text: '查看详情',
                                  title: __('查看详情'),
                                  extend: 'data-area = \'["80%","70%"]\'',
                                  classname: 'btn btn-xs btn-primary btn-dialog',
                                  icon: 'fa fa-list',
                                  url: 'customer/wholesale_customer/detail',
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
        },
        detail: function () {
            // 初始化表格参数配置
            Table.api.init({
                searchFormVisible: true,
                extend: {
                    index_url: 'finance/stock_parameter/detail' + location.search,
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                searchFormVisible:false,
                showToggle: false,
                commonSearch: false,
                showExport: false,
                columns: [
                    [
                        {field: 'id', title: __('ID'),},
                        {field: 'status', title: __('出入库类型'),custom: { 1: 'danger', 2: 'success'}, searchList: { 1: '采购入库', 2: '订单出库'},formatter: Table.api.formatter.status},
                        {field: 'customer_name', title: __('入库金额（￥）')},
                        {field: 'mobile', title: __('入库数量')},
                        {field: 'mobile', title: __('出库金额（￥）')},
                        {field: 'mobile', title: __('出库数量')}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },

        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
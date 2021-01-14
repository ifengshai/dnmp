define(['jquery', 'bootstrap', 'backend', 'table', 'form','upload'], function ($, undefined, Backend, Table, Form,Upload) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                searchFormVisible: true,
                extend: {
                    index_url: 'finance/cycle_carry_order/index' + location.search,
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                showToggle: false,
                showExport: false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('ID'),operate:false},
                        {field: 'customer_name', title: __('结转单')},
                        {field: 'createtime', title: __('结转时间'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
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
                    index_url: 'finance/cycle_carry_order/detail' + location.search,
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                showToggle: false,
                showExport: false,
                columns: [
                    [
                        {field: 'id', title: __('ID'),operate:false},
                        {field: 'status', title: __('类型'),custom: { 1: 'danger'}, searchList: { 1: '订单'},formatter: Table.api.formatter.status,operate:false},
                        {field: 'mobile', title: __('订单号'),operate:false},
                        {field: 'status', title: __('平台'),custom: { 1: 'danger', 2: 'success', 3: 'blue'}, searchList: { 1: 'zeelool', 2: 'voogueme', 3: 'nihao'},formatter: Table.api.formatter.status,operate:false},
                        {field: 'customer_name', title: __('币种'),operate:false},
                        {field: 'status', title: __('订单类型'),custom: { 1: 'danger', 2: 'success'}, searchList: { 1: '普通订单', 2: '网红单'},formatter: Table.api.formatter.status,operate:false},
                        {field: 'mobile', title: __('订单总金额'),operate:false},
                        {field: 'mobile', title: __('支付金额'),operate:false},
                        {field: 'mobile', title: __('镜架成本'),operate:false},
                        {field: 'mobile', title: __('镜片成本'),operate:false},
                        {field: 'mobile', title: __('支付时间'),operate:false},
                        {field: 'mobile', title: __('结转单号'),visible:false},
                        {field: 'createtime', title: __('结转时间'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
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
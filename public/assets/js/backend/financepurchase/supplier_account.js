define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'jqui', 'bootstrap-table-jump-to'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'financepurchase/supplier_account/index',
                    add_url: 'financepurchase/supplier_account/add',
                    // edit_url: 'financepurchase/supplier_account/edit',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                // sortName: 'id',
                searchList: true,
                columns: [
                    [
                        // {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'supplier_name', title: __('供应商名称'), operate: 'LIKE'},
                        {
                            field: 'status',
                            title: __('供应商账期'),
                            custom: {0: 'success', 1: 'yellow', 2: 'blue', 3: 'danger'},
                            searchList: {0: '无账期', 1: '一个月', 2: '两个月', 3: '三个月'},
                            formatter: Table.api.formatter.status
                        },
                        {field: 'now_wait_total', title: __('本期待结算金额（¥）'), operate: false},
                        {field: 'all_wait_total', title: __('总待结算金额（¥）'), operate: false},
                        {field: 'purchase_person', title: __('采购负责人'), operate: 'LIKE'},
                        {
                            field: 'statement_status', title: __('状态'), custom: {0: 'success', 1: 'danger'},
                            searchList: {0: '本期已结算', 1: '本期未结算'},
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'detail',
                                    text: '详情',
                                    title: __('Detail'),
                                    classname: 'btn btn-xs  btn-primary  btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'financepurchase/supplier_account/detail',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                }
                            ],
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
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
                    url: 'financepurchase/supplier_account/table1',
                    searchFormVisible: true,
                    toolbar: '#toolbar1',
                    sortName: 'id',
                    search: false,
                    columns: [
                        [
                            {field: 'state', checkbox: true},
                            {field: 'id', title: 'ID'},
                            {field: 'purchase_number', title: __('采购单号')},
                            {field: 'purchase_name', title: __('采购单名称')},
                            {field: 'pay_type', title: __('付款类型'),custom: {0: 'success', 1: 'yellow', 2: 'blue', 3: 'danger'},
                                searchList: {1: '预付款', 2: '全款预付', 3: '尾款'},
                                formatter: Table.api.formatter.status},
                            {field: 'purchase_batch', title: __('采购批次')},
                            {field: 'purchase_price', title: __('采购单价')},
                            {field: 'arrival_num', title: __('采购批次数量')},
                            {field: 'wait_pay', title: __('预付金额')},
                            {field: 'now_wait_pay', title: __('已支付预付金额')},
                            {field: 'quantity_num', title: __('入库数量')},
                            {field: 'in_stock_money', title: __('入库金额')},
                            {field: 'unqualified_num', title: __('退货数量')},
                            {field: 'unqualified_num_money', title: __('退货金额')},
                            {field: 'period', title: __('结算周期')},
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
                    url: 'financepurchase/supplier_account/table2',
                    extend: {
                        index_url: '',
                        add_url: '',
                        edit_url: '',
                        del_url: '',
                        multi_url: '',
                        table: '',
                    },
                    searchFormVisible: true,
                    toolbar: '#toolbar2',
                    sortName: 'id',
                    search: false,
                    columns: [
                        [
                            {field: 'id', title: 'ID'},
                            {field: 'statement_number', title: __('结算单号')},
                            {field: 'wait_statement_total', title: __('结算金额')},
                            {field: 'account_statement', title: __('结算账期时间')},
                            {field: 'status', title: __('状态'),
                                custom: {0: 'success', 1: 'yellow', 2: 'blue', 3: 'danger'},
                                searchList: {0: '新建', 1: '待审核', 2: '审核拒绝', 3: '待对账', 4: '待财务确认', 5: '已取消', 6: '已完成'},
                                formatter: Table.api.formatter.status
                            },
                            {field: 'create_person', title: __('创建人')},
                            {field: 'create_time', title: __('创建时间')},
                            {
                                field: 'operate',
                                title: __('Operate'),
                                table: table2,
                                events: Table.api.events.operate,
                                buttons: [
                                    {
                                        name: 'detail',
                                        text: '查看结算单详情',
                                        title: __('结算单详情'),
                                        classname: 'btn btn-xs  btn-primary  btn-dialog',
                                        icon: 'fa fa-list',
                                        url: 'financepurchase/supplier_account/detail',
                                        extend: 'data-area = \'["100%","100%"]\'',
                                        callback: function (data) {
                                            Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                        },
                                        visible: function (row) {
                                            //返回true时按钮显示,返回false隐藏
                                            return true;
                                        }
                                    }
                                ],
                                formatter: Table.api.formatter.operate
                            }                        ]
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
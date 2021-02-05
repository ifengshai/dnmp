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
                searchList:true,
                commonSearch: true,
                search: false,
                searchFormVisible: true,
                showExport: false,
                showColumns: false,
                showToggle: false,
                columns: [
                    [
                        // {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'supplier_name', title: __('供应商名称'), operate: 'LIKE'},
                        {
                            field: 'period',
                            title: __('供应商账期'),
                            custom: {0: 'success', 1: 'yellow', 2: 'blue', 3: 'danger'},
                            searchList: {0: '无账期', 1: '一个月', 2: '两个月', 3: '三个月'},
                            formatter: Table.api.formatter.status
                        },
                        {field: 'now_wait_total', title: __('本期待结算金额（¥）'), operate: false},
                        {field: 'all_wait_total', title: __('总待结算金额（¥）'), operate: false},
                        {field: 'purchase_person', title: __('采购负责人'), operate: 'LIKE'},
                        {
                            field: 'statement_status', title: __('状态'), custom: {1: 'blue', 2: 'danger'},
                            searchList: {1: '本期已结算', 2: '本期未结算'},
                            formatter: Table.api.formatter.status, operate: false
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
            $(document).on('click', '.panel-heading a[data-toggle="tab"]', function () {
                var value = $(this).data('value');
                if (value == 2){
                    $('#is_show').addClass('hide');
                }else{
                    $('#is_show').removeClass('hide');
                }
            });
            var table1 = $("#table1");
            //发起结算
            $(document).on('click', ".btn-logistics", function () {
                var ids = Table.api.selectedids(table1);
                if (ids.length > 0) {
                    var url = 'financepurchase/statement/add?ids=' + ids + '&supplier_id=' + Config.supplier_id;
                    Fast.api.open(url, __('创建结算单'), {area: ['100%', '100%']});
                    return false;
                } else {
                    Layer.alert('请选择待结算的采购批次')
                }
            });
            $(document).on('click', '#submit_cancel', function () {
                Fast.api.close(); // 关闭弹窗
                parent.location.reload(); //刷新父级
            })

        },
        table: {
            first: function () {
                // 表格1
                var table1 = $("#table1");
                table1.bootstrapTable({
                    url: 'financepurchase/supplier_account/table1?supplier_id='+ Config.supplier_id,
                    searchFormVisible: true,
                    toolbar: '#toolbar1',
                    sortName: 'a.id',
                    searchList:true,
                    commonSearch: true,
                    search: false,
                    showExport: false,
                    showColumns: false,
                    showToggle: false,
                    columns: [
                        [
                            {field: 'state', checkbox: true},
                            {field: 'id', title: 'ID', operate: false},
                            {field: 'purchase_number', title: __('采购单号'), operate: false},
                            {field: 'purchase_name', title: __('采购单名称'), operate: false},
                            {
                                field: 'pay_type',
                                title: __('付款类型'),
                                custom: {0: 'success', 1: 'yellow', 2: 'blue', 3: 'danger'},
                                searchList: {1: '预付款', 2: '全款预付', 3: '尾款'},
                                formatter: Table.api.formatter.status, operate: false
                            },
                            {field: 'purchase_batch', title: __('采购批次'), operate: false},
                            {field: 'purchase_price', title: __('采购单价'), operate: false},
                            {field: 'arrival_num', title: __('采购批次数量'), operate: false},
                            {field: 'wait_pay', title: __('预付金额'), operate: false},
                            {field: 'now_wait_pay', title: __('已支付预付金额'), operate: false},
                            {field: 'quantity_num', title: __('入库数量'), operate: false},
                            {field: 'purchase_freight', title: __('运费'), operate: false},
                            {field: 'in_stock_money', title: __('入库金额'), operate: false},
                            {field: 'unqualified_num', title: __('退货数量'), operate: false},
                            {field: 'unqualified_num_money', title: __('退货金额'), operate: false},
                            {field: 'period', title: __('结算周期'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange'},
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
                    url: 'financepurchase/supplier_account/table2?supplier_id='+ Config.supplier_id,
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
                    searchList:true,
                    commonSearch: true,
                    search: false,
                    searchFormVisible: true,
                    showExport: false,
                    showColumns: false,
                    showToggle: false,
                    columns: [
                        [
                            {field: 'id', title: 'ID', operate: false},
                            {field: 'statement_number', title: __('结算单号'), operate: false},
                            {field: 'wait_statement_total', title: __('结算金额'), operate: false},
                            {field: 'account_statement', title: __('结算账期时间'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange'},
                            {
                                field: 'status', title: __('状态'),
                                custom: {0: 'success', 1: 'yellow', 2: 'blue', 3: 'danger'},
                                searchList: {0: '新建', 1: '待审核', 2: '审核拒绝', 3: '待对账', 4: '待财务确认', 5: '已取消', 6: '已完成'},
                                formatter: Table.api.formatter.status, operate: false
                            },
                            {field: 'create_person', title: __('创建人'), operate: false},
                            {field: 'create_time', title: __('创建时间'), operate: false,formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                            {
                                field: 'operate',
                                title: __('Operate'),
                                table: table2,
                                events: Table.api.events.operate,
                                buttons: [
                                     {
                                        name: 'detail',
                                        text: '查看结算单详情',
                                        title: __('查看结算单详情'),
                                        classname: 'btn btn-xs  btn-primary  btn-dialog',
                                        icon: 'fa fa-list',
                                        url: 'financepurchase/statement/detail',
                                        extend: 'data-area = \'["100%","100%"]\'',
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
                            }]
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
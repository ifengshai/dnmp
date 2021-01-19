define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
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
                columns: [
                    [
                        // {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'supplier_name', title: __('供应商名称'), operate: 'LIKE'},
                        {
                            field: 'status', title: __('供应商账期'), custom: { 0: 'success', 1: 'yellow', 2: 'blue', 3: 'danger'},
                            searchList: { 0: '无账期', 1: '一个月', 2: '两个月', 3: '三个月'},
                            formatter: Table.api.formatter.status
                        },
                        {field: 'now_wait_total', title: __('本期待结算金额（¥）'),operate:false},
                        {field: 'all_wait_total', title: __('总待结算金额（¥）'),operate:false},
                        {field: 'purchase_person', title: __('采购负责人'), operate: 'LIKE'},
                        {
                            field: 'statement_status', title: __('状态'), custom: { 0: 'success', 1: 'danger'},
                            searchList: { 0: '本期已结算', 1: '本期未结算'},
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, buttons: [
                                {
                                    name: 'detail',
                                    text: '详情',
                                    title: __('Detail'),
                                    classname: 'btn btn-xs  btn-primary  btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'financepurchase/purchase_pay/detail',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                }
                            ], formatter: Table.api.formatter.operate
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
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
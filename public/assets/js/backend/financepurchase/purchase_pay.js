define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'financepurchase/purchase_pay/index',
                    add_url: 'financepurchase/purchase_pay/add',
                    edit_url: 'financepurchase/purchase_pay/edit',
                    // del_url: 'user/user/del',
                    // multi_url: 'user/user/multi',
                    // table: 'user',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                // sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'order_number', title: __('付款申请单号'), operate: 'LIKE'},
                        {field: 'supplier_name', title: __('供应商名称'), operate: 'LIKE'},
                        {
                            field: 'pay_type', title: __('付款类型'), custom: { 0: 'success', 1: 'yellow', 2: 'blue', 3: 'danger'},
                            searchList: { 0: '新建', 1: '预付款', 2: '全款预付', 3: '尾款'},
                            formatter: Table.api.formatter.status
                        },
                        {field: 'pay_grand_total', title: __('付款金额（￥）'), visible: false},
                        {field: 'base_currency_code', title: __('付款币种'), visible: false},
                        {
                            field: 'status', title: __('状态'), custom: { 0: 'success', 1: 'yellow', 2: 'blue', 3: 'danger'},
                            searchList: { 0: '新建', 1: '待审核', 2: '审核通过', 3: '已完成'},
                            formatter: Table.api.formatter.status
                        },
                        {field: 'create_person', title: __('创建人'), operate: 'LIKE'},
                        {field: 'create_time', title: __('创建时间'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
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
                                },
                                {
                                    name: 'edit',
                                    text: '编辑',
                                    title: __('Edit'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-pencil',
                                    url: 'financepurchase/purchase_pay/edit',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        // alert(row.status)
                                        //返回true时按钮显示,返回false隐藏
                                        if (row.status == 0) {
                                            return true;
                                        } else {
                                            return false;
                                        }
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
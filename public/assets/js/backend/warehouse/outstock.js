define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                searchFormVisible: true,
                extend: {
                    index_url: 'warehouse/outstock/index' + location.search,
                    add_url: 'warehouse/outstock/add',
                    edit_url: 'warehouse/outstock/edit',
                    del_url: 'warehouse/outstock/del',
                    multi_url: 'warehouse/outstock/multi',
                    table: 'out_stock',
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
                        {field: 'id', title: __('Id')},
                        {field: 'out_stock_number', title: __('Out_stock_number')},
                        {field: 'type_id', title: __('Type_id')},
                        {field: 'purchase_id', title: __('Purchase_id')},
                        {field: 'order_number', title: __('Order_number')},
                        {field: 'remark', title: __('Remark')},
                        {field: 'status', title: __('Status')},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'create_person', title: __('Create_person')},
                        { field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, buttons: [
                            {
                                name: 'detail',
                                text: '详情',
                                title: __('Detail'),
                                classname: 'btn btn-xs  btn-primary  btn-dialog',
                                icon: 'fa fa-list',
                                url: 'warehouse/outstock/detail',
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
                                text: '',
                                title: __('Edit'),
                                classname: 'btn btn-xs btn-success btn-dialog',
                                icon: 'fa fa-pencil',
                                url: 'warehouse/outstock/edit',
                                extend: 'data-area = \'["100%","100%"]\'',
                                callback: function (data) {
                                    Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                },
                                visible: function (row) {
                                    //返回true时按钮显示,返回false隐藏
                                    return true;
                                }
                            }

                        ], formatter: Table.api.formatter.operate }
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
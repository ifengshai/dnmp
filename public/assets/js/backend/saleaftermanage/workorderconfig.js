define(['jquery', 'bootstrap', 'backend', 'table', 'form','jstree'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'saleaftermanage/workorderconfig/index' + location.search,
                    add_url: 'saleaftermanage/workorderconfig/add',
                    edit_url: 'saleaftermanage/workorderconfig/edit',
                    del_url: 'saleaftermanage/workorderconfig/del',
                    multi_url: 'saleaftermanage/workorderconfig/multi',
                    table: 'work_order_problem_type',
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
                        { field: 'problem_belong', title: __('Problem_belong'), custom: { 1: 'blue', 2: 'danger', 3: 'orange' }, searchList: { 1: '订单修改', 2: '物流仓库', 3: '产品质量',4: '客户问题' ,5:'其他'}, formatter: Table.api.formatter.status },
                        { field: 'type', title: __('Type'), searchList: { 1: '客服工单', 2: '仓库工单' }, formatter: Table.api.formatter.status },
                        {field: 'problem_name', title: __('Problem_name')},
                        {
                            field: 'buttons',
                            width: "120px",
                            operate: false,
                            title: __('查看措施'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'workOrderNote',
                                    text: __('查看措施'),
                                    title: __('查看措施'),
                                    extend: 'data-area=\'["100%","100%"]\'',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'saleaftermanage/workorderconfig/detail',
                                    callback: function (data) {
                                    }
                                },
                            ],
                            formatter: Table.api.formatter.buttons
                        },
                        // {field: 'is_del', title: __('Is_del')},
                        {field: 'operate', title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: function (value, row, index) {
                                var that = $.extend({}, this);
                                $(table).data("operate-edit", null); // 列表页面隐藏 .编辑operate-edit  - 删除按钮operate-del
                                that.table = table;
                                return Table.api.formatter.operate.call(that, value, row, index);
                            }
                            // formatter: Table.api.formatter.operate
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
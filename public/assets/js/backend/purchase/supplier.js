define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                // searchFormVisible: true,
                // searchFormTemplate: 'customformtpl',
                extend: {
                    index_url: 'purchase/supplier/index' + location.search,
                    add_url: 'purchase/supplier/add',
                    edit_url: 'purchase/supplier/edit',
                    del_url: 'purchase/supplier/del',
                    multi_url: 'purchase/supplier/multi',
                    table: 'supplier',
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
                        { checkbox: true },
                        { field: 'id', title: __('Id') },
                        { field: 'supplier_name', title: __('Supplier_name'), operate: 'like' },
                        { field: 'email', title: __('Email'), operate: false },
                        { field: 'url', title: __('Url'), operate: false, formatter: Table.api.formatter.url },
                        { field: 'telephone', title: __('Telephone'), operate: false },
                        { field: 'address', title: __('Address'), operate: false },
                        { field: 'linkname', title: __('Linkname'), operate: 'like' },
                        { field: 'linkphone', title: __('Linkphone'), operate: 'like' },
                        { field: 'supplier_type', title: __('Supplier_type'), searchList: { 1: '镜片', 2: '镜架', 3: '眼镜盒', 4: '镜布' }, formatter: Controller.api.formatter.supplier_type },
                        { field: 'create_person', title: __('Create_person') },
                        { field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange' },
                        { field: 'status', title: __('Status'), searchList: { 1: '启用', 2: '禁用'}, formatter: Controller.api.formatter.status },
                        { field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
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
            formatter: {
                supplier_type: function (value, row, index) {
                    var str = '';
                    if (value == 1) {
                        str = '镜片';
                    } else if (value == 2) {
                        str = '镜架';
                    } else if (value == 3) {
                        str = '眼镜盒';
                    } else if (value == 4) {
                        str = '镜布';
                    }
                    return str;
                },
                status: function (value, row, index) {
                    var status = '';
                    if (value == 1) {
                        status = '启用';
                    } else if (value == 2) {
                        status = '禁用';
                    }
                    return status;
                },
            },
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
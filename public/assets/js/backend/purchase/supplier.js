define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
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
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'supplier_name', title: __('Supplier_name')},
                        {field: 'email', title: __('Email')},
                        {field: 'url', title: __('Url'), formatter: Table.api.formatter.url},
                        {field: 'telephone', title: __('Telephone')},
                        {field: 'address', title: __('Address')},
                        {field: 'area', title: __('Area')},
                        {field: 'linkname', title: __('Linkname')},
                        {field: 'linkphone', title: __('Linkphone')},
                        {field: 'opening_bank', title: __('Opening_bank')},
                        {field: 'receiving_address', title: __('Receiving_address')},
                        {field: 'opening_bank_address', title: __('Opening_bank_address')},
                        {field: 'bank_account', title: __('bank_account')},
                        {field: 'supplier_type', title: __('Supplier_type')},
                        {field: 'create_person', title: __('Create_person')},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'remark', title: __('Remark')},
                        {field: 'status', title: __('Status')},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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
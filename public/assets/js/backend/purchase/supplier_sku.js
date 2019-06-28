define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'purchase/supplier_sku/index' + location.search,
                    add_url: 'purchase/supplier_sku/add',
                    edit_url: 'purchase/supplier_sku/edit',
                    del_url: 'purchase/supplier_sku/del',
                    multi_url: 'purchase/supplier_sku/multi',
                    table: 'supplier_sku',
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
                        { field: 'sku', title: __('Sku'), operate: 'like' },
                        { field: 'supplier_sku', title: __('Supplier_sku') },
                        { field: 'supplier.supplier_name', title: __('Supplier_id'), operate: 'like', },
                        { field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange' },
                        { field: 'create_person', title: __('Create_person') },
                        { field: 'status', title: __('Status'), searchList: {  1: '启用',2: '禁用' }, formatter: Table.api.formatter.status },
                        { field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
            $(document).on('click', '.btn-add', function () {
                var content = $('#table-content table tbody').html();
                $('.caigou table tbody').append(content);
            })

            $(document).on('click', '.btn-del', function () {
                $(this).parent().parent().remove();
            })
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            formatter: {
                status: function (value, row, index) {
                    var custom = { hidden: 'gray', normal: 'success', deleted: 'danger', locked: 'info' };
                    if (typeof this.custom !== 'undefined') {
                        custom = $.extend(custom, this.custom);
                    }
                    this.custom = custom;
                    this.icon = 'fa fa-circle';
                    return Table.api.formatter.normal.call(this, value, row, index);
                }
            },
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
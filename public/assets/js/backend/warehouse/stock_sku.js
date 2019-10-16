define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                searchFormVisible: true,
                extend: {
                    index_url: 'warehouse/stock_sku/index' + location.search,
                    add_url: 'warehouse/stock_sku/add',
                    edit_url: 'warehouse/stock_sku/edit',
                    // del_url: 'warehouse/stock_sku/del',
                    multi_url: 'warehouse/stock_sku/multi',
                    table: 'store_sku',
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
                        { field: 'item.sku', title: __('Sku'), operate: 'like' },
                        { field: 'item.name', title: __('商品名称'), operate: 'like' },
                        {
                            field: 'item.is_open', title: __('SKU启用状态'), custom: { 1: 'success', 2: 'danger' },
                            searchList: { 1: '启用', 2: '禁用' },
                            formatter: Table.api.formatter.status
                        },

                        { field: 'storehouse.coding', title: __('Storehouse.coding'), operate: 'like' },
                        { field: 'storehouse.library_name', title: __('Storehouse.library_name'), operate: 'like' },
                        {
                            field: 'storehouse.status', title: __('Storehouse.status'), custom: { 1: 'success', 2: 'danger' },
                            searchList: { 1: '启用', 2: '禁用' },
                            formatter: Table.api.formatter.status
                        },
                        { field: 'create_person', title: __('Create_person') },
                        { field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange' },
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
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
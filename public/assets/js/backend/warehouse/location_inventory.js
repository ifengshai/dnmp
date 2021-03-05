define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'bootstrap-table-jump-to'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'warehouse/location_inventory/index' + location.search,
                    table: 'location_inventory'
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
                        { field: 'storehouse.coding', title: __('库位编码'), operate: 'like' },
                        { field: 'area_code', title: __('库区编码'), operate: 'like' },
                        { field: 'sku', title: __('Sku'), operate: 'like' },
                        { field: 'name', title: __('商品名称'), operate: false},
                        { field: 'stock', title: __('库位库存') , operate: false},
                        // { field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
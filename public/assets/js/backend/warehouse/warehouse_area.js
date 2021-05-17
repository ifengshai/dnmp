define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'bootstrap-table-jump-to'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'warehouse/warehouse_area/index' + location.search,
                    add_url: 'warehouse/warehouse_area/add?type=1',
                    edit_url: 'warehouse/warehouse_area/edit?type=1',
                    import_url: 'warehouse/warehouse_area/import',
                    table: 'warehouse_area'
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
                        { field: 'stock', title: __('仓库'), operate: 'like' },
                        { field: 'coding', title: __('库区编码'), operate: 'like' },
                        { field: 'name', title: __('库区名称') , operate: 'like' },
                        {
                            field: 'type', title: __('库区类型'), custom: { 1: 'success', 2: 'danger' },
                            searchList: { 1: '存储库区', 2: '拣货库区' },
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'status', title: __('Status'), custom: { 1: 'success', 2: 'danger' },
                            searchList: { 1: '启用', 2: '禁用' },
                            formatter: Table.api.formatter.status
                        },
                        { field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
            //启用
            $(document).on('click', '.btn-open', function () {
                var ids = Table.api.selectedids(table);
                Backend.api.ajax({
                    url: Config.moduleurl + '/warehouse/warehouse_area/setStatus',
                    data: { ids: ids, status: 1 }
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            });

            //禁用
            $(document).on('click', '.btn-close', function () {
                var ids = Table.api.selectedids(table);
                Backend.api.ajax({
                    url: Config.moduleurl + '/warehouse/warehouse_area/setStatus',
                    data: { ids: ids, status: 2 }
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            });
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
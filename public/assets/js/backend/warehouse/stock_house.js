define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'bootstrap-table-jump-to'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'warehouse/stock_house/index' + location.search,
                    add_url: 'warehouse/stock_house/add',
                    edit_url: 'warehouse/stock_house/edit',
                    // del_url: 'warehouse/stock_house/del',
                    multi_url: 'warehouse/stock_house/multi',
                    table: 'store_house',
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
                        { field: 'coding', title: __('Coding'), operate: 'like' }, 
                        { field: 'library_name', title: __('Library_name') },
                        {
                            field: 'status', title: __('Status'), custom: { 1: 'success', 2: 'danger' },
                            searchList: { 1: '启用', 2: '禁用' },
                            formatter: Table.api.formatter.status
                        },
                        { field: 'remark', title: __('Remark') },
                        { field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange' },
                        { field: 'create_person', title: __('Create_person') },
                        { field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            //审核通过
            $(document).on('click', '.btn-open', function () {
                var ids = Table.api.selectedids(table);
                Backend.api.ajax({
                    url: Config.moduleurl + '/warehouse/stock_house/setStatus',
                    data: { ids: ids, status: 1 }
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })

            //审核拒绝
            $(document).on('click', '.btn-close', function () {
                var ids = Table.api.selectedids(table);
                Backend.api.ajax({
                    url: Config.moduleurl + '/warehouse/stock_house/setStatus',
                    data: { ids: ids, status: 2 }
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })
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
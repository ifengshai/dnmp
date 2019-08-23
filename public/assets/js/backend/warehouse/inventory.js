define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                searchFormVisible: true,
                extend: {
                    index_url: 'warehouse/inventory/index' + location.search,
                    add_url: 'warehouse/inventory/add',
                    edit_url: 'warehouse/inventory/edit',
                    del_url: 'warehouse/inventory/del',
                    multi_url: 'warehouse/inventory/multi',
                    table: 'inventory_list',
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
                        {
                            field: '', title: __('序号'), formatter: function (value, row, index) {
                                var options = table.bootstrapTable('getOptions');
                                var pageNumber = options.pageNumber;
                                var pageSize = options.pageSize;
                                return (pageNumber - 1) * pageSize + 1 + index;
                            }, operate: false
                        },
                        { field: 'id', title: __('Id'), visible: false, operate: false },
                        { field: 'number', title: __('Number') },
                        { field: 'num', title: __('盘点数') },
                        { field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange' },
                        { field: 'create_person', title: __('Create_person') },
                        { field: 'status', title: __('Status'), searchList: { "0": __('待盘点'), "1": __('盘点中'), "2": __('已完成') }, formatter: Table.api.formatter.status },
                        { field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {

            //绑定事件
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var panel = $($(this).attr("href"));
                if (panel.size() > 0) {
                    Controller.table[panel.attr("id")].call(this);
                    $(this).on('click', function (e) {
                        $($(this).attr("href")).find(".btn-refresh").trigger("click");
                    });
                }
                //移除绑定的事件
                $(this).unbind('shown.bs.tab');
            });

            //必须默认触发shown.bs.tab事件
            $('ul.nav-tabs li.active a[data-toggle="tab"]').trigger("shown.bs.tab");




        },
        table: {
            first: function () {
                Table.api.init({
                    searchFormVisible: true,
                    search: false,
                    showExport: false,
                    showColumns: false,
                    showToggle: false,
                    pageSize: 50,

                });
                // 表格1
                var table1 = $("#table1");
                table1.bootstrapTable({
                    url: 'warehouse/inventory/add',
                    toolbar: '#toolbar1',
                    sortName: 'id',
                    search: false,
                    columns: [
                        [
                            { checkbox: true },
                            {
                                field: '', title: __('序号'), formatter: function (value, row, index) {
                                    var options = table1.bootstrapTable('getOptions');
                                    var pageNumber = options.pageNumber;
                                    var pageSize = options.pageSize;
                                    return (pageNumber - 1) * pageSize + 1 + index;
                                }, operate: false
                            },
                            { field: 'id', title: __('Id'), operate: false, visible: false },
                            { field: 'sku', title: __('Sku'), operate: 'like' },
                            { field: 'name', title: __('Name'), operate: false },
                            { field: 'stock', title: __('实时库存'), operate: 'BETWEEN' },
                            { field: 'available_stock', title: __('可用库存'), operate: false },
                            { field: 'occupy_stock', title: __('占用库存'), operate: false },
                            { field: 'sample_stock', title: __('留样库存'), operate: false },

                            { field: 'on_way_stock', title: __('在途库存'), operate: false },
                            { field: 'is_open', title: __('启用状态'), searchList: { 1: '启用', 2: '禁用' }, formatter: Table.api.formatter.status }
                        ]
                    ]
                });

                // 为表格1绑定事件
                Table.api.bindevent(table1);

                //添加
                $(document).on('click', '.btn-adds', function () {
                    var arr = table1.bootstrapTable('getSelections');
                    Backend.api.ajax({
                        url: '/admin/warehouse/inventory/addTempProduct',
                        data: { data: JSON.stringify(arr) }
                    }, function (data, ret) {
                        table1.bootstrapTable('refresh');
                    });
                })
            },
            second: function () {
                Table.api.init({
                    searchFormVisible: true,
                    search: false,
                    showExport: false,
                    showColumns: false,
                    showToggle: false,
                    pageSize: 50
                });
                // 表格2
                var table2 = $("#table2");
                table2.bootstrapTable({
                    url: 'warehouse/inventory/tempProduct',
                    extend: {
                        index_url: '',
                        add_url: '',
                        edit_url: '',
                        del_url: 'warehouse/inventory/tempdel',
                        multi_url: '',
                        table: '',
                    },
                    toolbar: '#toolbar2',
                    sortName: 'id',
                    search: false,
                    columns: [
                        [
                            { checkbox: true },
                            {
                                field: '', title: __('序号'), formatter: function (value, row, index) {
                                    var options = table2.bootstrapTable('getOptions');
                                    var pageNumber = options.pageNumber;
                                    var pageSize = options.pageSize;

                                    return (pageNumber - 1) * pageSize + 1 + index;
                                }, operate: false
                            },
                            { field: 'id', title: __('Id'), operate: false, visible: false },
                            { field: 'sku', title: __('Sku'), operate: 'like' },
                            { field: 'name', title: __('Name'), operate: false },
                            { field: 'stock', title: __('实时库存'), operate: false },
                            { field: 'available_stock', title: __('可用库存'), operate: false },
                            { field: 'occupy_stock', title: __('占用库存'), operate: false },
                            { field: 'sample_stock', title: __('留样库存'), operate: false },
                            { field: 'on_way_stock', title: __('在途库存'), operate: false }
                        ]
                    ]
                });

                // 为表格2绑定事件
                Table.api.bindevent(table2);

                //创建任务
                $(document).on('click', '.btn-create', function () {
                    var arr = table2.bootstrapTable('getSelections');
                    Backend.api.ajax({
                        url: '/admin/warehouse/inventory/createInventory',
                        data: { data: JSON.stringify(arr) }
                    }, function (data, ret) {
                        window.location.href = '/admin/warehouse/inventory/index';
                    });
                })

                //全部创建
                $(document).on('click', '.btn-createall', function () {
                    Backend.api.ajax({
                        url: '/admin/warehouse/inventory/createInventory',
                        data: { data: 'all' }
                    }, function (data, ret) {
                        window.location.href = '/admin/warehouse/inventory/index';
                    });
                })
            }
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
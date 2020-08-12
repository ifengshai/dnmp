define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'editable', 'bootstrap-table-jump-to'], function ($, undefined, Backend, Table, Form, undefined) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'warehouse/inventory/index' + location.search,
                    add_url: 'warehouse/inventory/add',
                    edit_url: 'warehouse/inventory/edit',
                    // del_url: 'warehouse/inventory/del',
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
                        {
                            field: 'check_status', title: __('审核状态'), custom: { 0: 'success', 1: 'yellow', 2: 'blue', 3: 'danger', 4: 'gray' },
                            searchList: { 0: '未提交', 1: '待审核', 2: '已审核', 3: '已拒绝', 4: '已取消' },
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, buttons: [
                                {
                                    name: '开始盘点',
                                    text: '开始盘点',
                                    title: __('开始盘点'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-play',
                                    url: 'warehouse/inventory/start',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        if (row.status == 1 || row.status == 2) {
                                            return false;
                                        }
                                        return true;
                                    }
                                },
                                {
                                    name: '继续盘点',
                                    text: '继续盘点',
                                    title: __('继续盘点'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-play',
                                    url: 'warehouse/inventory/start',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        if (row.status == 1) {
                                            return true;
                                        }
                                        return false;
                                    }
                                },
                                {
                                    name: 'edit',
                                    text: '',
                                    title: __('Edit'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-pencil',
                                    url: 'warehouse/inventory/edit',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        if (row.status == 0) {
                                            return true;
                                        }
                                        return false;
                                    }
                                },
                                {
                                    name: 'submitAudit',
                                    text: '提交审核',
                                    title: __('提交审核'),
                                    classname: 'btn btn-xs btn-success btn-ajax',
                                    icon: 'fa fa-leaf',
                                    url: 'warehouse/inventory/audit',
                                    confirm: '确认提交审核吗',
                                    success: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        $(".btn-refresh").trigger("click");
                                        //如果需要阻止成功提示，则必须使用return false;
                                        //return false;
                                    },
                                    error: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        if (row.check_status == 0 && row.status == 2) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    },
                                },
                                {
                                    name: 'cancel',
                                    text: '取消',
                                    title: '取消',
                                    classname: 'btn btn-xs btn-danger btn-cancel',
                                    icon: 'fa fa-remove',
                                    url: 'warehouse/inventory/cancel',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        if (row.check_status == 0 && row.status == 2) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'detail',
                                    text: '详情',
                                    title: '详情',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'warehouse/inventory/detail',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        if (row.status == 2) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                }

                            ], formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            //审核通过
            $(document).on('click', '.btn-endInventory', function () {
                var ids = Table.api.selectedids(table);
                Layer.confirm(
                    __('确定结束盘点吗?'),
                    { icon: 3, title: __('Warning'), shadeClose: true },
                    function (index) {
                        Backend.api.ajax({
                            url: Config.moduleurl + '/warehouse/inventory/endInventory',
                            data: { inventory_id: ids }
                        }, function (data, ret) {
                            Layer.close(index);
                            table.bootstrapTable('refresh');
                        });
                    }
                );

            })


            //审核通过
            $(document).on('click', '.btn-open', function () {
                var ids = Table.api.selectedids(table);
                Backend.api.ajax({
                    url: Config.moduleurl + '/warehouse/inventory/setStatus',
                    data: { ids: ids, status: 2 }
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })

            //审核拒绝
            $(document).on('click', '.btn-close', function () {
                var ids = Table.api.selectedids(table);
                Backend.api.ajax({
                    url: Config.moduleurl + '/warehouse/inventory/setStatus',
                    data: { ids: ids, status: 3 }
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })

            //审核取消
            $(document).on('click', '.btn-cancel', function (e) {
                e.preventDefault();
                var url = $(this).attr('href');
                Backend.api.ajax({
                    url: url,
                    data: { status: 4 }
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })



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
                    pageList: [10, 25, 50, 100],

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
                            { field: 'stock', title: __('总库存'), operate: 'BETWEEN' },
                            {
                                field: '', title: __('实时库存'), operate: false, formatter: function (value, row) {
                                    return row.stock - row.distribution_occupy_stock;
                                }
                            },
                            { field: 'available_stock', title: __('可用库存'), operate: false },
                            { field: 'distribution_occupy_stock', title: __('配货占用库存'), operate: false },
                            { field: 'is_open', title: __('启用状态'), searchList: { 1: '启用', 2: '禁用' }, operate: false, formatter: Table.api.formatter.status }
                        ]
                    ]
                });

                // 为表格1绑定事件
                Table.api.bindevent(table1);

                //添加
                $(document).on('click', '.btn-adds', function () {
                    var arr = table1.bootstrapTable('getSelections');
                    Backend.api.ajax({
                        url: Config.moduleurl + '/warehouse/inventory/addTempProduct',
                        data: { data: JSON.stringify(arr) }
                    }, function (data, ret) {
                        table1.bootstrapTable('refresh');
                    });
                })

                //批量导出xls 
                $('.btn-batch-export-xls').click(function () {
                    var options = table1.bootstrapTable('getOptions');
                    var search = options.queryParams({});
                    var filter = search.filter;
                    var op = search.op;
                    window.open(Config.moduleurl + '/warehouse/inventory/batch_export_xls?filter=' + filter + '&op=' + op, '_blank');
                });
            },
            second: function () {
                Table.api.init({
                    searchFormVisible: true,
                    search: false,
                    showExport: false,
                    showColumns: false,
                    showToggle: false,
                    pageSize: 50,
                    pageList: [10, 25, 50, 100],
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
                        import_url: 'warehouse/inventory/import',
                        multi_url: '',
                        table: '',
                    },
                    toolbar: '#toolbar2',
                    sortName: 'id',
                    search: false,
                    columns: [
                        [
                            // { checkbox: true },
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
                            { field: 'stock', title: __('总库存'), operate: false },
                            {
                                field: '', title: __('实时库存'), operate: false, formatter: function (value, row) {
                                    return row.stock - row.distribution_occupy_stock;
                                }
                            },
                            { field: 'available_stock', title: __('可用库存'), operate: false },
                            { field: 'distribution_occupy_stock', title: __('配货占用库存'), operate: false },
                        ]
                    ]
                });

                // 为表格2绑定事件
                Table.api.bindevent(table2);

                //创建任务
                $(document).on('click', '.btn-create', function () {
                    var arr = table2.bootstrapTable('getSelections');
                    Backend.api.ajax({
                        url: Config.moduleurl + '/warehouse/inventory/createInventory',
                        data: { data: JSON.stringify(arr) }
                    }, function (data, ret) {
                        window.location.href = Config.moduleurl + '/warehouse/inventory/index';
                    }, function (data, ret) {
                        window.location.href = Config.moduleurl + '/warehouse/inventory/index';
                    });
                })

                //全部创建
                $(document).on('click', '.btn-createall', function () {
                    Backend.api.ajax({
                        url: Config.moduleurl + '/warehouse/inventory/createInventory',
                        data: { data: 'all' }
                    }, function (data, ret) {
                        parent.location.reload();
                    }, function (data, ret) {
                        parent.location.reload();
                    });
                })
            }
        },
        edit: function () {
            //绑定事件
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var panel = $($(this).attr("href"));
                if (panel.size() > 0) {
                    Controller.table2[panel.attr("id")].call(this);
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
        table2: {
            first: function () {
                Table.api.init({
                    searchFormVisible: true,
                    search: false,
                    showExport: false,
                    showColumns: false,
                    showToggle: false,
                    pageSize: 50,
                    pageList: [10, 25, 50, 100],

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
                            { field: 'stock', title: __('总库存'), operate: 'BETWEEN' },
                            {
                                field: '', title: __('实时库存'), operate: false, formatter: function (value, row) {
                                    return row.stock - row.distribution_occupy_stock;
                                }
                            },
                            { field: 'available_stock', title: __('可用库存'), operate: false },
                            { field: 'distribution_occupy_stock', title: __('配货占用库存'), operate: false },
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
                        url: Config.moduleurl + '/warehouse/inventory/addInventoryItem',
                        data: { data: JSON.stringify(arr), inventory_id: Config.id }
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
                    pageSize: 50,
                    pageList: [10, 25, 50, 100],
                });
                // 表格2
                var table2 = $("#table2");
                table2.bootstrapTable({
                    url: 'warehouse/inventory/inventoryEdit' + '?inventory_id=' + Config.id,
                    extend: {
                        index_url: '',
                        add_url: '',
                        edit_url: '',
                        del_url: 'warehouse/inventory/delInventoryItem',
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
                            { field: 'real_time_qty', title: __('总库存'), operate: false },
                            { field: 'available_stock', title: __('可用库存'), operate: false },
                            { field: 'distribution_occupy_stock', title: __('配货占用库存'), operate: false }
                        ]
                    ]
                });

                // 为表格2绑定事件
                Table.api.bindevent(table2);

            }
        },
        start: function () {
            // 初始化表格参数配置
            Table.api.init({
                searchFormVisible: true,

                extend: {
                    index_url: 'warehouse/inventory/start' + location.search + '&inventory_id=' + Config.inventory_id,
                    edit_url: 'warehouse/inventory/startEdit',
                    import_url: 'warehouse/inventory/importXls' + '?inventory_id=' + Config.inventory_id,
                    table: 'inventory_item',
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
                        // { checkbox: true },
                        {
                            field: '', title: __('序号'), formatter: function (value, row, index) {
                                var options = table.bootstrapTable('getOptions');
                                var pageNumber = options.pageNumber;
                                var pageSize = options.pageSize;
                                return (pageNumber - 1) * pageSize + 1 + index;
                            }, operate: false
                        },
                        { field: 'id', title: __('Id'), visible: false, operate: false },
                        { field: 'sku', title: __('Sku'), operate: 'like' },
                        {
                            field: 'allstock', title: __('总库存'), operate: false, formatter: function (value, row) {
                                return row.real_time_qty + row.distribution_occupy_stock;
                            }
                        },
                        { field: 'real_time_qty', title: __('实时库存'), operate: false },
                        { field: 'available_stock', title: __('可用库存'), operate: false },
                        { field: 'distribution_occupy_stock', title: __('配货占用库存'), operate: false },
                        {
                            field: 'inventory_qty', title: __('盘点数量'), operate: false, editable: {
                                emptytext: "__",
                            }
                        },
                        { field: 'error_qty', title: __('误差数量'), operate: false }

                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            table.bootstrapTable('getOptions').onEditableSave = function (field, row, oldValue, $el) {
                var data = {};
                data["row[" + field + "]"] = row[field];
                Fast.api.ajax({
                    url: this.extend.edit_url + "/ids/" + row[this.pk],
                    data: data
                }, function (data) {

                    table.bootstrapTable('refresh');
                })
            }

            //结束盘点
            $(document).on('click', '.end', function () {
                var inventory_id = Config.inventory_id;
                Fast.api.ajax({
                    url: 'warehouse/inventory/endInventory',
                    data: { inventory_id: inventory_id }
                }, function (data) {
                    Layer.closeAll();
                    parent.location.href = Config.moduleurl + '/warehouse/inventory/index';
                })
            })
        },
        detail: function () {
            // 初始化表格参数配置
            Table.api.init({
                searchFormVisible: true,
                extend: {
                    index_url: 'warehouse/inventory/detail' + location.search + '&inventory_id=' + Config.inventory_id,
                    table: 'inventory_item',
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
                        // { checkbox: true },
                        {
                            field: '', title: __('序号'), formatter: function (value, row, index) {
                                var options = table.bootstrapTable('getOptions');
                                var pageNumber = options.pageNumber;
                                var pageSize = options.pageSize;
                                return (pageNumber - 1) * pageSize + 1 + index;
                            }, operate: false
                        },
                        { field: 'id', title: __('Id'), visible: false, operate: false },
                        { field: 'sku', title: __('Sku'), operate: 'like' },
                        {
                            field: 'allstock', title: __('总库存'), operate: false, formatter: function (value, row) {
                                return row.real_time_qty + row.distribution_occupy_stock;
                            }
                        },
                        { field: 'real_time_qty', title: __('实时库存'), operate: false },
                        { field: 'available_stock', title: __('可用库存'), operate: false },
                        { field: 'distribution_occupy_stock', title: __('配货占用库存'), operate: false },
                        {
                            field: 'inventory_qty', title: __('盘点数量'), operate: false
                        },
                        { field: 'error_qty', title: __('误差数量'), operate: false },
                        {
                            field: 'remark', title: __('备注'), operate: false
                        },

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
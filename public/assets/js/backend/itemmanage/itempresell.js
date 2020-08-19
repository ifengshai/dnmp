define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'jqui', 'bootstrap-table-jump-to'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'itemmanage/itempresell/index' + location.search + '&label=' + Config.label,
                    table: 'item_presell',
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
                        { field: 'id', title: __('Id'), operate: false },
                        { field: 'sku', title: __('商品SKU'), operate: 'LIKE' },
                        { field: 'platform_sku', title: __('平台SKU'), operate: 'LIKE' },
                        // { field: 'name', title: __('Name') },
                        {
                            field: 'outer_sku_status',
                            title: __('平台上下架状态'),
                            searchList: { 1: '上架', 2: '下架' },
                            custom: { 1: 'green', 2: 'red' },
                            formatter: Table.api.formatter.status,
                            operate: false
                        },
                        { field: 'available_stock', title: __('可用库存'), operate: false },
                        { field: 'stock', title: __('虚拟仓库存'), operate: false },
                        { field: 'presell_num', title: __('预售数量'), operate: false },
                        { field: 'presell_residue_num', title: __('预售剩余数量'), operate: false },
                        { field: 'presell_start_time', title: __('预售开始时间'), operate: false  },
                        { field: 'presell_end_time', title: __('预售结束时间'), operate: false  },
                        {
                            field: 'presell_status',
                            title: __('Presell_status'),
                            searchList: { 0: '未开启', 1: '预售中', 2: '已结束' },
                            custom: { 0: 'blue', 1: 'green', 2: 'danger' },
                            formatter: Table.api.formatter.status,
                        },
                        { field: 'presell_create_time', title: __('创建时间'), operate: 'RANGE', addclass: 'datetimerange' },
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons: [

                                {
                                    name: 'openStart',
                                    text: '开启预售',
                                    title: __('开启预售'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    url: Config.moduleurl + '/itemmanage/itempresell/openStart',
                                    success: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        $(".btn-refresh").trigger("click");
                                        //如果需要阻止成功提示，则必须使用return false;
                                        //return false;
                                    },
                                    extend: 'data-area = \'["50%","50%"]\'',
                                    error: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function (row) {
                                        if (row.presell_status == 0) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    },
                                },
                                {
                                    name: 'openEnd',
                                    text: '结束预售',
                                    title: __('结束预售'),
                                    classname: 'btn btn-xs btn-danger btn-ajax',
                                    url: Config.moduleurl + '/itemmanage/itempresell/openEnd',
                                    confirm: '确定要结束预售吗',
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
                                        if (row.presell_status == 1) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    },
                                },
                                {
                                    name: 'edit_presell',
                                    text: '编辑预售',
                                    title: __('编辑预售'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    url: Config.moduleurl + '/itemmanage/itempresell/openStart',
                                    extend: 'data-area = \'["50%","50%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        if (row.presell_status != 0) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'history',
                                    text: '历史记录',
                                    title: __('历史记录'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: Config.moduleurl + '/itemmanage/itempresell/presell_history',
                                    icon: 'fa fa-list',
                                    extend: 'data-area = \'["80%","60%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        return true;
                                    }
                                },
                            ]
                        },

                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            //选项卡切换
            $('.panel-heading a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var field = $(this).data("field");
                var value = $(this).data("value");
                var options = table.bootstrapTable('getOptions');
                options.pageNumber = 1;
                var queryParams = options.queryParams;
                options.queryParams = function (params) {
                    var params = queryParams(params);
                    var filter = params.filter ? JSON.parse(params.filter) : {};
                    var op = params.op ? JSON.parse(params.op) : {};
                    filter[field] = value;
                    params.filter = JSON.stringify(filter);
                    params.op = JSON.stringify(op);
                    return params;
                };
                table.bootstrapTable('refresh', {});
                return false;
            });
        },
        openstart: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        presell_history: function () {
            // 初始化表格参数配置
            Table.api.init({
                commonSearch: false,
                search: false,
                showExport: false,
                showColumns: false,
                showToggle: false,
                pagination: true,
                extend: {
                    index_url: 'itemmanage/itempresell/presell_history' + location.search + '&ids=' + Config.ids,
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
                        { field: 'id', title: __('Id'), operate: false },
                        { field: 'sku', title: 'SKU', operate: false },
                        { field: 'type', title: '操作类型', operate: false },
                        { field: 'presell_change_num', title: '预售变化数量', operate: false },
                        { field: 'old_presell_num', title: '原预售数量', operate: false },
                        { field: 'old_presell_residue_num', title: '原预售剩余数量', operate: false },
                        { field: 'presell_start_time', title: '预售开始时间', operate: false },
                        { field: 'presell_end_time', title: '预售结束时间', operate: false },
                        { field: 'create_time', title: '创建时间', operate: false },
                        { field: 'create_person', title: '创建人', operate: false }
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
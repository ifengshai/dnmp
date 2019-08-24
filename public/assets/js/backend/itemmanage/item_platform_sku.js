define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                searchFormVisible: true,
                extend: {
                    index_url: 'itemmanage/item_platform_sku/index' + location.search,
                    add_url: 'itemmanage/item_platform_sku/add',
                    //edit_url: 'itemmanage/item_platform_sku/edit',
                    //del_url: 'itemmanage/item_platform_sku/del',
                    multi_url: 'itemmanage/item_platform_sku/multi',
                    table: 'item_platform_sku',
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
                        {field: 'id', title: __('Id'),operate:false},
                        {field: 'sku', title: __('Sku')},
                        {field: 'platform_sku', title: __('Platform_sku')},
                        {field: 'name', title: __('Name')},
                        {
                            field: 'platform_type',
                            title: __('Platform_type'),
                            searchList:$.getJSON('saleaftermanage/sale_after_task/getAjaxOrderPlatformList'),
                            custom:{1:'blue',2:'yellow',3:'green',4:'red'},
                            formatter:Table.api.formatter.status,
                        },
                        {
                            field:'outer_sku_status',
                            title:__('Outer_sku_status'),
                            searchList:{1:'上架',2:'下架'},
                            custom:{1:'blue',2:'red'},
                            formatter:Table.api.formatter.status,
                        },
                        {
                            field: 'platform_sku_status',
                            title: __('Platform_sku_status'),
                            searchList: { 1:'上架', 2:'下架' },
                            custom: { 1: 'blue', 2: 'red' },
                            formatter: Table.api.formatter.status,
                        },
                        {field: 'create_person', title: __('Create_person')},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange'},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons:[
                                {
                                    name: 'putaway',
                                    text: '上架',
                                    title: __('上架'),
                                    classname: 'btn btn-xs btn-success btn-ajax',
                                    url: '/admin/itemmanage/item_platform_sku/putaway',
                                    confirm: '确定要上架吗',
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
                                        return true;
                                        // if (row.outer_sku_status == 2) {
                                        //     return true;
                                        // } else {
                                        //     return false;
                                        // }
                                    },
                                },
                                {
                                    name: 'soldOut',
                                    text: '下架',
                                    title: __('下架'),
                                    classname: 'btn btn-xs btn-danger btn-ajax',
                                    url: '/admin/itemmanage/item_platform_sku/soldOut',
                                    confirm: '确定要下架吗',
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
                                        return true;
                                        // if (row.outer_sku_status == 2) {
                                        //     return true;
                                        // } else {
                                        //     return false;
                                        // }
                                    },
                                },
                            ]
                        },

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
        },
        presell: function () {
            Table.api.init({
                searchFormVisible: true,
                extend: {
                    index_url: 'itemmanage/item_platform_sku/presell' + location.search,
                    add_url: 'itemmanage/item_platform_sku/addPresell',
                    //edit_url: 'itemmanage/item_platform_sku/edit',
                    //del_url: 'itemmanage/item_platform_sku/del',
                    multi_url: 'itemmanage/item_platform_sku/multi',
                    table: 'item_platform_sku',
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
                        {field: 'id', title: __('Id'),operate:false},
                        {field: 'sku', title: __('Sku')},
                        {field: 'platform_sku', title: __('Platform_sku')},
                        {field: 'name', title: __('Name')},
                        {
                            field: 'platform_type',
                            title: __('Platform_type'),
                            searchList:$.getJSON('saleaftermanage/sale_after_task/getAjaxOrderPlatformList'),
                            custom:{1:'blue',2:'yellow',3:'green',4:'red'},
                            formatter:Table.api.formatter.status,
                        },
                        {
                            field:'outer_sku_status',
                            title:__('Outer_sku_status'),
                            searchList:{1:'上架',2:'下架'},
                            custom:{1:'blue',2:'red'},
                            formatter:Table.api.formatter.status,
                        },
                        {
                            field: 'platform_sku_status',
                            title: __('Platform_sku_status'),
                            searchList: { 1:'上架', 2:'下架' },
                            custom: { 1: 'blue', 2: 'red' },
                            formatter: Table.api.formatter.status,
                        },
                        {
                          field:'presell_num',
                          title:__('Presell_num'),
                        },
                        {
                          field:'presell_residue_num',
                          title:__('Presell_residue_num'),
                        },
                        {
                          field:'presell_start_time',
                          title:__('Presell_start_time'),
                        },
                        {
                          field:'presell_end_time',
                          title:__('Presell_end_time'),
                        },
                        {
                          field:'presell_status',
                          title:__('Presell_status'),
                        },
                        {
                          field:'presell_create_person',
                          title:__('Presell_create_person'),
                        },
                        {field: 'create_person', title: __('Create_person')},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange'},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons:[
                                {
                                    name: 'putaway',
                                    text: '上架',
                                    title: __('上架'),
                                    classname: 'btn btn-xs btn-success btn-ajax',
                                    url: '/admin/itemmanage/item_platform_sku/putaway',
                                    confirm: '确定要上架吗',
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
                                        return true;
                                        // if (row.outer_sku_status == 2) {
                                        //     return true;
                                        // } else {
                                        //     return false;
                                        // }
                                    },
                                },
                                {
                                    name: 'soldOut',
                                    text: '下架',
                                    title: __('下架'),
                                    classname: 'btn btn-xs btn-danger btn-ajax',
                                    url: '/admin/itemmanage/item_platform_sku/soldOut',
                                    confirm: '确定要下架吗',
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
                                        return true;
                                        // if (row.outer_sku_status == 2) {
                                        //     return true;
                                        // } else {
                                        //     return false;
                                        // }
                                    },
                                },
                            ]
                        },

                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        }
    };
    return Controller;
});
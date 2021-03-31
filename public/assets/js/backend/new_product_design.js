define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'new_product_design/index' + location.search,
                    add_url: 'new_product_design/add',
                    edit_url: 'new_product_design/edit',
                    del_url: 'new_product_design/del',
                    multi_url: 'new_product_design/multi',
                    table: 'new_product_design',
                }
            });

            var table = $("#table");
            $('.panel-heading .nav-tabs li a').on('click', function (e) {
                var field = $(this).data("field");
                var value = $(this).data("value");

                var options = table.bootstrapTable('getOptions');
                options.pageNumber = 1;
                var queryParams = options.queryParams;
                options.queryParams = function (params) {
                    var params = queryParams(params);
                    var filter = params.filter ? JSON.parse(params.filter) : {};
                    var op = params.op ? JSON.parse(params.op) : {};
                    if (field == '') {
                        delete filter.label;
                    } else {
                        filter[field] = value;
                    }
                    params.filter = JSON.stringify(filter);
                    params.op = JSON.stringify(op);
                    return params;
                };
                table.bootstrapTable('refresh', {});
            });

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('序号')},
                        {field: 'sku', title: __('Sku')},

                        {
                            field: 'status',
                            title: __('状态'),
                            searchList: { 1: '待录尺寸', 2: '待拍摄', 3: '拍摄中', 4: '待分配', 5: '待修图', 6: '修图中', 7: '待审核', 8: '已完成', 9: '审核拒绝', 10: '完成'},
                            custom: { 1: 'black', 2: 'black', 3: 'black', 4: 'black', 5: 'black', 6: 'black', 7: 'black', 8: 'black', 9: 'black', 10: 'black', 11: 'black' },
                            formatter: Table.api.formatter.status
                        },
                        {field: 'responsible_id', title: __('Responsible_id')},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, buttons: [

                                {
                                    name: 'edit_recipient',
                                    text:__('录尺寸'),
                                    title:__('录尺寸'),
                                    hidden:function(row){
                                        return row.status !== 1? true : false;
                                    },
                                    extend: 'data-area = \'["50%","50%"]\'',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'new_product_design/record_size',
                                    icon: '',
                                    area: ['50%', '45%'],
                                    //extend: 'data-area = \'["100%","100%"]\' target=\'_blank\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function(row){
                                        return true;
                                    }
                                },
                                {
                                    name: 'edit',
                                    text:'查看详情',
                                    title:__('查看详情'),

                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: '',
                                    url: 'new_product_design/detail/id/{row.id}',
                                    area: ['50%', '45%'],
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return row.status<2? true : false;

                                    }
                                },
                                {
                                    name: 'start_shooting',
                                    text: '开始拍摄',
                                    title: __('开始拍摄'),
                                    hidden:function(row){
                                        return row.status !== 2? true : false;
                                    },
                                    classname: 'btn btn-xs btn-danger  btn-magic btn-dialog',
                                    icon: 'fa fa-magic',
                                    url: 'new_product_design/change_status?status=3',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh', {});
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
                                    }
                                },
                                {
                                    name: 'shot_over',
                                    text: '拍摄完成',
                                    title: __('拍摄完成'),
                                    hidden:function(row){
                                        return row.status !== 3? true : false;
                                    },
                                    classname: 'btn btn-xs btn-danger  btn-magic btn-dialog',
                                    icon: 'fa fa-magic',
                                    url: 'new_product_design/change_status?status=4',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh', {});
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
                                    }
                                },
                                {
                                    name: 'distr_user',
                                    text:'分配',
                                    title:__('分配'),
                                    hidden:function(row){
                                        return row.status !== 4? true : false;
                                    },
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: '',
                                    url: 'new_product_design/allocate_personnel/id/{row.id}',
                                    area: ['50%', '45%'],
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                },
                                {
                                    name: 'tarted_making',
                                    text: '开始制作',
                                    title: __('开始制作'),
                                    hidden:function(row){
                                        return row.status !== 5? true : false;
                                    },
                                    classname: 'btn btn-xs btn-danger  btn-magic btn-dialog',
                                    icon: 'fa fa-magic',
                                    url: 'new_product_design/change_status?status=6',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh', {});
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
                                    }
                                },
                                {
                                    name: 'upload_pictures',
                                    text:__('上传图片'),
                                    title:__('上传图片'),
                                    hidden:function(row){
                                        return row.status !== 6? true : false;
                                    },
                                    extend: 'data-area = \'["50%","50%"]\'',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'new_product_design/add_img',
                                    icon: '',
                                    area: ['50%', '45%'],
                                    //extend: 'data-area = \'["100%","100%"]\' target=\'_blank\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function(row){
                                        return true;
                                    }
                                },
                                {
                                    name: 'approved',
                                    text:__('审核通过'),
                                    title:__('审核通过'),
                                    hidden:function(row){
                                        return row.status !== 7? true : false;
                                    },
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-magic',
                                    url: 'new_product_design/change_status?status=8',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh', {});
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
                                    }
                                },
                                {
                                    name: 'audit_refused',
                                    text:__('审核拒绝'),
                                    title:__('审核拒绝'),
                                    hidden:function(row){
                                        return row.status !== 7? true : false;
                                    },
                                    classname: 'btn btn-xs btn-danger  btn-magic btn-dialog',
                                    icon: 'fa fa-magic',
                                    url: 'new_product_design/change_status?status=9',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh', {});
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
                                    }
                                },


                            ], formatter: Table.api.formatter.operate
                        }

                    ]
                ]
            });

            $(table).data("operate-del", null);

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
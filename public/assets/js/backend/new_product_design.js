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
                                    name: 'edit',
                                    text:'查看详情',
                                    hidden:function(row){
                                        return row.status !==5 ? true : false;
                                    },
                                    title: function (row) {
                                        return __('Answer') + '【' + row.ticket_id + '】' + row.subject;
                                    },
                                    classname: 'btn btn-xs btn-success',
                                    icon: '',
                                    url: 'zendesk/zendesk/edit/status/{row.status}',
                                    extend: 'data-area = \'["100%","100%"]\' target=\'_blank\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function(row){
                                        var range = [row.due_id,1,75,95,114,116,117,181];
                                        if(-1 != $.inArray(Config.admin_id, range)){
                                            return true;
                                        }else{
                                            return false;
                                        }
                                    }
                                },

                                {
                                    name: 'edit',
                                    text: ('查看'),
                                    title: function (row) {
                                        return __('Answer') + '【' + row.ticket_id + '】' + row.subject;
                                    },
                                    classname: 'btn btn-xs btn-success',
                                    icon: '',
                                    url: 'zendesk/zendesk/email_toview/status/{row.status}',
                                    extend: 'data-area = \'["100%","100%"]\' target=\'_blank\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: true,
                                },
                                {
                                    name: 'edit_recipient',
                                    text:__('修改承接人'),
                                    title:__('修改承接人'),
                                    extend: 'data-area = \'["50%","50%"]\'',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'zendesk/zendesk/edit_recipient',
                                    icon: '',
                                    area: ['50%', '45%'],
                                    //extend: 'data-area = \'["100%","100%"]\' target=\'_blank\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function(row){
                                        return true;
                                    }
                                }

                            ], formatter: Table.api.formatter.operate
                        }

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
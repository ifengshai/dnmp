define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'oc_customer_after_sales_work_order/index' + location.search,
                    add_url: 'oc_customer_after_sales_work_order/add',
                    edit_url: 'oc_customer_after_sales_work_order/edit',
                    del_url: 'oc_customer_after_sales_work_order/del',
                    multi_url: 'oc_customer_after_sales_work_order/multi',
                    table: 'oc_customer_after_sales_work_order',
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
                        {
                            field: '', title: __('序号'), formatter: function (value, row, index) {
                                var options = table.bootstrapTable('getOptions');
                                var pageNumber = options.pageNumber;
                                var pageSize = options.pageSize;

                                //return (pageNumber - 1) * pageSize + 1 + index;
                                return 1 + index;
                            }, operate: false
                        },
                        // {field: 'id', title: __('Id')},
                        // {
                        //     field: 'site',
                        //     title: __('站点'),
                        //     searchList: { 1: 'zeelool', 2: 'voogueme'}
                        // },
                        {field: 'increment_id', title: __('订单号')},
                        {field: 'email', title: __('Email')},
                        {
                            field: 'order_type',
                            title: __('Order_type'),
                            searchList: { 1: '普通订单', 2: '批发', 3: '网红',4:'补发'},
                            formatter: Table.api.formatter.status,
                            visible: false
                        },
                        {field: 'problem_type', title: __('Problem_type'),operate:false},
                        {field: 'status', title: __('处理状态'), searchList: { 1: 'Submitted', 2: 'Processing', 3: 'Completed'},},
                        {field: 'handler_name', title: __('Handler_name'),operate:'LIKE'},
                        {
                            field: 'task_info', title: __('工单'), operate: false, formatter: function (value, row) {
                                if (value) {
                                    return '<a href="' + Config.moduleurl + '/saleaftermanage/work_order_list/index?platform_order=' + row.increment_id + '" class="btn btn-primary btn-xs btn-click btn-dialog" data-table-id="table" target="_blank" data-field-index="11" data-row-index="0" data-button-index="3" title="工单"><i class="fa fa-list"></i> 工单</a>'
                                }
                            }
                        },

                        {field: 'created_at', title: __('Created_at'),operate: 'RANGE', sortable: true, addclass: 'datetimerange'},
                        {field: 'completed_at', title: __('Completed_at'),operate: 'RANGE', sortable: true, addclass: 'datetimerange'},

                        {
                            field: 'operate', width: "240px", title: __('操作'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'detail',
                                    text: '问题详情',
                                    title: __('问题详情'),
                                    extend: 'data-area = \'["80%","80%"]\'',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-list',
                                    url: Config.moduleurl + '/oc_customer_after_sales_work_order/question_detail',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                },
                                {
                                    name: 'order_detail',
                                    text: '订单详情',
                                    title: __('订单详情'),
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    classname: 'btn btn-xs btn-warning btn-addtabs',
                                    icon: 'fa fa-pencil',

                                    url: Config.moduleurl + '/saleaftermanage/order_return/search',

                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        return true;
                                    }
                                },
                            ],
                            formatter: Table.api.formatter.buttons
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
        question_detail:function (){
            Controller.api.bindevent();
            $(document).on('click', ".btn-sub", function () {
                var type = $(this).val();
                if (type == 'Submitted') {
                    $('#pm_audit_status').val(1);
                    $("#demand_edit").attr('action', 'oc_customer_after_sales_work_order/question_detail');
                }
                if (type == 'Processing') {
                    $('#pm_audit_status').val(2);
                    $("#demand_edit").attr('action', 'oc_customer_after_sales_work_order/question_detail');
                }
                if (type == 'Completed') {
                    $('#pm_audit_status').val(3);
                    $("#demand_edit").attr('action', 'oc_customer_after_sales_work_order/question_detail');
                }
                $("#demand_edit").submit();
            });

        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
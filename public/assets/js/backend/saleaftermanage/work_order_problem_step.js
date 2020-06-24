define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'saleaftermanage/work_order_problem_step/index' + location.search,
                    add_url: 'saleaftermanage/work_order_problem_step/add',
                    edit_url: 'saleaftermanage/work_order_problem_step/edit',
                    del_url: 'saleaftermanage/work_order_problem_step/del',
                    multi_url: 'saleaftermanage/work_order_problem_step/multi',
                    table: 'work_order_problem_step',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                searchFormVisible: true,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        // {field: 'problem_id', title: __('Problem_id')},
                        {
                            field: 'problem_name',
                            title: __('问题类型'),
                            formatter: Table.api.formatter.status,
                            operate:false
                        },
                        // {field: 'step_id', title: __('Step_id')},
                        {
                            field: 'step_id',
                            title: __('Step_id'),
                            searchList: $.getJSON('saleaftermanage/work_order_problem_step/getMeasureContent'),
                            formatter: Table.api.formatter.status,
                            visible: false
                        },
                        {
                            field: 'problem_id',
                            title: __('Problem_id'),
                            searchList: $.getJSON('saleaftermanage/work_order_problem_step/getQuestionType'),
                            formatter: Table.api.formatter.status,
                            visible: false
                        },
                        {
                            field: 'extend_group_id',
                            title: __('Extend_group_id'),
                            searchList: $.getJSON('saleaftermanage/work_order_problem_step/getUserGroup'),
                            formatter: Table.api.formatter.status,
                            visible: false
                        },
                        {
                            field: 'step_name',
                            title: __('措施'),
                            formatter: Table.api.formatter.status,
                            operate:false
                        },
                        {field: 'name', title: __('承接组'),operate:false},
                        {
                            field: 'is_check',
                            title: __('Is_check'),
                            searchList: {1: '需要审核', 0: '无需审核'},
                            formatter: Table.api.formatter.status
                        },
                        // {
                        //     field: 'is_auto_complete',
                        //     title: __('是否自动完成'),
                        //     searchList: {1: '是', 0: '否'},
                        //     formatter: Table.api.formatter.status
                        // },
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate
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
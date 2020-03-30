define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'demand/develop_demand/index' + location.search,
                    add_url: 'demand/develop_demand/add',
                    edit_url: 'demand/develop_demand/edit',
                    del_url: 'demand/develop_demand/del',
                    multi_url: 'demand/develop_demand/multi',
                    table: 'develop_demand',
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
                        {field: 'id', title: __('Id')},
                        {field: 'title', title: __('Titel')},
                        {field: 'desc', title: __('Desc')},
                        {field: 'review_status_manager', title: __('Review_status_manager')},
                        {field: 'expected_time', title: __('Expected_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'priority', title: __('Priority')},
                        {field: 'review_status_develop', title: __('Review_status_develop')},
                        {field: 'assign_developer_ids', title: __('Assign_developer_ids')},
                        {field: 'is_test', title: __('Is_test')},
                        {field: 'complexity', title: __('Complexity')},
                        {field: 'estimated_time', title: __('Estimated_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'test_person', title: __('Test_person')},
                        {field: 'test_is_passed', title: __('Test_is_passed')},
                        {field: 'test_finish_time', title: __('Test_finish_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'is_finish', title: __('开发主管是否确认')},
                        {field: 'finish_time', title: __('Finish_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'is_finish_task', title: __('产品经理是否确认')},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'create_person', title: __('Create_person')},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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
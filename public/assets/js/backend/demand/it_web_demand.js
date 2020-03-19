define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'demand/it_web_demand/index' + location.search,
                    add_url: 'demand/it_web_demand/add',
                    edit_url: 'demand/it_web_demand/edit',
                    del_url: 'demand/it_web_demand/del',
                    multi_url: 'demand/it_web_demand/multi',
                    table: 'it_web_demand',
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
                        {field: 'status', title: __('Status'),visible:false},
                        {field: 'id', title: __('Id')},
                        {field: 'site_type', title: __('Site_type')},
                        {field: 'entry_user_id', title: __('Entry_user_id')},
                        {field: 'title', title: __('Title')},
                        {
                            field: 'content',
                            title: __('content'),
                            formatter: Controller.api.formatter.getcontent,
                        },
                        {field: 'hope_time', title: __('Hope_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'All_group', title: __('All_group')},







                        {field: 'web_designer_user_id', title: __('Web_designer_user_id')},
                        {field: 'web_designer_expect_time', title: __('Web_designer_expect_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'web_designer_is_finish', title: __('Web_designer_is_finish')},
                        {field: 'web_designer_finish_time', title: __('Web_designer_finish_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'phper_group', title: __('Phper_group')},
                        {field: 'phper_complexity', title: __('Phper_complexity')},
                        {field: 'phper_user_id', title: __('Phper_user_id')},
                        {field: 'phper_expect_time', title: __('Phper_expect_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'phper_is_finish', title: __('Phper_is_finish')},
                        {field: 'phper_finish_time', title: __('Phper_finish_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'app_group', title: __('App_group')},
                        {field: 'app_complexity', title: __('App_complexity')},
                        {field: 'app_user_id', title: __('App_user_id')},
                        {field: 'app_expect_time', title: __('App_expect_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'app_is_finish', title: __('App_is_finish')},
                        {field: 'app_finish_time', title: __('App_finish_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'test_group', title: __('Test_group')},
                        {field: 'test_complexity', title: __('Test_complexity')},
                        {field: 'test_user_id', title: __('Test_user_id')},
                        {field: 'test_is_finish', title: __('Test_is_finish')},
                        {field: 'test_finish_time', title: __('Test_finish_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'test_bug_for_web_designer_num', title: __('Test_bug_for_web_designer_num')},
                        {field: 'test_bug_for_phper_num', title: __('Test_bug_for_phper_num')},
                        {field: 'test_bug_for_app_num', title: __('Test_bug_for_app_num')},
                        {field: 'entry_user_confirm', title: __('Entry_user_confirm')},
                        {field: 'entry_user_confirm_time', title: __('Entry_user_confirm_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'all_finish_time', title: __('All_finish_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
            //需求详情
            $(document).on('click', '.check_demand_content', function () {
                var problem_desc = $(this).attr('data');
                Layer.open({
                    closeBtn: 1,
                    title: '问题描述',

                    content: problem_desc
                });
                return false;
            });
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


            },
            formatter: {
                getcontent: function (value) {
                    if (value == null || value == undefined) {
                        value = '';
                    }
                    return '<span class="check_demand_content" data = "' + value + '" style="">查 看</span>';
                },

                getClear: function (value) {

                    if (value == null || value == undefined) {
                        return '';
                    } else {
                        var tem = value;

                        if (tem.length <= 20) {
                            return tem;
                        } else {
                            return '<span class="problem_desc_info" name = "' + tem + '" style="">' + tem.substr(0, 20) + '...</span>';

                        }
                    }
                }

            }
        }
    };
    return Controller;
});
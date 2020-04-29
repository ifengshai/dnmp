define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
                escape: false,
                showExport: false,
                showColumns: false,
                showToggle: false,
                extend: {
                    index_url: 'demand/it_test_record/index' + location.search,
                    table: 'it_test_record',
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
                        { field: 'id', title: __('Id') , operate: false },
                        { field: 'type', title: __('Type'), custom: { 1: 'danger', 2: 'success', 3: 'blue', 4: 'yellow' }, searchList: { 1: 'bug', 2: '需求', 3: '疑难', 4: '开发' }, formatter: Table.api.formatter.status },
                        { field: 'title', title: __('关联任务标题'), operate: false  },
                        {
                            field: 'site_type',
                            title: __('Site_type'),
                            searchList: { 1: 'Zeelool', 2: 'Voogueme', 3: 'Nihao', 4: 'Wesee', 5: 'Orther' },
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'responsibility_group', title: __('Responsibility_group'),
                            searchList: { 1: '前端', 2: '后端', 3: 'APP', 4: '测试' },
                            formatter: Table.api.formatter.status
                        },
                        { field: 'responsibility_user_name', title: __('Responsibility_user_id') , operate: false },
                        {
                            field: 'bug_type', title: __('Bug_type'),
                            custom: { 1: 'success', 2: 'yellow', 3: 'blue', 4: 'danger' },
                            searchList: { 1: '次要', 2: '一般', 3: '严重', 4: '崩溃' },
                            formatter: Table.api.formatter.status
                        },
                        { field: 'environment_type', title: __('Environment_type'), searchList: { 1: '测试环境', 2: '正式环境' }, formatter: Table.api.formatter.status },
                        { field: 'describe', title: __('描述'), cellStyle: formatTableUnit, formatter: Controller.api.formatter.getClear, operate: false },
                        { field: 'create_time', title: __('Create_time'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime },
                        { field: 'create_user_name', operate: false , title: __('Create_user_id') }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            //td宽度以及内容超过宽度隐藏
            function formatTableUnit(value, row, index) {
                return {
                    css: {
                        "white-space": "nowrap",
                        "text-overflow": "ellipsis",
                        "overflow": "hidden",
                        "max-width": "150px"
                    }
                }
            }

            //问题描述
            $(document).on('click', ".problem_desc_info", function () {
                var problem_desc = $(this).attr('data');
                Layer.open({
                    closeBtn: 1,
                    title: '详情',
                    area: ['900px', '500px'],
                    content: decodeURIComponent(problem_desc)
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
            formatter: {

                getClear: function (value) {

                    if (value == null || value == undefined) {
                        return '';
                    } else {
                        var tem = value;

                        if (tem.length <= 10) {
                            return tem;
                        } else {
                            return '<div class="problem_desc_info" data = "' + encodeURIComponent(tem) + '"' + '>' + tem + '</div>';

                        }
                    }
                },

            },
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
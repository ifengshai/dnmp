define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'saleaftermanage/work_order_check_rule/index' + location.search,
                    add_url: 'saleaftermanage/work_order_check_rule/add',
                    edit_url: 'saleaftermanage/work_order_check_rule/edit',
                    del_url: 'saleaftermanage/work_order_check_rule/del',
                    multi_url: 'saleaftermanage/work_order_check_rule/multi',
                    table: 'work_order_check_rule',
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
                        {field: 'id', title: __('id')},
                        {field: 'is_group_create', title: __('创建者类型'),custom: { 1: 'blue', 0: 'danger'}, searchList: { 1: '组创建', 0: '人创建' }, formatter: Table.api.formatter.status },
                        {field: 'work_create_person_id', title: __('创建人或创建组的id')},

                        {field: 'work_create_person', title: __('创建人或创建组名称')},

                        {field: 'step_id', title: __('措施')},
                        {field: 'symbol', title: __('Symbol'),searchList: { 'gt': '大于', 'egt': '大于等于', 'lt': '小于', 'elt': '小于等于', 'eq': '等于',}, formatter: Table.api.formatter.status },
                        {field: 'step_value', title: __('Step_value')},
                        {field: 'check_group_name', title: __('审核组')},
                        {field: 'weight', title: __('Weight')},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
            $("#c-problem_belong").change(function () {
                var checkValue=$("#c-problem_belong").val();
                if (checkValue == 1){
                    $("#step").hide();
                    $("#create_person").show();
                }else{
                    $("#step").show();
                    $("#create_person").hide();
                }
            });
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
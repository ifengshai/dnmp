define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'oc_prescription_pic/index' + location.search,
                    add_url: 'oc_prescription_pic/add',
                    edit_url: 'oc_prescription_pic/edit',
                    del_url: 'oc_prescription_pic/del',
                    multi_url: 'oc_prescription_pic/multi',
                    table: 'oc_prescription_pic',
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
                            field: 'site',
                            title: __('站点'),
                            searchList: { 1: '全部', 2: 'Z站', 3: 'V站'},
                            custom: { 1: 'black', 2: 'black', 3: 'black', 4: 'black', 5: 'black', 6: 'black', 7: 'black', 8: 'black', 9: 'black', 10: 'black', 11: 'black' },
                            formatter: Table.api.formatter.status,
                            visible: false
                        },

                        {field: 'id', title: __('Id')},
                        {field: 'email', title: __('Email'),operate: false},

                        {
                            field: 'query',
                            title: __('Query'),
                            operate:false,
                            events: Controller.api.events.gettitle,
                            cellStyle: formatTableUnit,
                            formatter: Controller.api.formatter.gettitle,
                        },
                        {
                            field: 'status',
                            title: __('状态'),
                            searchList: { 1: '待处理', 2: '已处理' }},
                        {field: 'handler_name', title: __('Handler_name'),operate: false},
                        {field: 'created_at', title: __('Created_at'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'completion_time', title: __('Completion_time'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'remarks', title: __('Remarks'),operate: false},
                        // {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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
            },
            formatter: {

                //点击标题，弹出窗口
                gettitle: function (value) {
                    return '<a class="btn-gettitle" style="color: #333333!important;">' + value + '</a>';
                },

            },
            events: {//绑定事件的方法
                //点击标题，弹出窗口
                gettitle: {
                    //格式为：方法名+空格+DOM元素
                    'click .btn-gettitle': function (e, value, row, index) {
                        Backend.api.open('oc_prescription_pic/question_message/type/view/ids/' + row.id, __('问题描述'), { area: ['70%', '70%'] });
                    }
                },

            }
        }
    };
    Form.api.bindevent($("form[role=form]"));
    return Controller;
});
function formatTableUnit(value, row, index) {
    return {
        css: {
            "white-space": "nowrap",
            "text-overflow": "ellipsis",
            "overflow": "hidden",
            "max-width": "200px"
        }
    }
}
define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageSize: 10,
                pageList: [10, 25, 50, 100],
                escape: false,
                extend: {
                    index_url: 'demand/it_web_task/index' + location.search,
                    add_url: 'demand/it_web_task/add',
                    edit_url: 'demand/it_web_task/edit',

                    multi_url: 'demand/it_web_task/multi',
                    table: 'it_web_task',
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
                        { field: 'id', title: __('Id') },
                        { field: 'type', title: __('Type'), custom: { 1: 'success', 2: 'success', 3: 'success' }, searchList: { 1: '短期任务', 2: '中期任务', 3: '长期任务' }, formatter: Table.api.formatter.status },
                        { field: 'title', title: __('Title') },
                        { field: 'desc', title: __('Desc'), cellStyle: formatTableUnit, formatter: Controller.api.formatter.getClear, operate: false },
                        { field: 'closing_date', title: __('Closing_date') },
                        {
                            field: 'is_complete', title: __('Is_complete'),
                            custom: { 1: 'success', 0: 'danger' },
                            searchList: { 1: '是', 0: '否' },
                            formatter: Table.api.formatter.status
                        },
                        { field: 'complete_date', title: __('complete_date') },
                        {
                            field: 'is_test_adopt', title: __('Is_test_adopt'), custom: { 1: 'success', 0: 'danger' },
                            searchList: { 1: '是', 0: '否' },
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'result', title: __('关键结果'), formatter: function (value, row) {
                                console.log(row);
                                return '<a class="btn btn-xs btn-primary btn-dialog" data-area="[&quot;80%&quot;,&quot;70%&quot;]" title="关键结果" data-table-id="table" data-field-index="12" data-row-index="0" data-button-index="0" href="demand/it_web_task/item/ids/' + row.id + '"><i class="fa fa-list"></i> 查看</a>';
                            }
                        },
                        { field: 'create_person', title: __('Create_person'), operate: false },
                        { field: 'createtime', title: __('Create_time'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime },

                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, buttons: [

                                {
                                    name: 'detail',
                                    text: '详情',
                                    title: __('查看详情'),
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'demand/it_web_task/detail',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                },
                                {
                                    name: 'edit',
                                    text: '编辑',
                                    title: __('Edit'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-pencil',
                                    url: 'demand/it_web_task/edit',
                                    extend: 'data-area = \'["70%","70%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
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

            //描述弹窗
            $(document).on('click', ".problem_desc_info", function () {
                var problem_desc = $(this).attr('data');
                Layer.open({
                    closeBtn: 1,
                    title: '问题描述',
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
        detail: function () {
            Controller.api.bindevent();
        },
        item: function () {
            // 初始化表格参数配置
            
            Table.api.init({
                escape: false,
                commonSearch: false,
                search: false,
                showExport: false,
                showColumns: false,
                showToggle: false,
                pagination: false,
                extend: {
                    index_url: 'demand/it_web_task/item' + location.search + '&id=' + Config.id,

                    table: 'it_web_task_item',
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
                        { field: 'id', title: __('Id') },
                        { field: 'person_in_charge_text', title: __('负责人') },
                        {
                            field: 'group_type', title: __('分组类型'),
                            custom: { 1: 'success', 2: 'success', 3: 'success', 4: 'success' },
                            searchList: { 1: '前端', 2: '后端', 3: 'app', 4: '测试' },
                            formatter: Table.api.formatter.status
                        },
                        { field: 'title', title: __('Title') },
                        { field: 'desc', title: __('Desc'), cellStyle: formatTableUnit, formatter: Controller.api.formatter.getClear, operate: false },
                        { field: 'plan_date', title: __('Closing_date') },
                        {
                            field: 'is_complete', title: __('Is_complete'),
                            custom: { 1: 'success', 0: 'danger' },
                            searchList: { 1: '是', 0: '否' },
                            formatter: Table.api.formatter.status
                        },
                        { field: 'complete_date', title: __('complete_date') },
                        {
                            field: 'is_test_adopt', title: __('Is_test_adopt'), custom: { 1: 'success', 0: 'danger' },
                            searchList: { 1: '是', 0: '否' },
                            formatter: Table.api.formatter.status
                        },
                        { field: 'test_adopt_time', title: __('测试通过时间'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime },
                        { field: 'test_person', title: __('测试操作人') },
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, buttons: [
                                {
                                    name: 'ajax',
                                    text: '完成',
                                    title: __('完成'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    icon: 'fa fa-magic',
                                    url: 'demand/it_web_task/set_complete_status',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh', {});
                                        //如果需要阻止成功提示，则必须使用return false;
                                        //return false;
                                    },
                                    error: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function (row) {
                                        return true;
                                        if (row.user_id == row.person_in_charge && row.is_complete == 0) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'ajax',
                                    text: '测试通过',
                                    title: __('测试通过'),
                                    classname: 'btn btn-xs btn-success btn-info btn-ajax',
                                    icon: 'fa fa-leaf',
                                    url: 'demand/it_web_task/set_test_status',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh', {});
                                        //如果需要阻止成功提示，则必须使用return false;
                                        //return false;
                                    },
                                    error: function (data, ret) {
                                        console.log(data, ret);
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function (row) {
                                        return true;
                                        var test_person = Config.test_user;
                                        if (test_person.includes(row.user_id) && row.is_test_adopt == 0) {
                                            return true;
                                        } else {
                                            return false;
                                        }
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
        api: {
            formatter: {

                getClear: function (value) {

                    if (value == null || value == undefined) {
                        return '';
                    } else {
                        var tem = value;

                        if (tem.length <= 20) {
                            return tem;
                        } else {
                            return '<div class="problem_desc_info" data = "' + encodeURIComponent(tem) + '"' + '>' + tem + '</div>';

                        }
                    }
                },

            },
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));

                $(document).on('click', '.btn-add', function () {
                    var content = $('#table-content table tbody').html();
                    $('.caigou table tbody').append(content);

                    Form.api.bindevent($("form[role=form]"));
                })

                $(document).on('click', '.btn-del', function () {
                    $(this).parent().parent().remove();
                })

                //选择分组类型
                $(document).on('change', '.group_type', function () {
                    var type = $(this).val();
                    var person = [];
                    if (type == 1) {
                        person = Config.web_designer_user;
                    } else if (type == 2) {
                        person = Config.phper_user;
                    } else if (type == 3) {
                        person = Config.app_user;
                    } else if (type == 4) {
                        person = Config.test_user;
                    }
                    var shtml = '';
                    for (var i in person) {
                        shtml += "<option value='" + i + "'>" + person[i] + "</option>";
                    }
                    $(this).parent().parent().find('.person_in_charge').html(shtml);
                })


            }
        }
    };
    return Controller;
});

 //td宽度以及内容超过宽度隐藏
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
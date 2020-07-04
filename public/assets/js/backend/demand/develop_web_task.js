define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
                escape: false,
                extend: {
                    index_url: 'demand/develop_web_task/index' + location.search,
                    add_url: 'demand/develop_web_task/add',
                    edit_url: 'demand/develop_web_task/edit',
                    multi_url: 'demand/develop_web_task/multi',
                    table: 'develop_web_task',
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
                        // { checkbox: true },
                        { field: 'id', title: __('Id') },
                        { field: 'type', title: __('Type'), custom: { 1: 'success', 2: 'success', 3: 'success' }, searchList: { 1: '短期任务', 2: '中期任务', 3: '长期任务' }, formatter: Table.api.formatter.status },
                        { field: 'title', title: __('Title') },
                        { field: 'desc', title: __('Desc'), cellStyle: formatTableUnit, formatter: Controller.api.formatter.getClear, operate: false },
                        { field: 'closing_date', title: __('Closing_date') , operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime , visible:false },
                        {
                            field: 'is_complete', title: __('开发完成'),
                            custom: { 1: 'success', 0: 'danger' },
                            searchList: { 1: '是', 0: '否' },
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'is_test_adopt', title: __('Is_test_adopt'), custom: { 1: 'success', 0: 'danger' },
                            searchList: { 1: '是', 0: '否' },
                            formatter: Table.api.formatter.status
                        },

                        { field: 'complete_date', title: __('开发完成时间'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime  , visible:false},
                        { field: 'test_regression_adopt_time', title: __('回归测试通过时间'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime  , visible:false},
                        {
                            field: 'is_finish', title: __('产品经理确认'), custom: { 1: 'success', 0: 'danger' },
                            searchList: { 1: '已确认', 0: '未确认' },
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'test_regression_adopt', title: __('回归测试状态'), custom: { 1: 'success', 0: 'danger' },
                            searchList: { 1: '已通过', 0: '待处理' },
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'result', title: __('关键结果'), operate: false, formatter: function (value, row) {
                                return '<a href="javascript:;" data-id="' + row.id + '" class="btn btn-xs btn-primary  btn-list" title="关键结果" ><i class="fa fa-list"></i> 查看</a>';
                            }
                        },
                        { field: 'create_person', title: __('Create_person'), operate: false },
                        { field: 'nickname', title: __('负责人'), visible: false, operate: 'like' },
                        { field: 'createtime', title: __('创建时间'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime , visible:false},
                        { field: 'finish_time', title: __('确认时间'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime, visible:false },

                        {
                            field: '时间节点',
                            title: __('时间节点'),
                            operate: false,
                            formatter: function (value, rows) {
                                var all_user_name = '';
                                if(rows.createtime){
                                    all_user_name += '<span class="all_user_name">创建：<b>'+ rows.createtime + '</b></span><br>';
                                }
                                if(rows.closing_date){
                                    all_user_name += '<span class="all_user_name">截止：<b>'+ rows.closing_date + '</b></span><br>';
                                }
                                if(rows.complete_date){
                                    all_user_name += '<span class="all_user_name">开发：<b>'+ rows.complete_date + '</b></span><br>';
                                }
                                if(rows.finish_time){
                                    all_user_name += '<span class="all_user_name">确认：<b>'+ rows.finish_time + '</b></span><br>';
                                }
                                if(rows.test_regression_adopt_time){
                                    all_user_name += '<span class="all_user_name">回归：<b>'+ rows.test_regression_adopt_time + '</b></span><br>';
                                }
                                return all_user_name;
                            },
                        },

                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, buttons: [
                                {
                                    name: 'problem',
                                    text: '问题记录',
                                    title: __('问题记录'),
                                    extend: 'data-area = \'["70%","70%"]\'',
                                    classname: 'btn btn-xs btn-warning btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'demand/develop_web_task/problem_detail',
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
                                        // return true;
                                        //返回true时按钮显示,返回false隐藏
                                        if (row.is_complete == 1 && Config.is_problem_detail == 1) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                },
                                /*{
                                    name: 'ajax',
                                    text: '完成',
                                    title: __('完成'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    icon: 'fa fa-magic',
                                    url: 'demand/develop_web_task/set_task_complete_status',
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
                                        // return true;
                                        if (row.is_complete == 0 && Config.is_set_status == 1) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                },*/
                                {
                                    name: 'detail',
                                    text: '详情',
                                    title: __('查看详情'),
                                    extend: 'data-area = \'["80%","70%"]\'',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'demand/develop_web_task/detail',
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
                                    url: 'demand/develop_web_task/edit',
                                    extend: 'data-area = \'["80%","70%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        if (row.is_test_adopt == 0 && row.is_complete == 0 && Config.is_edit == 1) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'test',
                                    text: '记录问题',
                                    title: __('测试站记录问题'),
                                    classname: 'btn btn-xs btn-warning btn-dialog',
                                    url: 'demand/develop_web_task/test_info',
                                    extend: 'data-area = \'["70%","70%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        if (row.is_complete == 1 && Config.is_test_info_btu == 1  && row.is_test_adopt ==0) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'ajax',
                                    text: '通过测试',
                                    title: __('测试站测试通过'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    icon: 'fa fa-magic',
                                    url: 'demand/develop_web_task/set_test_status',
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
                                        if (Config.is_set_test_status_btu == 1 && row.is_test_adopt ==0 && row.is_complete == 1 ) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'test',
                                    text: '回归测试',
                                    title: __('回归测试'),
                                    classname: 'btn btn-xs btn-warning btn-dialog',
                                    url: 'demand/develop_web_task/regression_test_info',
                                    extend: 'data-area = \'["70%","70%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        if ( row.is_finish ==1 && row.test_regression_adopt == 0&& Config.is_regression_test_info == 1) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'ajax',
                                    text: '通过测试',
                                    title: __('回归测试通过'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    icon: 'fa fa-magic',
                                    url: 'demand/develop_web_task/set_task_test_status',
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
                                        if ( row.is_finish ==1 && row.test_regression_adopt==0 && Config.is_set_task_test_status == 1) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'ajax',
                                    text: '产品经理确认',
                                    title: __('产品经理确认'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    icon: 'fa fa-magic',
                                    url: 'demand/develop_web_task/is_finish_task',
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
                                        if (row.is_test_adopt == 1 && Config.is_finish_task == 1 && row.is_finish == 0 ) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                        
                                    }
                                },
                                {
                                    name: 'ajax',
                                    title: __('删除'),
                                    icon:'fa fa-trash',
                                    classname: 'btn btn-xs btn-danger btn-magic btn-ajax',
                                    url: 'demand/develop_web_task/del',
                                    confirm: '是否删除?',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh', {});
                                    },
                                    error: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function (row) {
                                        if (Config.is_del_btu == 1  && row.is_finish_task ==0) {//有权限 或者创建人为当前人
                                            return true;
                                        }else {
                                            return  false;
                                        }
                                    }
                                },
                            ], formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

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

            //关键结果弹出框
            $(document).on('click', '.btn-list', function () {
                var options = {
                    shadeClose: false,
                    shade: [0.3, '#393D49'],
                    area: ['80%', '70%'], //弹出层宽高
                    callback: function (value) {
                    }
                };
                var ids = $(this).data('id');
                Fast.api.open('demand/develop_web_task/item?ids=' + ids, '关键结果', options);
            })


            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
            $(document).on('click', '.btn-del', function () {
                $(this).parent().parent().remove();
            })
        },
        edit: function () {
            Controller.api.bindevent();

             //删除商品数据
             $(document).on('click', '.btn-del', function () {
                var id = $(this).parent().parent().find('.item_id').val();
                if (id) {
                    Layer.confirm(
                        __('确定要删除吗'),
                        function (index) {
                            Backend.api.ajax({
                                url: Config.moduleurl + '/demand/develop_web_task/deleteItem',
                                data: { id: id }
                            }, function (data, ret) {
                                $(this).parent().parent().remove();
                                location.reload();
                                Layer.closeAll();
                            });
                        }
                    );

                }
            })
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
                    index_url: 'demand/develop_web_task/item' + location.search + '&id=' + Config.id,

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
                            field: 'type', title: __('任务类型'),
                            custom: { 1: 'success', 2: 'success', 3: 'success' },
                            searchList: { 1: '短期任务', 2: '中期任务', 3: '长期任务' },
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
                        // {
                        //     field: 'is_test_adopt', title: __('Is_test_adopt'), custom: { 1: 'success', 0: 'danger' },
                        //     searchList: { 1: '是', 0: '否' },
                        //     formatter: Table.api.formatter.status
                        // },
                        // { field: 'test_adopt_time', title: __('测试通过时间'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime },
                        // { field: 'test_person', title: __('测试操作人') },
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, buttons: [
                                {
                                    name: 'ajax',
                                    text: '完成',
                                    title: __('完成'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    icon: 'fa fa-magic',
                                    url: 'demand/develop_web_task/set_complete_status',
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
                                        if (Config.user_id == row.person_in_charge && row.is_complete == 0) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                },
                               /* {
                                    name: 'ajax',
                                    text: '测试通过',
                                    title: __('测试通过'),
                                    classname: 'btn btn-xs btn-success btn-info btn-ajax',
                                    icon: 'fa fa-leaf',
                                    url: 'demand/develop_web_task/set_test_status',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh', {});
                                        //如果需要阻止成功提示，则必须使用return false;
                                        //return false;
                                    },
                                    error: function (data, ret) {
                                        console.log(data, ret);
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function (row) {
                                        var test_person = Config.test_user;
                                        if ($.inArray(Config.user_id, test_person) !== -1 && row.is_test_adopt == 0 && row.is_complete == 1) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                },

                                {
                                    name: 'info',
                                    text: '记录问题',
                                    title: __('记录问题'),
                                    classname: 'btn btn-xs btn-success  btn-dialog',
                                    icon: 'fa fa-pencil',
                                    url: 'demand/develop_web_task/test_info',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh', {});
                                        //如果需要阻止成功提示，则必须使用return false;
                                        //return false;
                                    },
                                    error: function (data, ret) {
                                        console.log(data, ret);
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function (row) {
                                        var test_person = Config.test_user;
                                        if ($.inArray(Config.user_id, test_person) !== -1 && row.is_test_adopt == 0 && row.is_complete == 1) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                }*/
                            ], formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);


        },
        test_info:function(){
            Controller.api.bindevent();
        },
        problem_detail: function () {
            Controller.api.bindevent();
        },
        regression_test_info: function () {
            Controller.api.bindevent();
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
                        if (!i) {
                            continue;
                        }
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
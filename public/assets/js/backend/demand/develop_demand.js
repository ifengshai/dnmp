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
                    index_url: 'demand/develop_demand/index' + location.search,
                    add_url: 'demand/develop_demand/add',
                    edit_url: 'demand/develop_demand/edit',
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
                        // { checkbox: true },
                        { field: 'id', title: __('Id') },
                        // { field: 'title', title: __('Titel'), cellStyle: formatTableUnit, formatter: Controller.api.formatter.getClear, operate: false },
                        {
                            field: 'desc',
                            operate: false,
                            title: __('需求内容'),
                            events: Controller.api.events.getcontent,
                            formatter: Controller.api.formatter.getcontent,
                        },
                        // { field: 'desc', title: __('Desc'), cellStyle: formatTableUnit, formatter: Controller.api.formatter.getClear, operate: false },
                        { field: 'create_person', title: __('提出人'), operate: false },
                        { field: 'review_status_manager', title: __('Review_status_manager'), custom: { 0: 'yellow', 1: 'success', 2: 'danger' }, searchList: { 0: '待审核', 1: '审核通过', 2: '审核拒绝' }, formatter: Table.api.formatter.status },
                        { field: 'solution', title: __('解决方案'), cellStyle: formatTableUnit, formatter: Controller.api.formatter.getClear, operate: false },
                        { field: 'refuse_reason', title: __('拒绝原因'), cellStyle: formatTableUnit, formatter: Controller.api.formatter.getClear, operate: false },
                        { field: 'priority', title: __('Priority'), custom: { 1: 'success', 2: 'blue', 3: 'danger' }, searchList: { 1: '低', 2: '中', 3: '高' }, formatter: Table.api.formatter.status },
                        { field: 'review_status_develop', title: __('Review_status_develop'), custom: { 0: 'yellow', 1: 'success', 2: 'danger' }, searchList: { 0: '待审核', 1: '审核通过', 2: '审核拒绝' }, formatter: Table.api.formatter.status },
                        {
                            field: 'nickname', title: __('开发负责人'), operate: false, formatter: function (value, rows) {
                                var group = '<span>' + value + '</span>';
                                var web_distribution = '';
                                if (rows.review_status_develop == 1 && !rows.assign_developer_ids && Config.is_distribution == 1) {
                                    web_distribution = '<span><a href="#" class="btn btn-xs btn-primary web_distribution" data="' + rows.id + '"><i class="fa fa-list"></i>分配</a></span><br>';
                                }
                                return web_distribution + group;
                            },
                        },
                        { field: 'complexity', title: __('Complexity'), custom: { 1: 'success', 2: 'blue', 3: 'danger' }, searchList: { 1: '简单', 2: '中等', 3: '复杂' }, formatter: Table.api.formatter.status },
                        { field: 'is_test', title: __('Is_test'), custom: { 0: 'danger', 1: 'success' }, searchList: { 0: '否', 1: '是' }, formatter: Table.api.formatter.status },
                        { field: 'test_person', title: __('Test_person'), operate: false },
                        { field: 'is_finish', title: __('是否开发完成'), custom: { 0: 'danger', 1: 'success' }, searchList: { 0: '否', 1: '是' }, formatter: Table.api.formatter.status },
                        { field: 'test_is_passed', title: __('Test_is_passed'), custom: { 0: 'danger', 1: 'success' }, searchList: { 0: '否', 1: '是' }, formatter: Table.api.formatter.status },
                        { field: 'is_finish_task', title: __('产品经理是否确认'), custom: { 0: 'danger', 1: 'success' }, searchList: { 0: '否', 1: '是' }, formatter: Table.api.formatter.status },
                        { field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange', visible: false, formatter: Table.api.formatter.datetime },
                        { field: 'expected_time', title: __('Expected_time'), operate: 'RANGE', addclass: 'datetimerange', visible: false, formatter: Table.api.formatter.datetime },
                        { field: 'estimated_time', title: __('Estimated_time'), operate: 'RANGE', addclass: 'datetimerange', visible: false, formatter: Table.api.formatter.datetime },
                        { field: 'finish_time', title: __('Finish_time'), operate: 'RANGE', addclass: 'datetimerange', visible: false, formatter: Table.api.formatter.datetime },
                        {
                            field: 'createtime',
                            title: __('时间'),
                            operate: false,
                            formatter: function (value, rows) {
                                var str = '';
                                str += '<div class="step_recept"><b class="step">创建时间：</b><b class="recept">' + value + '</b></div>';
                                if (rows.expected_time) {
                                    str += '<br><div class="step_recept"><b class="step">期望时间：</b><b class="recept">' + rows.expected_time + '</b></div>';
                                }
                                if (rows.estimated_time) {
                                    str += '<br><div class="step_recept"><b class="step">预计时间：</b><b class="recept">' + rows.estimated_time + '</b></div>';
                                }

                                if (rows.finish_time) {
                                    str += '<br><div class="step_recept"><b class="step">完成时间：</b><b class="recept">' + rows.finish_time + '</b></div>';
                                }
                                return str;
                            },
                        },
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, buttons: [
                                {
                                    name: 'ajax',
                                    text: '审核通过',
                                    title: __('审核通过'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-dialog',
                                    icon: 'fa fa-magic',
                                    url: 'demand/develop_demand/review',
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
                                        if (row.review_status_manager == 0 && Config.review_status_manager_btn == 1) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'ajax',
                                    text: '审核拒绝',
                                    title: __('审核拒绝'),
                                    classname: 'btn btn-xs btn-danger  btn-magic btn-dialog',
                                    icon: 'fa fa-magic',
                                    url: 'demand/develop_demand/review?label=refuse',
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
                                        if (row.review_status_manager == 0 && Config.review_status_manager_btn == 1) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                },

                                {
                                    name: 'ajax',
                                    text: '审核通过',
                                    title: __('审核通过'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    icon: 'fa fa-magic',
                                    url: 'demand/develop_demand/review_status_develop?review_status_develop=1',
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
                                        if (row.review_status_manager == 1 && row.review_status_develop == 0 && Config.review_status_btn == 1) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'ajax',
                                    text: '审核拒绝',
                                    title: __('审核拒绝'),
                                    classname: 'btn btn-xs btn-danger  btn-magic btn-ajax',
                                    icon: 'fa fa-magic',
                                    url: 'demand/develop_demand/review_status_develop?review_status_develop=2',
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
                                        if (row.review_status_manager == 1 && row.review_status_develop == 0 && Config.review_status_btn == 1) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                },

                                {
                                    name: 'test_distribution',
                                    text: __('测试分配'),
                                    title: __('测试分配'),
                                    extend: 'data-area = \'["50%","50%"]\'',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'demand/develop_demand/test_distribution',
                                    callback: function (data) {
                                    },
                                    visible: function (row) {
                                        if (row.is_test == 1 && !row.test_person && Config.test_btn == 1) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'ajax',
                                    text: '开发完成',
                                    title: __('开发完成'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    icon: 'fa fa-magic',
                                    url: 'demand/develop_demand/set_complete_status',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh', {});
                                    },
                                    error: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function (row) {
                                        if (row.is_test == 1) {
                                            if (row.is_finish == 0 && Config.is_set_status == 1 && row.review_status_develop == 1 && row.assign_developer_ids && row.test_person) {
                                                return true;
                                            } else {
                                                return false;
                                            }
                                        } else {
                                            if (row.is_finish == 0 && Config.is_set_status == 1 && row.review_status_develop == 1 && row.assign_developer_ids) {
                                                return true;
                                            } else {
                                                return false;
                                            }
                                        }
                                        
                                    }
                                },
                                {
                                    name: 'test_record_bug',
                                    text: __('记录问题'),
                                    title: __('记录问题'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'demand/develop_demand/test_record_bug',
                                    callback: function (data) {
                                    },
                                    visible: function (row) {
                                        if (row.is_finish == 1 && Config.test_record_bug == 1 && row.is_test == 1 && row.test_person && row.test_is_passed == 0) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'problem',
                                    text: '问题详情',
                                    title: __('问题详情'),
                                    extend: 'data-area = \'["70%","70%"]\'',
                                    classname: 'btn btn-xs btn-warning btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'demand/develop_demand/problem_detail',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        if (row.is_test == 1) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'ajax',
                                    text: '通过测试',
                                    title: __('通过测试'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    icon: 'fa fa-magic',
                                    url: 'demand/develop_demand/test_is_passed',
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

                                        if (row.test_is_passed == 0 && Config.test_is_passed == 1 && row.is_test == 1 && row.test_person && row.is_finish == 1) {
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
                                    url: 'demand/develop_demand/is_finish_task',
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
                                        if (row.is_test == 1) {
                                            if (row.test_is_passed == 1 && Config.is_finish_task == 1 && row.is_finish_task == 0) {
                                                return true;
                                            } else {
                                                return false;
                                            }
                                        } else {
                                            if (row.is_finish == 1 && Config.is_finish_task == 1 && row.is_finish_task == 0) {
                                                return true;
                                            } else {
                                                return false;
                                            }
                                        }

                                    }
                                },
                                {
                                    name: 'detail',
                                    text: '详情',
                                    title: __('查看详情'),
                                    extend: 'data-area = \'["80%","70%"]\'',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'demand/develop_demand/detail',
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
                                    url: 'demand/develop_demand/edit',
                                    extend: 'data-area = \'["80%","70%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        if (row.review_status_manager == 0 && Config.is_edit == 1) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'test',
                                    text: '记录问题',
                                    title: __('记录问题'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'demand/develop_demand/regression_test_info',
                                    extend: 'data-area = \'["70%","70%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        if (row.is_finish_task == 1 && row.is_test == 1 && Config.regression_test_info == 1 && row.is_test_complete == 0) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'ajax',
                                    text: '通过测试',
                                    title: __('通过测试'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    icon: 'fa fa-magic',
                                    url: 'demand/develop_demand/test_complete',
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
                                        if (row.is_finish_task == 1 && row.is_test == 1 && Config.test_complete == 1 && row.is_test_complete == 0) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                },
                            ], formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

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

            //审核通过 弹窗
            $(document).on('click', '.btn-open', function () {
                var id = Table.api.selectedids(table);
                if (id.length > 1) {
                    Toastr.error('只能选择一条记录进行审核');
                    return false;
                }
                //获取行数据
                var data = Table.api.getrowbyid(table, id);
                if (data.review_status_manager > 0) {
                    Toastr.error('此记录已审核！！');
                    return false;
                }
                var options = {
                    shadeClose: false,
                    shade: [0.3, '#393D49'],
                    area: ['60%', '60%'], //弹出层宽高
                    callback: function (value) {

                    }
                };
                Fast.api.open('demand/develop_demand/review?id=' + id, '审核通过', options);
            })

            //审核通过 弹窗
            $(document).on('click', '.btn-close', function () {
                var id = Table.api.selectedids(table);
                if (id.length > 1) {
                    Toastr.error('只能选择一条记录进行审核');
                    return false;
                }
                //获取行数据
                var data = Table.api.getrowbyid(table, id);
                if (data.review_status_manager > 0) {
                    Toastr.error('此记录已审核！！');
                    return false;
                }
                var options = {
                    shadeClose: false,
                    shade: [0.3, '#393D49'],
                    area: ['60%', '60%'], //弹出层宽高
                    callback: function (value) {

                    }
                };
                Fast.api.open('demand/develop_demand/review?label=refuse&id=' + id, '审核拒绝', options);
            })

            //分配负责人
            $(document).on('click', ".web_distribution", function () {
                var id = $(this).attr('data');
                var options = {
                    shadeClose: false,
                    shade: [0.3, '#393D49'],
                    area: ['60%', '60%'], //弹出层宽高
                    callback: function (value) {

                    }
                };
                Fast.api.open('demand/develop_demand/distribution?id=' + id, '分配', options);
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
        review: function () {
            Controller.api.bindevent();
        },
        review_refuse: function () {
            Controller.api.bindevent();
        },
        distribution: function () {
            Controller.api.bindevent();
        },
        test_distribution: function () {
            Controller.api.bindevent();
        },
        test_record_bug: function () {
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
                getcontent: function (value) {
                    if (value == null || value == undefined) {
                        value = '';
                    }
                    return '<div style="float: left;width: 100%;"><span class="btn-getcontent check_demand_content">查 看</span></div>';
                },
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
            },
            events: {//绑定事件的方法
                getcontent: {
                    //格式为：方法名+空格+DOM元素
                    'click .btn-getcontent': function (e, value, row, index) {
                        var str = '标题：' + row.title + '<br><hr>内容：' + value;
                        Layer.open({
                            closeBtn: 1,
                            title: "详情",
                            content: str
                        });
                    }
                }
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
            "max-width": "150px"
        }
    }
}


function update_responsibility_user(val) {
    var is_val = $(val).val();
    $('.responsibility_user_id').attr('name', '');
    $('#responsibility_user_id_' + is_val).attr('name', 'row[responsibility_user_id]');
}
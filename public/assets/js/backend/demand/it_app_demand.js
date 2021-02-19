define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'demand/it_app_demand/index' + location.search,
                    add_url: 'demand/it_app_demand/add',
                    edit_url: 'demand/it_app_demand/edit',
                    del_url: 'demand/it_app_demand/del',
                    multi_url: 'demand/it_app_demand/multi',
                    table: 'it_app_demand',
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
                        {field: 'it_web_demand_id', title: __('App需求ID')},
                        {field: 'create_time', title: __('提出时间'), operate:'RANGE',  operate: false,addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {
                            field: 'title',
                            title: __('标题'),
                            operate: false,
                            events: Controller.api.events.gettitle,
                            cellStyle: formatTableUnit,
                            formatter: Controller.api.formatter.gettitle,
                        },
                        {field: 'node_time', title: __('预计完成时间'), operate: false},
                        {field: 'develop_finish_status', title: __('开发进度'), operate: false},
                        {field: 'test_is_finish', title: __('测试完成'), searchList: { 0: '否', 1: '是' },},
                        {field: 'test_status', title: __('已上线'), operate: false},
                        {field: 'version_number', title: __('上线版本号')},
                        {field: 'version_number', title: __('完成时间节点'), operate: false},
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
            },
            formatter: {
                //
                getcontent: function (value, row) {
                    if (!value || value == undefined) {
                        return '--';
                    }
                    return '<div style="float: left;width: 100%;"><span class="btn-getcontent">' + row.remark + '</span></div>';
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
                //点击标题，弹出窗口
                gettitle: function (value) {
                    return '<a class="btn-gettitle" style="color: #333333!important;">' + value + '</a>';
                },
                //RDC点击标题，弹出窗口
                getrdctitle: function (value) {
                    return '<a class="btn-gettitle" style="color: #333333!important;">' + value + '</a>';
                },
                //点击评审，弹出窗口
                ge_pm_status: function (value, row, index) {
                    if (row.pm_audit_status == 1) {
                        return '<div><span class="check_pm_status status1_color">待审</span></div>';
                    }
                    if (row.pm_audit_status == 2) {
                        return '<div><span class="check_pm_status status2_color">Pending</span></div>';
                    }
                    if (row.pm_audit_status == 3) {
                        return '<div><span class="check_pm_status status3_color">通过</span></div>';
                    }
                    if (row.pm_audit_status == 4) {
                        return '<div><span class="check_pm_status status4_color">拒绝</span></div>';
                    }
                },
                //rdc点击评审,直接通过
                ge_rdcpm_status: function (value, row, index) {
                    if (row.pm_audit_status == 1) {
                        if (row.pm_status) {
                            return '<div><span class="check_rdcpm_status status1_color">待通过</span></div>';
                        } else {
                            return '<div><span class="check_rdcpm_status status1_color disabled">待通过</span></div>';
                        }
                    }
                    if (row.pm_audit_status == 3) {
                        return '<div><span class="check_rdcpm_status status3_color disabled">通过</span></div>';
                    }
                    if (row.pm_audit_status == 4) {
                        return '<div><span class="check_rdcpm_status status4_color disabled">拒绝</span></div>';
                    }
                },
                //开发进度点击弹窗
                get_develop_status: function (value, row, index) {
                    // if (row.status >= 2) {
                    if (row.develop_finish_status == 1) {
                        if (row.status ==1){
                            return '<div><span>未响应</span></div>';
                        }else{
                            return '<div><span class="check_develop_status status1_color">未响应</span></div>';
                        }

                    } else if (row.develop_finish_status == 2) {
                        return '<div><span class="check_develop_status status1_color">开发中</span></div>';
                    } else if (row.develop_finish_status == 3) {
                        if (row.status == 5) {
                            return '<div><span class="check_develop_status status4_color">开发完成</span></div>';
                        } else {
                            return '<div><span class="check_develop_status status3_color">开发完成</span></div>';
                        }
                    } else {
                        return '<div><span class="check_develop_status status3_color">拒绝</span></div>';
                    }
                    // } else {
                    //     return '-';
                    // }
                },
                //测试进度点击弹窗
                get_test_status: function (value, row, index) {
                    // if (row.status >= 2) {
                    if (row.test_status == 1) {
                        if (row.site_type.search('3') !== -1){
                            if (row.web_designer_group ==0  || row.phper_group ==0 ||  row.app_group ==0 ){
                                return '<div><span>未确认</span></div>';
                            }else{
                                return '<div><span class="check_test_status status1_color">未确认</span></div>';
                            }
                        }else{
                            if (row.web_designer_group ==0  || row.phper_group ==0 ){
                                return '<div><span>未确认</span></div>';
                            }else{
                                return '<div><span class="check_test_status status1_color">未确认</span></div>';
                            }
                        }

                    } else if (row.test_status == 2) {
                        if (row.test_group == 1) {
                            return '<div><span class="check_test_status status3_color">待测试</span></div>';
                        } else {
                            return '<div><span class="check_test_status status3_color">无需测试</span></div>';
                        }

                    } else if (row.test_status == 3) {
                        return '<div><span class="check_test_status status1_color">待通过</span></div>';
                    } else if (row.test_status == 4) {
                        return '<div><span class="check_test_status status1_color">待上线</span></div>';
                    } else if (row.test_status == 5) {
                        return '<div><span class="check_test_status status3_color">已上线</span></div>';
                    }
                    // } else {
                    //     return '-';
                    // }
                },
                //完成确认
                get_user_confirm: function (value, row, index) {
                    if (row.test_status == 5) {
                        //状态=5才可以点击通过
                        if (row.demand_pm_status && row.entry_user_id == Config.admin_id) {
                            //当前登录人有产品确认权限，并且当前登录人就是录入人，则一个按钮
                            if (row.entry_user_confirm == 1 && row.pm_confirm == 1) {
                                return '已确认';
                            } else {
                                return '<div><span class="check_user_confirm status1_color">确认</span></div>';
                            }
                        } else {
                            //如果是产品
                            if (row.demand_pm_status) {
                                if (row.pm_confirm == 1) {
                                    //产品已经确认
                                    if (row.entry_user_confirm == 1) {
                                        return '已确认';
                                    } else {
                                        return '产品已确认';
                                    }
                                } else {
                                    return '<div><span class="check_user_confirm status1_color">确认</span></div>';
                                }
                            }
                            //如果是提出人
                            if (row.entry_user_id == Config.admin_id) {
                                if (row.entry_user_confirm == 1) {
                                    //提出人已经确认
                                    if (row.pm_confirm == 1) {
                                        return '已确认';
                                    } else {
                                        return '提出人已确认';
                                    }
                                } else {
                                    return '<div><span class="check_user_confirm status1_color">确认</span></div>';
                                }
                            }
                            //如果是其他人
                            if (row.entry_user_confirm == 1 && row.pm_confirm == 1) {
                                return '已确认';
                            } else {
                                if (row.entry_user_confirm == 1) {
                                    return '提出人已确认';
                                }
                                if (row.pm_confirm == 1) {
                                    return '产品已确认';
                                }
                                return '未确认';
                            }
                        }
                    } else {
                        return '-'
                    }
                },

                //详情记录点击查看
                get_detail: function (value, row, index) {
                    return '<div><span class="check_detail">查看</span></div>';
                },
            },
            events: {
                //绑定事件的方法
                //点击备注查看全部
                getcontent: {
                    //格式为：方法名+空格+DOM元素
                    'click .btn-getcontent': function (e, value, row, index) {
                        var str = row.remark;
                        Layer.open({
                            closeBtn: 1,
                            title: "备注：",
                            content: str,
                            area: ['80%', '80%'],
                            anim: 0
                        });
                    }
                },
                //点击标题，弹出窗口
                gettitle: {
                    //格式为：方法名+空格+DOM元素
                    'click .btn-gettitle': function (e, value, row, index) {
                        Backend.api.open('demand/it_app_demand/edit/type/view/ids/' + row.id, __('任务查看'), { area: ['70%', '70%'] });
                    }
                },
                //RDC点击标题，弹出窗口
                getrdctitle: {
                    //格式为：方法名+空格+DOM元素
                    'click .btn-gettitle': function (e, value, row, index) {
                        Backend.api.open('demand/it_web_demand/edit/demand_type/2/type/view/ids/' + row.id, __('任务查看'), { area: ['70%', '70%'] });
                    }
                },
                //点击评审，弹出窗口
                ge_pm_status: {
                    'click .check_pm_status': function (e, value, row, index) {
                        Backend.api.open('demand/it_web_demand/edit/type/pm_audit/ids/' + row.id, __('任务评审'), { area: ['70%', '70%'] });
                    }
                },
                //RDC点击评审，弹出窗口
                ge_rdcpm_status: {
                    'click .check_rdcpm_status': function (e, value, row, index) {
                        if (row.pm_audit_status == 1) {
                            Backend.api.open('demand/it_web_demand/rdc_demand_pass/ids/' + row.id, __('任务评审'), { area: ['70%', '70%'] });
                        }
                        /*Backend.api.ajax({
                            url: 'demand/it_web_demand/rdc_demand_pass/ids/' + row.id,
                        }, function (data, ret) {
                            $("#table").bootstrapTable('refresh');
                        }, function (data, ret) {
                            //失败的回调
                            $("#table").bootstrapTable('refresh');
                        });*/
                    }
                },
                //开发进度，弹出窗口
                get_develop_status: {
                    'click .check_develop_status': function (e, value, row, index) {
                        Backend.api.open('demand/it_web_demand/distribution/ids/' + row.id, __('开发进度'), { area: ['80%', '55%'] });
                    }
                },
                //测试进度
                get_test_status: {
                    'click .check_test_status': function (e, value, row, index) {
                        Backend.api.open('demand/it_web_demand/test_handle/ids/' + row.id, __('测试进度'), { area: ['40%', '50%'] });
                    }
                },
                //完成确认
                get_user_confirm: {
                    'click .check_user_confirm': function (e, value, row, index) {
                        layer.confirm('确认本需求？', {
                            btn: ['确认', '取消'] //按钮
                        }, function () {
                            Backend.api.ajax({
                                url: 'demand/it_web_demand/add/is_user_confirm/1/ids/' + row.id,
                            }, function (data, ret) {
                                $("#table1").bootstrapTable('refresh');
                                Layer.closeAll();
                            }, function (data, ret) {
                                //失败的回调
                                Layer.closeAll();
                                return false;
                            });
                        }, function () {
                            Layer.closeAll();
                        });
                    }
                },

                //详情记录点击查看
                get_detail: {
                    'click .check_detail': function (e, value, row, index) {
                        Backend.api.open('demand/it_web_demand/detail/ids/' + row.id, __('详情记录'), { area: ['70%', '60%'] });
                    }
                },
            }
        }
    };
    return Controller;
});
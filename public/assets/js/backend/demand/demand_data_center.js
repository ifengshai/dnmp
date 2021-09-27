define(['jquery', 'bootstrap', 'backend', 'addtabs', 'table','form','echartsobj', 'echarts', 'echarts-theme', 'template','custom-css'], function ($, undefined, Backend, Datatable, Table,Form, EchartObj, undefined, Template) {

    var Controller = {
        index: function () {
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'demand/demand_data_center/confirm_list' + location.search,
                }
            });
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var panel = $($(this).attr("href"));
                if (panel.size() > 0) {
                    console.log(111);
                    Controller.table[panel.attr("id")].call(this);
                    //导致多次请求
                    // $(this).on('click', function (e) {
                    //     $($(this).attr("href")).find(".btn-refresh").trigger("click");
                    //
                    // });
                }
                $(this).unbind('shown.bs.tab');
            });
            $('.panel-heading .nav-tabs li a').on('click', function (e) {
                var field = $(this).data("field");
                var create_time=$("#create_time").val();
                var value = $(this).data("value");

                if ($(this).attr("href") == '#first') {
                    var table = $('#table1');
                } else {
                    var table = $('#table2');
                }
                var options = table.bootstrapTable('getOptions');
                options.pageNumber = 1;
                var queryParams = options.queryParams;
                options.queryParams = function (params) {
                    var params = queryParams(params);
                    var filter = params.filter ? JSON.parse(params.filter) : {};
                    var op = params.op ? JSON.parse(params.op) : {};
                    if (field == '') {
                        delete filter.label;
                    } else {
                        console.log(value);
                        filter[field] = value;
                    }

                    params.filter = JSON.stringify(filter);
                    params.op = JSON.stringify(op);
                    return params;
                };
                table.bootstrapTable('refresh', {});
            });
            $('ul.nav-tabs li.active a[data-toggle="tab"]').trigger("shown.bs.tab");
            Controller.api.formatter.daterangepicker($("form[role=form1]"));

        },
        table: {
            first: function () {
                // 表格1
                var table1 = $("#table1");
                table1.bootstrapTable({
                    url: $.fn.bootstrapTable.defaults.extend.index_url,
                    toolbar: '#toolbar1',
                    pk: 'id',
                    sortName: 'id',
                    columns: [
                        [
                            { field: 'id', title: __('Id'), operate: '=' },
                            {
                                field: 'site',
                                title: __('项目'),
                                searchList: {
                                    1: 'Zeelool',
                                    2: 'Voogueme',
                                    3: 'Meeloog',
                                    4: 'Vicmoo',
                                    5: 'Wesee',
                                    6: 'Rufoo',
                                    7: 'Toloog',
                                    8: 'Other',
                                    9: 'ZeeloolEs',
                                    10: 'ZeeloolDe',
                                    11: 'ZeeloolJp',
                                    12: 'voogmechic',
                                    15: 'ZeeloolFr',
                                    66: '网红管理系统',
                                },
                                custom: {
                                    1: 'black',
                                    2: 'black',
                                    3: 'black',
                                    4: 'black',
                                    5: 'black',
                                    6: 'black',
                                    7: 'black',
                                    8: 'black',
                                    9: 'black',
                                    10: 'black',
                                    11: 'black'
                                },
                                formatter: Table.api.formatter.status
                            },
                            { field: 'task_user_name', title: __('任务人'), operate: 'like', visible: false },

                            { field: 'entry_user_name', title: __('提出人'), operate: false },
                            {
                                field: 'type',
                                title: __('任务类型'),
                                searchList: { 1: 'Bug', 2: '维护', 3: '优化', 4: '新功能', 5: '开发' },
                                custom: { 1: 'red', 2: 'blue', 3: 'blue', 4: 'blue', 5: 'green' },
                                formatter: Table.api.formatter.status
                            },
                            {
                                field: 'functional_module',
                                title: __('功能模块'),
                                searchList: { 1: '购物车', 2: '个人中心', 3: '列表页', 4: '详情页', 5: '首页', 6: '优惠券', 7: '支付页', 8: 'magento后台',9:'活动页',10:'其他' },
                                formatter: Table.api.formatter.status
                            },
                            {
                                field: 'title',
                                title: __('标题'),
                                operate: false,
                                events: Controller.api.events.gettitle,
                                cellStyle: formatTableUnit,
                                formatter: Controller.api.formatter.gettitle,
                            },

                            { field: 'create_time', title: __('创建时间'), operate: false },
                            {
                                field: 'pm_audit_status',
                                title: __('产品评审'),
                                searchList: { 1: '待审', 2: 'Pending', 3: '通过', 4: '已拒绝' },
                                formatter: Controller.api.formatter.ge_pm_status,
                                operate: false
                            },
                            {
                                field: 'priority',
                                title: __('优先级'),
                                searchList: {'': '-', 0: '-', 1: '低', 2: '低+', 3: '中', 4: '中+', 5: '高', 6: '高+'},
                                custom: {1: 'black', 2: 'black', 3: 'black', 4: 'black', 5: 'black'},
                                formatter: Table.api.formatter.status,
                                operate: false
                            },
                            {field: 'node_time', title: __('期望时间'), operate: false},
                            {
                                field: 'status',
                                title: __('开发评审'),
                                searchList: { 1: '未激活', 3: '已响应', 4: '完成', 5: '超时完成' },
                                custom: { 1: 'gray', 2: 'blue', 3: 'green', 4: 'gray', 5: 'yellow' },
                                formatter: Table.api.formatter.status,
                                operate: false
                            },
                            {
                                field: 'develop_finish_status',
                                title: __('开发进度'),
                                events: Controller.api.events.get_develop_status,
                                formatter: Controller.api.formatter.get_develop_status,
                                operate: false
                            },
                            {
                                field: 'test_status',
                                title: __('测试进度'),
                                events: Controller.api.events.get_test_status,
                                formatter: Controller.api.formatter.get_test_status,
                                operate: false
                            },
                            {
                                field: 'all_finish_time',
                                title: __('完成时间节点'),
                                operate: false,
                                formatter: function (value, rows) {
                                    var all_user_name = '';
                                    if (rows.develop_finish_time) {
                                        all_user_name += '<span class="all_user_name">开发：<b>' + rows.develop_finish_time + '</b></span><br>';
                                    }

                                    if (rows.test_is_finish == 1) {
                                        all_user_name += '<span class="all_user_name">测试：<b>' + rows.test_finish_time + '</b></span><br>';
                                    }

                                    if (rows.all_finish_time) {
                                        all_user_name += '<span class="all_user_name">上线：<b>' + rows.all_finish_time + '</b></span><br>';
                                    }
                                    if (all_user_name == '') {
                                        all_user_name = '-';
                                    }

                                    return all_user_name;
                                },
                            },
                            {
                                field: 'entry_user_confirm',
                                title: __('完成确认'),
                                events: Controller.api.events.get_user_confirm,
                                formatter: Controller.api.formatter.get_user_confirm,
                                operate: false
                            },
                            {
                                field: 'detail',
                                title: __('详情记录'),
                                events: Controller.api.events.get_detail,
                                formatter: Controller.api.formatter.get_detail,
                                operate: false
                            },
                            {
                                field: 'web_designer_user_name',
                                title: __('前端'),
                                operate: false,
                                formatter: function (value, rows) {
                                    var all_user_name = '';
                                    if (rows.web_designer_user_name) {
                                        for (var i in rows.web_designer_user_name) {
                                            all_user_name += rows.web_designer_user_name[i] + '<br>';
                                        }
                                    }
                                    return all_user_name ? all_user_name : '-';
                                },
                            },
                            {
                                field: 'php_user_name',
                                title: __('后端'),
                                operate: false,
                                formatter: function (value, rows) {
                                    var all_user_name = '';
                                    if (rows.php_user_name) {
                                        for (var i in rows.php_user_name) {
                                            all_user_name += rows.php_user_name[i] + '<br>';
                                        }
                                    }
                                    return all_user_name ? all_user_name : '-';
                                },
                            },
                            {
                                field: 'app_user_name',
                                title: __('APP'),
                                operate: false,
                                formatter: function (value, rows) {
                                    var all_user_name = '';
                                    if (rows.app_user_name) {
                                        for (var i in rows.app_user_name) {
                                            all_user_name += rows.app_user_name[i] + '<br>';
                                        }
                                    }
                                    return all_user_name ? all_user_name : '-';
                                },
                            },
                            {
                                field: 'test_user_name',
                                title: __('测试'),
                                operate: false,
                                formatter: function (value, rows) {
                                    var all_user_name = '';
                                    if (rows.test_user_name) {
                                        for (var i in rows.test_user_name) {
                                            all_user_name += rows.test_user_name[i] + '<br>';
                                        }
                                    }
                                    return all_user_name ? all_user_name : '-';
                                },
                            },


                        ]
                    ],
                    queryParams: function (params) {
                        //这里可以追加搜索条件
                        var filter = JSON.parse(params.filter);
                        var op = JSON.parse(params.op);
                        var create_time=$("#create_time").val();
                        //这里可以动态赋值，比如从URL中获取admin_id的值，filter.admin_id=Fast.api.query('admin_id');
                        filter.create_time=create_time;
                        op.create_time = "=";
                        params.filter = JSON.stringify(filter);
                        params.op = JSON.stringify(op);
                        return params;
                    },
                });

                // 为表格1绑定事件
                Table.api.bindevent(table1);

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
                //批量导出xls
                $('.btn-batch-export-xls').click(function () {

                    var ids = Table.api.selectedids(table1);
                    if (ids.length > 0) {
                        window.open(Config.moduleurl + '/demand/it_web_demand/batch_export_xls?type=1&ids=' + ids, '_blank');
                    } else {
                        var options = table1.bootstrapTable('getOptions');
                        var search = options.queryParams({});
                        var filter = search.filter;
                        var op = search.op;
                        window.open(Config.moduleurl + '/demand/it_web_demand/batch_export_xls?type=1&filter=' + filter + '&op=' + op, '_blank');
                    }
                    // window.open(Config.moduleurl + '/demand/it_web_demand/batch_export_xls?type=1', '_blank');
                });
            },
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
                Form.api.bindevent($("form[role=form1]"));
            },
            formatter: {
                //
                daterangepicker: function (form) {
                    console.log("daterangepicker222222222222222222222222");
                    //绑定日期时间元素事件
                    if ($(".datetimerange", form).size() > 0) {
                        require(['bootstrap-daterangepicker'], function () {
                            var ranges = {};
                            ranges[__('Today')] = [Moment().startOf('day'), Moment().endOf('day')];
                            ranges[__('Yesterday')] = [Moment().subtract(1, 'days').startOf('day'), Moment().subtract(1, 'days').endOf('day')];
                            ranges[__('Last 7 Days')] = [Moment().subtract(6, 'days').startOf('day'), Moment().endOf('day')];
                            ranges[__('Last 30 Days')] = [Moment().subtract(29, 'days').startOf('day'), Moment().endOf('day')];
                            ranges[__('This Month')] = [Moment().startOf('month'), Moment().endOf('month')];
                            ranges[__('Last Month')] = [Moment().subtract(1, 'month').startOf('month'), Moment().subtract(1, 'month').endOf('month')];
                            var options = {
                                timePicker: false,
                                autoUpdateInput: false,
                                timePickerSeconds: true,
                                timePicker24Hour: true,
                                autoApply: true,
                                locale: {
                                    format: 'YYYY-MM-DD HH:mm:ss',
                                    customRangeLabel: __("Custom Range"),
                                    applyLabel: __("Apply"),
                                    cancelLabel: __("Clear"),
                                },
                                ranges: ranges,
                                timePicker: true,
                                timePickerIncrement: 1
                            };
                            var origincallback = function (start, end) {
                                $(this.element).val(start.format(this.locale.format) + " - " + end.format(this.locale.format));
                                $(this.element).trigger('blur');
                            };
                            $(".datetimerange", form).each(function () {
                                var callback = typeof $(this).data('callback') == 'function' ? $(this).data('callback') : origincallback;
                                $(this).on('apply.daterangepicker', function (ev, picker) {
                                    callback.call(picker, picker.startDate, picker.endDate);
                                });
                                $(this).on('cancel.daterangepicker', function (ev, picker) {
                                    $(this).val('').trigger('blur');
                                });
                                $(this).daterangepicker($.extend({}, options, $(this).data()), callback);
                            });
                        });
                    }
                },
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
                        Backend.api.open('demand/it_web_demand/edit/type/view/ids/' + row.id, __('任务查看'), { area: ['70%', '70%'] });
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

        },

    };
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

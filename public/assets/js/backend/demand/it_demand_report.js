define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init();

            //绑定事件
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var panel = $($(this).attr("href"));
                if (panel.size() > 0) {
                    Controller.table[panel.attr("id")].call(this);
                    $(this).on('click', function (e) {
                        $($(this).attr("href")).find(".btn-refresh").trigger("click");
                    });
                }
                //移除绑定的事件
                $(this).unbind('shown.bs.tab');
            });

            //必须默认触发shown.bs.tab事件
            $('ul.nav-tabs li.active a[data-toggle="tab"]').trigger("shown.bs.tab");
        },
        warehouse_data: function () {
            Controller.api.formatter.daterangepicker($("form[role=form1]"));
        },
        table: {
            first: function () {
                Table.api.init({
                    extend: {
                        index_url: 'demand/it_demand_report/demand_list' + location.search,
                    }
                });
                // 表格1
                var table = $("#table1");
                table.bootstrapTable({
                    url: $.fn.bootstrapTable.defaults.extend.index_url,
                    pk: 'id',
                    toolbar: '#toolbar1',
                    sortName: 'id',
                    columns: [
                        [
                            {field: 'id', title: __('Id'),operate:'='},
                            {
                                field: 'status',
                                title: __('Status'),
                                visible:false,
                                searchList: { 1: 'NEW', 2: '测试已确认', 3: '开发ing' , 4: '开发已完成', 5: '待上线', 6: '待回归测试'},
                                formatter: Table.api.formatter.status
                            },
                            {
                                field: 'site_type',
                                title: __('Site_type'),
                                searchList: { 1: 'Zeelool', 2: 'Voogueme', 3: 'Nihao' , 4: 'Wesee', 5: 'Orther'},
                                formatter: Table.api.formatter.status
                            },
                            {
                                field: 'type',
                                title: __('Problem_ype'),
                                searchList: { 1:'bug',2:'需求',3:'疑难'},
                                formatter: Table.api.formatter.status
                            },

                            {field: 'title', title: __('title'),operate:false},
                            {field: 'entry_user_id', title: __('Entry_user_id'),visible:false,operate:false},
                            {field: 'entry_user_name', title: __('Entry_user_id'),operate:false},
                            {field: 'title', title: __('Title'),visible:false,operate:'LIKE'},
                            {field: 'hope_time', title: __('Hope_time'), operate:'RANGE', addclass:'datetimerange',operate:false},
                            {
                                field: 'Allgroup_sel',
                                title: __('All_group'),
                                visible:false,
                                searchList: { 1: '前端', 2: '后端', 3: 'APP' , 4: '测试'},
                                formatter: Table.api.formatter.status
                            },
                            {
                                field: 'Allgroup',
                                title: __('All_group'),
                                operate:false,
                                formatter: function (value, rows) {
                                    var res = '';
                                    if(value){
                                        for(i = 0,len = value.length; i < len; i++){
                                            res += value[i] + '</br>';
                                        }
                                    }
                                    var group = '<span>'+res+'</span>';
                                    var web_distribution = '';
                                    return  web_distribution + group;
                                },
                            },
                            {
                                field: 'all_user_id',
                                title: __('all_user_id'),
                                operate: false,
                                formatter: function (value, rows) {
                                    var all_user_name = '';
                                    if(rows.web_designer_group == 1){
                                        all_user_name += '<span class="all_user_name">前端：<b>'+ rows.web_designer_user_name + '</b></span><br>';
                                    }
                                    if(rows.phper_group == 1){
                                        all_user_name += '<span class="all_user_name">后端：<b>'+ rows.phper_user_name + '</b></span><br>';
                                    }
                                    if(rows.app_group == 1){
                                        all_user_name += '<span class="all_user_name">APP：<b>'+ rows.app_user_name + '</b></span><br>';
                                    }
                                    return all_user_name;
                                },
                            },
                            {
                                field: 'all_expect_time',
                                title: __('all_expect_time'),
                                operate: false,
                                formatter: function (value, rows) {
                                    var all_user_name = '';
                                    if(rows.web_designer_group == 1){
                                        all_user_name += '<span class="all_user_name">前端：<b>'+ rows.web_designer_expect_time + '</b></span><br>';
                                    }
                                    if(rows.phper_group == 1){
                                        all_user_name += '<span class="all_user_name">后端：<b>'+ rows.phper_expect_time + '</b></span><br>';
                                    }
                                    if(rows.app_group == 1){
                                        all_user_name += '<span class="all_user_name">APP：<b>'+ rows.app_expect_time + '</b></span><br>';
                                    }
                                    return all_user_name;
                                },
                            },
                            { field: 'test_group', title: __('Test_group'), custom: { 2: 'danger', 1: 'success',0: 'black' }, searchList: { 1: '是', 2: '否', 0:'未确认' }, formatter: Table.api.formatter.status },
                            {
                                field: 'test_user_id_arr',
                                title: __('test_user_id'),
                                operate: false,
                                formatter: function (value, rows) {
                                    var res = '';
                                    if(value){
                                        for(i = 0,len = value.length; i < len; i++){
                                            res += value[i] + '</br>';
                                        }
                                        var group = '<span>'+res+'</span>';
                                        return  group;
                                    }else{
                                        return '-';
                                    }

                                },
                            },
                            {field: 'status_str', title: __('Status'),operate:false},
                        ]
                    ]
                });

                // 为表格1绑定事件
                Table.api.bindevent(table);
            },
            second: function () {
                Table.api.init({
                    extend: {
                        index_url: 'demand/it_demand_report/undone_task' + location.search,
                    }
                });
                // 表格2
                var tableDemand = $("#table2");
                tableDemand.bootstrapTable({
                    url: $.fn.bootstrapTable.defaults.extend.index_url,
                    pk: 'id',
                    sortName: 'id',
                    toolbar: "#toolbar2",
                    columns: [
                        [
                            {field: 'id', title: __('Id'),operate:'='},

                            {
                                field: 'site_type',
                                title: __('Site_type'),
                                searchList: { 1: 'Zeelool', 2: 'Voogueme', 3: 'Nihao' , 4: 'Wesee', 5: 'Orther'},
                                formatter: Table.api.formatter.status
                            },
                            { field: 'type', title: __('Task_ype'), custom: { 1: 'success', 2: 'black', 3: 'danger' }, searchList: { 1: '短期任务', 2: '中期任务', 3: '长期任务' }, formatter: Table.api.formatter.status },
                            { field: 'title', title: __('Title') },
                            { field: 'closing_date', title: __('Closing_date') , operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime },
                            {
                                field: 'is_test_adopt', title: __('Is_test_adopt'), custom: { 1: 'success', 0: 'danger' },
                                searchList: { 1: '是', 0: '否' },
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
                        ]
                    ]
                });

                // 为表格2绑定事件
                Table.api.bindevent(tableDemand);
                $(document).on('click', '.btn-list', function () {
                    var options = {
                        shadeClose: false,
                        shade: [0.3, '#393D49'],
                        area: ['80%', '70%'], //弹出层宽高
                        callback: function (value) {
                        }
                    };
                    var ids = $(this).data('id');
                    Fast.api.open('demand/it_web_task/item?ids=' + ids, '关键结果', options);
                })
            }
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

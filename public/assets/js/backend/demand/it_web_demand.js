define(['jquery', 'bootstrap', 'backend', 'table', 'form','nkeditor', 'upload'], function ($, undefined, Backend, Table, Form,Nkeditor, Upload) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'demand/it_web_demand/index' + location.search,
                    add_url: 'demand/it_web_demand/add',
                    edit_url: 'demand/it_web_demand/edit/demand_type/2',
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
                        {field: 'id', title: __('Id'),operate:'='},
                        {
                            field: 'site',
                            title: __('项目'),
                            searchList: { 1: 'Zeelool', 2: 'Voogueme', 3: 'Nihao' , 4: 'Meeloog', 5: 'Wesee', 6:'Rufoo',7:'Toloog',8:'Other'},
                            custom:{1: 'black', 2: 'black', 3: 'black' , 4: 'black', 5: 'black', 6:'black',7:'black',8:'black'},
                            formatter: Table.api.formatter.status
                        },
                        {field: 'entry_user_id', title: __('提出人'),visible:false},
                        {field: 'entry_user_name', title: __('提出人')},
                        {
                            field: 'type',
                            title: __('类型'),
                            searchList: { 1: 'Bug', 2: '维护', 3: '优化' , 4: '新功能', 5: '开发'},
                            custom:{1: 'red', 2: 'blue', 3: 'blue' , 4: 'blue', 5: 'green'},
                            formatter: Table.api.formatter.status
                        },

                        {
                            field: 'title',
                            title: __('标题'),
                            operate: 'LIKE',
                            events: Controller.api.events.gettitle,
                            cellStyle: formatTableUnit,
                            formatter: Controller.api.formatter.gettitle,
                        },

                        {field: 'create_time', title: __('创建时间'), operate: 'RANGE'},

                        {
                            field: 'pm_audit_status',
                            title: __('评审'),
                            events: Controller.api.events.ge_pm_status,
                            formatter: Controller.api.formatter.ge_pm_status,
                        },
                        {
                            field: 'priority',
                            title: __('优先级'),
                            searchList: { '':'-', 1: 'D1', 2: 'D2', 3: 'V1' , 4: 'V2', 5: 'V3'},
                            custom:{1: 'black', 2: 'black', 3: 'black' , 4: 'black', 5: 'black'},
                            formatter: Table.api.formatter.status
                        },
                        {field: 'node_time', title: __('任务周期'),operate:false},
                        {
                            field: 'status',
                            title: __('任务状态'),
                            searchList: { 1: '未激活', 2: '激活', 3: '已响应' , 4: '完成', 5: '超时完成'},
                            custom:{1: 'gray', 2: 'blue', 3: 'green' , 4: 'gray', 5: 'yellow'},
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'develop_finish_status',
                            title: __('开发进度'),
                            events: Controller.api.events.get_develop_status,
                            formatter: Controller.api.formatter.get_develop_status,
                        },
                        {
                            field: 'test_status',
                            title: __('测试进度'),
                            events: Controller.api.events.get_test_status,
                            formatter: Controller.api.formatter.get_test_status,
                        },
                        /*{field: 'develop_finish_time', title: __('开发完成时间'), formatter: Table.api.formatter.datetime},
                        {field: 'test_finish_time', title: __('测试完成时间'), formatter: Table.api.formatter.datetime},*/
                        {
                            field: 'all_finish_time',
                            title: __('时间节点'),
                            operate: false,
                            formatter: function (value, rows) {
                                var all_user_name = '';
                                if(rows.develop_finish_time){
                                    all_user_name += '<span class="all_user_name">开发完成：<b>'+ rows.develop_finish_time + '</b></span><br>';
                                }

                                if(rows.test_group == 1){
                                    if(rows.test_is_finish == 1){
                                        all_user_name += '<span class="all_user_name">测试完成：<b>'+ rows.test_finish_time + '</b></span><br>';
                                    }
                                }
                                if(rows.all_finish_time){
                                    all_user_name += '<span class="all_user_name">完&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;成：<b>'+ rows.all_finish_time + '</b></span><br>';
                                }
                                if(all_user_name == ''){
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
                        },


                        /*{field: 'web_designer_user_id', title: __('前端'),operate:false},
                        {field: 'phper_user_id', title: __('后端'),operate:false},
                        {field: 'app_user_id', title: __('APP'),operate:false},
                        {field: 'test_user_id', title: __('测试'),operate:false},*/

                        {
                            field: 'all_user_id',
                            title: __('责任人'),
                            operate: false,
                            formatter: function (value, rows) {
                                var all_user_name = '';
                                if(rows.web_designer_user_id){
                                    all_user_name += '<span class="all_user_name">前端：<b>'+ rows.web_designer_user_name + '</b></span><br>';
                                }
                                if(rows.phper_user_id){
                                    all_user_name += '<span class="all_user_name">后端：<b>'+ rows.php_user_name + '</b></span><br>';
                                }
                                if(rows.app_user_id){
                                    all_user_name += '<span class="all_user_name">APP：<b>'+ rows.app_user_name + '</b></span><br>';
                                }
                                if(rows.test_user_id){
                                    all_user_name += '<span class="all_user_name">测试：<b>'+ rows.test_user_name + '</b></span><br>';
                                }
                                return all_user_name;
                            },
                        },

                        {
                            field: 'detail',
                            title: __('详情记录'),
                            events: Controller.api.events.get_detail,
                            formatter: Controller.api.formatter.get_detail,
                        },




                        /*{field: 'entry_user_name', title: __('Entry_user_id')},
                        {field: 'all_user_name', title: __('all_user_id'), visible:false},


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
                                if(rows.status == 3 && rows.demand_distribution){
                                    web_distribution ='<span><a href="#" class="btn btn-xs btn-primary web_distribution" data="'+rows.id+'"><i class="fa fa-list"></i>分配</a></span><br>';
                                }
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
                        /!*{field: 'testgroup', title: __('Test_group'),operate:false},*!/
                        {
                            field: 'test_user_id_arr',
                            title: __('test_user_id'),
                            operate: 'in',
                            searchList: { 195: '马红亚', 200: '陈亚蒙', 255:'陈玉晓', 242:'张鹏' },
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
                        {field: 'create_time', title: __('create_time'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        /!*{field: 'all_finish_time', title: __('时间节点'), operate:'RANGE', addclass:'datetimerange',operate:false},*!/

                        {
                            field: 'all_finish_time',
                            title: __('时间节点'),
                            operate: false,
                            formatter: function (value, rows) {
                                var all_user_name = '';
                                all_user_name += '<span class="all_user_name">创&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;建：<b>'+ rows.create_time + '</b></span><br>';
                                if(rows.test_group != 0){
                                    all_user_name += '<span class="all_user_name">测试确认：<b>'+ rows.test_confirm_time + '</b></span><br>';
                                }
                                if(rows.test_group == 1){
                                    if(rows.test_is_finish == 1){
                                        all_user_name += '<span class="all_user_name">测试完成：<b>'+ rows.test_finish_time + '</b></span><br>';
                                    }
                                }
                                if(rows.all_finish_time){
                                    all_user_name += '<span class="all_user_name">完&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;成：<b>'+ rows.all_finish_time + '</b></span><br>';
                                }

                                return all_user_name;
                            },
                        },

                        {field: 'status_str', title: __('Status'),operate:false},
                        {
                            field: 'buttons',
                            width: "120px",
                            operate:false,
                            title: __('操作'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'test_distribution',
                                    text: __('测试确认'),
                                    title: __('测试确认'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'demand/it_web_demand/test_distribution/demand_type/2',
                                    callback: function (data) {
                                    },
                                    visible: function(row){
                                        if(row.status == 1){
                                            if(row.demand_test_distribution){//操作权限
                                                return true;
                                            }
                                        }else{
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'through_demand',
                                    text: __('通过'),
                                    title: __('通过'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    icon: 'fa fa-magic',
                                    url: 'demand/it_web_demand/through_demand',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
                                    },
                                    error: function (data, ret) {
                                        console.log(data, ret);
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function(row){
                                        if(row.status == 2){
                                            if(row.demand_through_demand){//操作权限
                                                return true;
                                            }
                                        }else{
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'group_finish',
                                    text: __('完成'),
                                    title: __('完成'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'demand/it_web_demand/group_finish/demand_type/2',
                                    callback: function (data) {
                                    },
                                    visible: function(row){
                                        if(row.status == 3){
                                            if(row.demand_finish){//操作权限
                                                if(row.web_designer_group == 0 && row.phper_group == 0 && row.app_group == 0){
                                                    return false;
                                                }else{
                                                    return true;
                                                }
                                            }
                                        }
                                    }
                                },
                                {
                                    name: 'test_record_bug',
                                    text: __('记录问题'),
                                    title: __('记录问题'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'demand/it_web_demand/test_record_bug',
                                    callback: function (data) {
                                    },
                                    visible: function(row){
                                        if(row.status == 4){
                                            if(row.demand_test_record_bug && row.is_test_record_hidden == 1){//操作权限及显示权限
                                                if(row.test_is_finish == 0){
                                                    return true;
                                                }else{
                                                    return false;
                                                }
                                            }
                                        }
                                    }
                                },
                                {
                                    name: 'test_group_finish',
                                    text: __('通过测试'),
                                    title: __('通过测试'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    url: 'demand/it_web_demand/test_group_finish',
                                    confirm: '请确定是否  <b>通过测试</b>',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
                                        //如果需要阻止成功提示，则必须使用return false;
                                        //return false;
                                    },
                                    error: function (data, ret) {
                                        console.log(data, ret);
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function(row){
                                        if(row.status == 4){
                                            if(row.demand_test_finish && row.is_test_finish_hidden == 1){//操作权限及显示权限
                                                if(row.test_group == 1 && row.test_is_finish == 0){
                                                    return true;
                                                }else{
                                                    return false;
                                                }
                                            }
                                        }

                                    }
                                },
                                {
                                    name: 'add',
                                    text: __('提出人确认'),
                                    title: __('提出人确认'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    icon: 'fa fa-magic',
                                    url: 'demand/it_web_demand/add/is_user_confirm/1',
                                    confirm: '确认本需求？',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
                                    },
                                    error: function (data, ret) {
                                        console.log(data, ret);
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function(row){
                                        if(row.status == 4 || row.status == 5){
                                            if(row.demand_add && row.is_entry_user_hidden == 1){//操作权限及显示权限
                                                if(row.test_group == 1){
                                                    if(row.entry_user_confirm == 0){
                                                        return true;
                                                    }
                                                }
                                            }
                                        }else{
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'add_online',
                                    text: __('上线'),
                                    title: __('上线'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    icon: 'fa fa-magic',
                                    url: 'demand/it_web_demand/add_online',
                                    confirm: '确定上线？',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
                                    },
                                    error: function (data, ret) {
                                        console.log(data, ret);
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function(row){
                                        if(row.status == 5){
                                            if(row.demand_add_online){//操作权限
                                                if(row.test_group == 1){
                                                    if(row.entry_user_confirm == 0){
                                                        return false;
                                                    }else{
                                                        return true;
                                                    }
                                                }else{
                                                    return true;
                                                }
                                            }
                                        }else{
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'test_record_bug',
                                    text: __('记录问题'),
                                    title: __('记录回归测试问题'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'demand/it_web_demand/test_record_bug',
                                    callback: function (data) {
                                    },
                                    visible: function(row){
                                        if(row.status == 6){
                                            if(row.demand_test_record_bug && row.is_test_record_hidden == 1){//操作权限及显示权限
                                                if(row.return_test_is_finish == 0){
                                                    return true;
                                                }else{
                                                    return false;
                                                }
                                            }
                                        }
                                    }
                                },
                                {
                                    name: 'test_group_finish',
                                    text: __('通过测试'),
                                    title: __('通过回归测试'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    url: 'demand/it_web_demand/test_group_finish/is_all_test/1',
                                    confirm: '请确定是否  <b>通过测试</b>',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
                                        //如果需要阻止成功提示，则必须使用return false;
                                        //return false;
                                    },
                                    error: function (data, ret) {
                                        console.log(data, ret);
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function(row){
                                        if(row.status == 6){
                                            if(row.demand_test_finish && row.is_test_finish_hidden == 1){//操作权限及显示权限
                                                if(row.test_group == 1 && row.return_test_is_finish == 0){
                                                    return true;
                                                }else{
                                                    return false;
                                                }
                                            }
                                        }

                                    }
                                },
                                {
                                    name: 'detail_log',
                                    text: __('详情记录'),
                                    title: __('详情记录'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'demand/it_web_demand/detail_log/demand_type/2',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
                                        //如果需要阻止成功提示，则必须使用return false;
                                        //return false;
                                    },
                                    error: function (data, ret) {
                                        console.log(data, ret);
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function(row){
                                        if(row.test_group == 0 || row.test_group == 2){
                                            return false;
                                        }else{
                                            if(row.status == 7){
                                                return true;
                                            }else{
                                                if(row.status >= 4 && row.is_test_detail_log != 0){
                                                    return true;
                                                }else{
                                                    return false;
                                                }
                                            }
                                        }
                                    }
                                },

                                {
                                    name: 'edit',
                                    text: __(''),
                                    title: __('编辑'),
                                    icon: 'fa fa-pencil',
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    url: 'demand/it_web_demand/edit/demand_type/2',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
                                    },
                                    callback: function (data) {
                                    },
                                    visible: function(row){
                                        if(row.demand_supper_edit){
                                            return true;
                                        }else{
                                            if(row.status <= 4){
                                                if(row.demand_del){//操作权限
                                                    return true;
                                                }
                                            }else{
                                                return false;
                                            }
                                        }

                                        /!* if(row.status == 1 || row.status == 2){
                                            if(row.demand_del && row.is_entry_user_hidden == 1){//操作权限
                                                return true;
                                            }
                                        }else{
                                            return false;
                                        } *!/
                                    }
                                },
                                {
                                    name: 'del',
                                    text: __(''),
                                    title: __('删除'),
                                    icon: 'fa fa-trash',
                                    classname: 'btn btn-xs btn-danger btn-magic btn-ajax',
                                    url: 'demand/it_web_demand/del',
                                    confirm: '是否删除?',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
                                    },
                                    callback: function (data) {
                                    },
                                    visible: function(row){
                                        if(row.demand_del || row.is_entry_user_hidden == 1){//操作权限
                                            return true;
                                        }
                                    }
                                },
                            ],
                            formatter: Table.api.formatter.buttons
                        },*/
                    ]
                ]
            });
            $('.panel-heading a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var field = $(this).data("field");
                var value = $(this).data("value");
                // console.log(field);
                // console.log(value);
                var options = table.bootstrapTable('getOptions');
                options.pageNumber = 1;
                var queryParams = options.queryParams;
                options.queryParams = function (params) {
                    var params = queryParams(params);
                    var filter = params.filter ? JSON.parse(params.filter) : {};
                    var op     = params.op ? JSON.parse(params.op) : {};
                    if(field == 'me_task'){
                        filter[field] = value;
                        delete filter.none_complete;
                    }else if(field == 'none_complete'){
                        filter[field] = value;
                        delete filter.me_task;
                    }else{
                        delete filter.me_task;
                        delete filter.none_complete;
                    }
                    params.filter = JSON.stringify(filter);
                    params.op     = JSON.stringify(op);
                    return params;
                };
                table.bootstrapTable('refresh', {});
                return false;
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
            //需求详情

            $(document).on('click', ".web_distribution", function () {
                var id = $(this).attr('data');
                var options = {
                    shadeClose: false,
                    shade: [0.3, '#393D49'],
                    area: ['100%', '100%'], //弹出层宽高
                    callback: function (value) {

                    }
                };
                Fast.api.open('demand/it_web_demand/distribution?id=' + id, '分配', options);
            });
        },
        index1: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'demand/it_web_demand/index' + location.search,
                    add_url: 'demand/it_web_demand/add/demand_type/2',
                    edit_url: 'demand/it_web_demand/edit/demand_type/2',
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
                        {field: 'id', title: __('Id'),operate:'='},
                        {
                            field: 'status',
                            title: __('Status'),
                            visible:false,
                            searchList: { 1: 'NEW', 2: '测试已确认', 3: '开发ing' , 4: '开发已完成', 5: '待上线', 6: '待回归测试',7: '已完成'},
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'site_type',
                            title: __('Site_type'),
                            searchList: { 1: 'Zeelool', 2: 'Voogueme', 3: 'Nihao' , 4: 'Wesee', 5: 'Other', 6:'如弗小程序'},
                            formatter: Table.api.formatter.status
                        },
                        {field: 'entry_user_id', title: __('Entry_user_id'),visible:false,operate:false},
                        {field: 'entry_user_name', title: __('Entry_user_id')},
                        {field: 'all_user_name', title: __('all_user_id'), visible:false},
                        {field: 'title', title: __('Title'),cellStyle: formatTableUnit,operate:'LIKE'},
                        {
                            field: 'content',
                            operate:false,
                            title: __('content_detail'),
                            events: Controller.api.events.getcontent,
                            formatter: Controller.api.formatter.getcontent,
                        },
                        {field: 'hope_time', title: __('Hope_time'), operate:'RANGE', addclass:'datetimerange'},
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
                                if(rows.status == 3 && rows.demand_distribution){
                                    web_distribution ='<span><a href="#" class="btn btn-xs btn-primary web_distribution" data="'+rows.id+'"><i class="fa fa-list"></i>分配</a></span><br>';
                                }
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
                        /*{field: 'testgroup', title: __('Test_group'),operate:false},*/
                        {
                            field: 'test_user_id_arr',
                            title: __('test_user_id'),
                            operate: 'in',
                            searchList: { 195: '马红亚', 200: '陈亚蒙', 255:'陈玉晓', 242:'张鹏' },
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
                        {field: 'create_time', title: __('create_time'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        /*{field: 'all_finish_time', title: __('时间节点'), operate:'RANGE', addclass:'datetimerange',operate:false},*/

                        {
                            field: 'all_finish_time',
                            title: __('时间节点'),
                            operate: false,
                            formatter: function (value, rows) {
                                var all_user_name = '';
                                all_user_name += '<span class="all_user_name">创&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;建：<b>'+ rows.create_time + '</b></span><br>';
                                if(rows.test_group != 0){
                                    all_user_name += '<span class="all_user_name">测试确认：<b>'+ rows.test_confirm_time + '</b></span><br>';
                                }
                                if(rows.test_group == 1){
                                    if(rows.test_is_finish == 1){
                                        all_user_name += '<span class="all_user_name">测试完成：<b>'+ rows.test_finish_time + '</b></span><br>';
                                    }
                                }
                                if(rows.all_finish_time){
                                    all_user_name += '<span class="all_user_name">完&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;成：<b>'+ rows.all_finish_time + '</b></span><br>';
                                }

                                return all_user_name;
                            },
                        },

                        {field: 'status_str', title: __('Status'),operate:false},
                        {
                            field: 'buttons',
                            width: "120px",
                            operate:false,
                            title: __('操作'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'test_distribution',
                                    text: __('测试确认'),
                                    title: __('测试确认'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'demand/it_web_demand/test_distribution/demand_type/2',
                                    callback: function (data) {
                                    },
                                    visible: function(row){
                                        if(row.status == 1){
                                            if(row.demand_test_distribution){//操作权限
                                                return true;
                                            }
                                        }else{
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'through_demand',
                                    text: __('通过'),
                                    title: __('通过'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    icon: 'fa fa-magic',
                                    url: 'demand/it_web_demand/through_demand',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
                                    },
                                    error: function (data, ret) {
                                        console.log(data, ret);
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function(row){
                                        if(row.status == 2){
                                            if(row.demand_through_demand){//操作权限
                                                return true;
                                            }
                                        }else{
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'group_finish',
                                    text: __('完成'),
                                    title: __('完成'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'demand/it_web_demand/group_finish/demand_type/2',
                                    callback: function (data) {
                                    },
                                    visible: function(row){
                                        if(row.status == 3){
                                            if(row.demand_finish){//操作权限
                                                if(row.web_designer_group == 0 && row.phper_group == 0 && row.app_group == 0){
                                                    return false;
                                                }else{
                                                    return true;
                                                }
                                            }
                                        }
                                    }
                                },
                                {
                                    name: 'test_record_bug',
                                    text: __('记录问题'),
                                    title: __('记录问题'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'demand/it_web_demand/test_record_bug',
                                    callback: function (data) {
                                    },
                                    visible: function(row){
                                        if(row.status == 4){
                                            if(row.demand_test_record_bug && row.is_test_record_hidden == 1){//操作权限及显示权限
                                                if(row.test_is_finish == 0){
                                                    return true;
                                                }else{
                                                    return false;
                                                }
                                            }
                                        }
                                    }
                                },
                                {
                                    name: 'test_group_finish',
                                    text: __('通过测试'),
                                    title: __('通过测试'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    url: 'demand/it_web_demand/test_group_finish',
                                    confirm: '请确定是否  <b>通过测试</b>',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
                                        //如果需要阻止成功提示，则必须使用return false;
                                        //return false;
                                    },
                                    error: function (data, ret) {
                                        console.log(data, ret);
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function(row){
                                        if(row.status == 4){
                                            if(row.demand_test_finish && row.is_test_finish_hidden == 1){//操作权限及显示权限
                                                if(row.test_group == 1 && row.test_is_finish == 0){
                                                    return true;
                                                }else{
                                                    return false;
                                                }
                                            }
                                        }

                                    }
                                },
                                {
                                    name: 'add',
                                    text: __('提出人确认'),
                                    title: __('提出人确认'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    icon: 'fa fa-magic',
                                    url: 'demand/it_web_demand/add/is_user_confirm/1',
                                    confirm: '确认本需求？',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
                                    },
                                    error: function (data, ret) {
                                        console.log(data, ret);
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function(row){
                                        if(row.status == 4 || row.status == 5){
                                            if(row.demand_add && row.is_entry_user_hidden == 1){//操作权限及显示权限
                                                if(row.test_group == 1){
                                                    if(row.entry_user_confirm == 0){
                                                        return true;
                                                    }
                                                }
                                            }
                                        }else{
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'add_online',
                                    text: __('上线'),
                                    title: __('上线'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    icon: 'fa fa-magic',
                                    url: 'demand/it_web_demand/add_online',
                                    confirm: '确定上线？',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
                                    },
                                    error: function (data, ret) {
                                        console.log(data, ret);
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function(row){
                                        if(row.status == 5){
                                            if(row.demand_add_online){//操作权限
                                                if(row.test_group == 1){
                                                    if(row.entry_user_confirm == 0){
                                                        return false;
                                                    }else{
                                                        return true;
                                                    }
                                                }else{
                                                    return true;
                                                }
                                            }
                                        }else{
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'test_record_bug',
                                    text: __('记录问题'),
                                    title: __('记录回归测试问题'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'demand/it_web_demand/test_record_bug',
                                    callback: function (data) {
                                    },
                                    visible: function(row){
                                        if(row.status == 6){
                                            if(row.demand_test_record_bug && row.is_test_record_hidden == 1){//操作权限及显示权限
                                                if(row.return_test_is_finish == 0){
                                                    return true;
                                                }else{
                                                    return false;
                                                }
                                            }
                                        }
                                    }
                                },
                                {
                                    name: 'test_group_finish',
                                    text: __('通过测试'),
                                    title: __('通过回归测试'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    url: 'demand/it_web_demand/test_group_finish/is_all_test/1',
                                    confirm: '请确定是否  <b>通过测试</b>',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
                                        //如果需要阻止成功提示，则必须使用return false;
                                        //return false;
                                    },
                                    error: function (data, ret) {
                                        console.log(data, ret);
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function(row){
                                        if(row.status == 6){
                                            if(row.demand_test_finish && row.is_test_finish_hidden == 1){//操作权限及显示权限
                                                if(row.test_group == 1 && row.return_test_is_finish == 0){
                                                    return true;
                                                }else{
                                                    return false;
                                                }
                                            }
                                        }

                                    }
                                },
                                {
                                    name: 'detail_log',
                                    text: __('详情记录'),
                                    title: __('详情记录'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'demand/it_web_demand/detail_log/demand_type/2',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
                                        //如果需要阻止成功提示，则必须使用return false;
                                        //return false;
                                    },
                                    error: function (data, ret) {
                                        console.log(data, ret);
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function(row){
                                        if(row.test_group == 0 || row.test_group == 2){
                                            return false;
                                        }else{
                                            if(row.status == 7){
                                                return true;
                                            }else{
                                                if(row.status >= 4 && row.is_test_detail_log != 0){
                                                    return true;
                                                }else{
                                                    return false;
                                                }
                                            }
                                        }
                                    }
                                },

                                {
                                    name: 'edit',
                                    text: __(''),
                                    title: __('编辑'),
                                    icon: 'fa fa-pencil',
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    url: 'demand/it_web_demand/edit/demand_type/2',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
                                    },
                                    callback: function (data) {
                                    },
                                    visible: function(row){
                                        if(row.demand_supper_edit){
                                            return true;
                                        }else{
                                            if(row.status <= 4){
                                                if(row.demand_del){//操作权限
                                                    return true;
                                                }
                                            }else{
                                                return false;
                                            }
                                        }

                                        /* if(row.status == 1 || row.status == 2){
                                            if(row.demand_del && row.is_entry_user_hidden == 1){//操作权限
                                                return true;
                                            }
                                        }else{
                                            return false;
                                        } */
                                    }
                                },
                                {
                                    name: 'del',
                                    text: __(''),
                                    title: __('删除'),
                                    icon: 'fa fa-trash',
                                    classname: 'btn btn-xs btn-danger btn-magic btn-ajax',
                                    url: 'demand/it_web_demand/del',
                                    confirm: '是否删除?',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
                                    },
                                    callback: function (data) {
                                    },
                                    visible: function(row){
                                        if(row.demand_del || row.is_entry_user_hidden == 1){//操作权限
                                            return true;
                                        }
                                    }
                                },
                            ],
                            formatter: Table.api.formatter.buttons
                        },
                    ]
                ]
            });
            $('.panel-heading a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var field = $(this).data("field");
                var value = $(this).data("value");
                // console.log(field);
                // console.log(value);
                var options = table.bootstrapTable('getOptions');
                options.pageNumber = 1;
                var queryParams = options.queryParams;
                options.queryParams = function (params) {
                    var params = queryParams(params);
                    var filter = params.filter ? JSON.parse(params.filter) : {};
                    var op     = params.op ? JSON.parse(params.op) : {};
                    if(field == 'me_task'){
                        filter[field] = value;
                        delete filter.none_complete;
                    }else if(field == 'none_complete'){
                        filter[field] = value;
                        delete filter.me_task;
                    }else{
                        delete filter.me_task;
                        delete filter.none_complete;
                    }
                    params.filter = JSON.stringify(filter);
                    params.op     = JSON.stringify(op);
                    return params;
                };
                table.bootstrapTable('refresh', {});
                return false;
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
            //需求详情

            $(document).on('click', ".web_distribution", function () {
                var id = $(this).attr('data');
                var options = {
                    shadeClose: false,
                    shade: [0.3, '#393D49'],
                    area: ['100%', '100%'], //弹出层宽高
                    callback: function (value) {

                    }
                };
                Fast.api.open('demand/it_web_demand/distribution?id=' + id, '分配', options);
            });
        },
        bug_list: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'demand/it_web_demand/bug_list' + location.search,
                    add_url: 'demand/it_web_demand/add/demand_type/1',
                    edit_url: 'demand/it_web_demand/edit/demand_type/1',
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
                        {field: 'id', title: __('Id'),operate:'='},
                        {
                            field: 'status',
                            title: __('Status'),
                            visible:false,
                            searchList: { 1: 'NEW', 2: '测试已确认', 3: '开发ing' , 4: '开发已完成', 5: '待上线', 6: '待回归测试',7: '已完成'},
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'site_type',
                            title: __('Site_type'),
                            searchList: { 1: 'Zeelool', 2: 'Voogueme', 3: 'Nihao' , 4: 'Wesee', 5: 'Other', 6:'如弗小程序'},
                            formatter: Table.api.formatter.status
                        },
                        {field: 'entry_user_id', title: __('Entry_user_id'),visible:false,operate:false},
                        {field: 'entry_user_name', title: __('Entry_user_id'),operate:false},
                        {field: 'title', title: __('Title'),cellStyle: formatTableUnit,operate:'LIKE',},
                        {
                            field: 'content',
                            operate:false,
                            title: __('content_detail'),
                            events: Controller.api.events.getcontent,
                            formatter: Controller.api.formatter.getcontent,
                        },
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
                                if(rows.status == 3 && rows.demand_distribution){
                                    web_distribution ='<span><a href="#" class="btn btn-xs btn-primary web_distribution" data="'+rows.id+'"><i class="fa fa-list"></i>分配</a></span><br>';
                                }
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
                        { field: 'test_group', title: __('Test_group'), custom: { 2: 'danger', 1: 'success',0: 'black' }, searchList: { 1: '是', 2: '否', 0:'未确认' }, formatter: Table.api.formatter.status },
                        /*{field: 'testgroup', title: __('Test_group'),operate:false},*/
                        {
                            field: 'test_user_id_arr',
                            title: __('test_user_id'),
                            operate: 'in',
                            searchList: { 195: '马红亚', 200: '陈亚蒙', 255:'陈玉晓', 242:'张鹏' },
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
                        {field: 'create_time', title: __('create_time'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {
                            field: 'is_small_probability',
                            title: __('is_small_probability'),
                            operate:false,
                            formatter: function (value, rows) {
                                var str = '';
                                if(value == 0){
                                    str = '<a href="javascript:;" class="is_small_probability" data-toggle="tooltip" title="" data-field="is_small_probability" data-value="1" data-id="'+rows.id+'" data-original-title="标记为小概率"><span class="text-black"><i class="fa fa-circle"></i> 否</span></a>'
                                }
                                if(value == 1){
                                    str = '<a href="javascript:;" class="is_small_probability" data-toggle="tooltip" title="" data-field="is_small_probability" data-value="0" data-id="'+rows.id+'" data-original-title="修改为非小概率"><span class="text-danger"><i class="fa fa-circle"></i> 是</span></a>';
                                }
                                return str;
                            },
                        },
                        {field: 'all_finish_time', title: __('all_finish_time'), operate:'RANGE', addclass:'datetimerange',operate:false},
                        {
                            field: 'create_time',
                            title: __('时间节点'),
                            operate: false,
                            formatter: function (value, rows) {
                                var all_user_name = '';
                                all_user_name += '<span class="all_user_name">创&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;建：<b>'+ rows.create_time + '</b></span><br>';
                                if(rows.test_group != 0){
                                    all_user_name += '<span class="all_user_name">测试确认：<b>'+ rows.test_confirm_time + '</b></span><br>';
                                }
                                if(rows.test_group == 1){
                                    if(rows.test_is_finish == 1){
                                        all_user_name += '<span class="all_user_name">测试完成：<b>'+ rows.test_finish_time + '</b></span><br>';
                                    }
                                }
                                if(rows.all_finish_time){
                                    all_user_name += '<span class="all_user_name">完&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;成：<b>'+ rows.all_finish_time + '</b></span><br>';
                                }
                                return all_user_name;
                            },
                        },

                        {field: 'status_str', title: __('Status'),operate:false},
                        {
                            field: 'is_work_time', title: __('是否为加班处理'),
                            searchList: { 1: '是', 0: '否' },
                            custom: { 1: 'blue', 2: 'yellow' },
                            visible:false,
                            operate:false,
                            formatter: Table.api.formatter.status
                        },{
                            field: 'is_test_duty', title: __('是否扣除测试绩效'),
                            searchList: { 1: '是', 0: '否' },
                            custom: { 1: 'blue', 2: 'yellow' },
                            visible:false,
                            operate:false,
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'buttons',
                            width: "120px",
                            operate:false,
                            title: __('操作'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'test_distribution',
                                    text: __('测试确认'),
                                    title: __('测试确认'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'demand/it_web_demand/test_distribution/demand_type/1',
                                    callback: function (data) {
                                    },
                                    visible: function(row){
                                        if(row.status == 1){
                                            if(row.demand_test_distribution){//操作权限
                                                return true;
                                            }
                                        }else{
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'group_finish',
                                    text: __('完成'),
                                    title: __('完成'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'demand/it_web_demand/group_finish/demand_type/1',
                                    callback: function (data) {
                                    },
                                    visible: function(row){
                                        if(row.status == 3){
                                            if(row.demand_finish){//操作权限
                                                if(row.web_designer_group == 0 && row.phper_group == 0 && row.app_group == 0){
                                                    return false;
                                                }else{
                                                    return true;
                                                }
                                            }
                                        }
                                    }
                                },
                                {
                                    name: 'test_record_bug',
                                    text: __('记录问题'),
                                    title: __('记录问题'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'demand/it_web_demand/test_record_bug',
                                    callback: function (data) {
                                    },
                                    visible: function(row){
                                        if(row.status == 4){
                                            if(row.demand_test_record_bug && row.is_test_record_hidden == 1){//操作权限及显示权限
                                                if(row.test_is_finish == 0){
                                                    return true;
                                                }else{
                                                    return false;
                                                }
                                            }
                                        }
                                    }
                                },
                                {
                                    name: 'test_group_finish',
                                    text: __('通过测试'),
                                    title: __('通过测试'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    url: 'demand/it_web_demand/test_group_finish',
                                    confirm: '请确定是否  <b>通过测试</b>',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
                                        //如果需要阻止成功提示，则必须使用return false;
                                        //return false;
                                    },
                                    error: function (data, ret) {
                                        console.log(data, ret);
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function(row){
                                        if(row.status == 4){
                                            if(row.demand_test_finish && row.is_test_finish_hidden == 1){//操作权限及显示权限
                                                if(row.test_group == 1 && row.test_is_finish == 0){
                                                    return true;
                                                }else{
                                                    return false;
                                                }
                                            }
                                        }

                                    }
                                },
                                {
                                    name: 'add',
                                    text: __('提出人确认'),
                                    title: __('提出人确认'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    icon: 'fa fa-magic',
                                    url: 'demand/it_web_demand/add/is_user_confirm/1',
                                    confirm: '确认本需求？',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
                                    },
                                    error: function (data, ret) {
                                        console.log(data, ret);
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function(row){
                                        if(row.status == 4 || row.status == 5){
                                            if(row.demand_add && row.is_entry_user_hidden == 1){//操作权限及显示权限
                                                if(row.test_group == 1){
                                                    if(row.entry_user_confirm == 0){
                                                        return true;
                                                    }
                                                }
                                            }
                                        }else{
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'add_online',
                                    text: __('上线'),
                                    title: __('上线'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    icon: 'fa fa-magic',
                                    url: 'demand/it_web_demand/add_online',
                                    confirm: '确定上线？',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
                                    },
                                    error: function (data, ret) {
                                        console.log(data, ret);
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function(row){
                                        if(row.status == 5){
                                            if(row.demand_add_online){//操作权限
                                                if(row.test_group == 1){
                                                    if(row.entry_user_confirm == 0){
                                                        return false;
                                                    }else{
                                                        return true;
                                                    }
                                                }else{
                                                    return true;
                                                }
                                            }
                                        }else{
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'test_record_bug',
                                    text: __('记录问题'),
                                    title: __('记录回归测试问题'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'demand/it_web_demand/test_record_bug',
                                    callback: function (data) {
                                    },
                                    visible: function(row){
                                        if(row.status == 6){
                                            if(row.demand_test_record_bug && row.is_test_record_hidden == 1){//操作权限及显示权限
                                                if(row.return_test_is_finish == 0){
                                                    return true;
                                                }else{
                                                    return false;
                                                }
                                            }
                                        }
                                    }
                                },
                                {
                                    name: 'test_group_finish',
                                    text: __('通过测试'),
                                    title: __('通过回归测试'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    url: 'demand/it_web_demand/test_group_finish/is_all_test/1',
                                    confirm: '请确定是否  <b>通过测试</b>',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
                                        //如果需要阻止成功提示，则必须使用return false;
                                        //return false;
                                    },
                                    error: function (data, ret) {
                                        console.log(data, ret);
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function(row){
                                        if(row.status == 6){
                                            if(row.demand_test_finish && row.is_test_finish_hidden == 1){//操作权限及显示权限
                                                if(row.test_group == 1 && row.return_test_is_finish == 0){
                                                    return true;
                                                }else{
                                                    return false;
                                                }
                                            }
                                        }

                                    }
                                },
                                {
                                    name: 'detail_log',
                                    text: __('详情记录'),
                                    title: __('详情记录'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'demand/it_web_demand/detail_log/demand_type/1',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
                                        //如果需要阻止成功提示，则必须使用return false;
                                        //return false;
                                    },
                                    error: function (data, ret) {
                                        console.log(data, ret);
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function(row){
                                        if(row.test_group == 0 || row.test_group == 2){
                                            return false;
                                        }else{
                                            if(row.status == 7){
                                                return true;
                                            }else{
                                                if(row.status >= 4 && row.is_test_detail_log != 0){
                                                    return true;
                                                }else{
                                                    return false;
                                                }
                                            }
                                        }
                                    }
                                },
                                {
                                    name: 'opt_test_duty',
                                    text: '测试责任',
                                    title: __('将扣除测试绩效'),
                                    classname: 'btn btn-xs btn-danger btn-ajax',
                                    url: 'demand/it_web_demand/opt_test_duty/is_test_duty/1',
                                    confirm: '确认测试责任吗,将扣除测试绩效',
                                    success: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        $(".btn-refresh").trigger("click");
                                        //如果需要阻止成功提示，则必须使用return false;
                                        //return false;
                                    },
                                    error: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        if(row.demand_opt_test_duty && row.is_test_duty == 0){//操作权限及显示权限
                                            return true;
                                        }else {
                                            return false;
                                        }
                                    },
                                },{
                                    name: 'opt_test_duty',
                                    text: '非测试责任',
                                    title: __('无需扣除测试绩效'),
                                    classname: 'btn btn-xs btn-success btn-ajax',
                                    url: 'demand/it_web_demand/opt_test_duty',
                                    confirm: '确认非测试责任吗,无需扣除测试绩效',
                                    success: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        $(".btn-refresh").trigger("click");
                                        //如果需要阻止成功提示，则必须使用return false;
                                        //return false;
                                    },
                                    error: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        if(row.demand_opt_test_duty && row.is_test_duty == 1){//操作权限及显示权限
                                            return true;
                                        }else {
                                            return false;
                                        }
                                    },
                                },


                                {
                                    name: 'opt_work_time',
                                    text: '改为加班处理',
                                    title: __('改为加班处理'),
                                    classname: 'btn btn-xs btn-warning btn-ajax',
                                    url: 'demand/it_web_demand/opt_work_time/is_work_time/1',
                                    confirm: '是否改为加班时间此处理问题',
                                    success: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        $(".btn-refresh").trigger("click");
                                        //如果需要阻止成功提示，则必须使用return false;
                                        //return false;
                                    },
                                    error: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        if(row.demand_opt_work_time && row.is_work_time == 0){//操作权限及显示权限
                                            return true;
                                        }else {
                                            return false;
                                        }
                                    },
                                },{//是否非工作时间处理问题  0 否 不是非工作时间处理问题  1 是 是非工作时间处理问题
                                    name: 'opt_work_time',
                                    text: '改为工作时间处理',
                                    title: __('改为工作时间处理'),
                                    classname: 'btn btn-xs btn-success btn-ajax',
                                    url: 'demand/it_web_demand/opt_work_time',
                                    confirm: '是否改为工作时间处理此问题',
                                    success: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        $(".btn-refresh").trigger("click");
                                        //如果需要阻止成功提示，则必须使用return false;
                                        //return false;
                                    },
                                    error: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        if(row.demand_opt_work_time && row.is_work_time == 1){//操作权限及显示权限
                                            return true;
                                        }else {
                                            return false;
                                        }
                                    },
                                },
                                {
                                    name: 'edit',
                                    text: __(''),
                                    title: __('编辑'),
                                    icon: 'fa fa-pencil',
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    url: 'demand/it_web_demand/edit/demand_type/1',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
                                    },
                                    callback: function (data) {
                                    },
                                    visible: function(row){
                                        if(row.status == 1 || row.status == 2){
                                            if(row.demand_del && row.is_entry_user_hidden == 1){//操作权限
                                                return true;
                                            }
                                            //创建人显示
                                            if(row.entry_user_id == Config.admin_id){
                                                return true;
                                            }
                                        }else{
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'del',
                                    text: __(''),
                                    title: __('删除'),
                                    icon: 'fa fa-trash',
                                    classname: 'btn btn-xs btn-danger btn-magic btn-ajax',
                                    url: 'demand/it_web_demand/del',
                                    confirm: '是否删除?',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
                                    },
                                    callback: function (data) {
                                    },
                                    visible: function(row){
                                        if(row.demand_del || row.is_entry_user_hidden == 1){//操作权限
                                            return true;
                                        }

                                    }
                                },
                            ],
                            formatter: Table.api.formatter.buttons
                        },
                    ]
                ]
            });

            $('.panel-heading a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var field = $(this).data("field");
                var value = $(this).data("value");
                // console.log(field);
                // console.log(value);
                var options = table.bootstrapTable('getOptions');
                options.pageNumber = 1;
                var queryParams = options.queryParams;
                options.queryParams = function (params) {
                    var params = queryParams(params);
                    var filter = params.filter ? JSON.parse(params.filter) : {};
                    var op     = params.op ? JSON.parse(params.op) : {};
                    if(field == 'me_task'){
                        filter[field] = value;
                        delete filter.none_complete;
                    }else if(field == 'none_complete'){
                        filter[field] = value;
                        delete filter.me_task;
                    }else{
                        delete filter.me_task;
                        delete filter.none_complete;
                    }
                    params.filter = JSON.stringify(filter);
                    params.op     = JSON.stringify(op);
                    return params;
                };
                table.bootstrapTable('refresh', {});
                return false;
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
            //需求详情

            $(document).on('click', ".web_distribution", function () {
                var id = $(this).attr('data');
                var options = {
                    shadeClose: false,
                    shade: [0.3, '#393D49'],
                    area: ['100%', '100%'], //弹出层宽高
                    callback: function (value) {

                    }
                };
                Fast.api.open('demand/it_web_demand/distribution?id=' + id, '分配', options);
            });

            $(document).on('click', ".is_small_probability", function () {

                var id = $(this).attr('data-id');
                var val = $(this).attr('data-value');

                Layer.confirm(
                    __('确定要执行操作么'),
                    function (index) {
                        Backend.api.ajax({
                            url: "demand/it_web_demand/through_demand",
                            data: { ids: id,val: val,small_probability:1 }
                        }, function (data, ret) {
                            table.bootstrapTable('refresh');
                            Layer.close(index);
                        });
                    }
                );



                console.log(id);
                console.log(val);
                return false;
                var options = {
                    shadeClose: false,
                    shade: [0.3, '#393D49'],
                    area: ['100%', '100%'], //弹出层宽高
                    callback: function (value) {

                    }
                };
                Fast.api.open('demand/it_web_demand/distribution?id=' + id, '分配', options);
            });
        },
        difficult_list: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'demand/it_web_demand/difficult_list' + location.search,
                    add_url: 'demand/it_web_demand/add/demand_type/3',
                    edit_url: 'demand/it_web_demand/edit/demand_type/3',
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
                        {field: 'id', title: __('Id'),operate:'='},
                        {
                            field: 'status',
                            title: __('Status'),
                            visible:false,
                            searchList: { 1: 'NEW', 2: '测试已确认', 3: '开发ing' , 4: '开发已完成', 5: '待上线', 6: '待回归测试',7: '已完成'},
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'site_type',
                            title: __('Site_type'),
                            searchList: { 1: 'Zeelool', 2: 'Voogueme', 3: 'Nihao' , 4: 'Wesee', 5: 'Other'},
                            formatter: Table.api.formatter.status
                        },
                        {field: 'entry_user_id', title: __('Entry_user_id'),visible:false,operate:false},
                        {field: 'entry_user_name', title: __('Entry_user_id'),operate:false},
                        {field: 'title', title: __('Title'),visible:false,operate:'LIKE'},
                        {
                            field: 'content',
                            operate:false,
                            title: __('content_detail'),
                            events: Controller.api.events.getcontent,
                            formatter: Controller.api.formatter.getcontent,
                        },
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
                                if(rows.status == 3 && rows.demand_distribution){
                                    web_distribution ='<span><a href="#" class="btn btn-xs btn-primary web_distribution" data="'+rows.id+'"><i class="fa fa-list"></i>分配</a></span><br>';
                                }
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

                        { field: 'test_group', title: __('Test_group'), custom: { 2: 'danger', 1: 'success',0: 'black' }, searchList: { 1: '是', 2: '否', 0:'未分配' }, formatter: Table.api.formatter.status },
                        /*{field: 'testgroup', title: __('Test_group'),operate:false},*/
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
                        {field: 'all_finish_time', title: __('all_finish_time'), operate:'RANGE', addclass:'datetimerange',operate:false},
                        {field: 'status_str', title: __('Status'),operate:false},
                        {
                            field: 'buttons',
                            width: "120px",
                            operate:false,
                            title: __('操作'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'test_distribution',
                                    text: __('分配测试'),
                                    title: __('分配测试'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'demand/it_web_demand/test_distribution/demand_type/3',
                                    callback: function (data) {
                                    },
                                    visible: function(row){
                                        if(row.status == 1){
                                            if(row.demand_test_distribution){//操作权限
                                                return true;
                                            }
                                        }else{
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'group_finish',
                                    text: __('完成'),
                                    title: __('完成'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'demand/it_web_demand/group_finish/demand_type/3',
                                    callback: function (data) {
                                    },
                                    visible: function(row){
                                        if(row.status == 3){
                                            if(row.demand_finish){//操作权限
                                                if(row.web_designer_group == 0 && row.phper_group == 0 && row.app_group == 0){
                                                    return false;
                                                }else{
                                                    return true;
                                                }
                                            }
                                        }
                                    }
                                },
                                {
                                    name: 'test_record_bug',
                                    text: __('记录问题'),
                                    title: __('记录问题'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'demand/it_web_demand/test_record_bug',
                                    callback: function (data) {
                                    },
                                    visible: function(row){
                                        if(row.status == 4){
                                            if(row.demand_test_record_bug && row.is_test_record_hidden == 1){//操作权限及显示权限
                                                if(row.test_is_finish == 0){
                                                    return true;
                                                }else{
                                                    return false;
                                                }
                                            }
                                        }
                                    }
                                },
                                {
                                    name: 'test_group_finish',
                                    text: __('通过测试'),
                                    title: __('通过测试'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    url: 'demand/it_web_demand/test_group_finish',
                                    confirm: '请确定是否  <b>通过测试</b>',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
                                        //如果需要阻止成功提示，则必须使用return false;
                                        //return false;
                                    },
                                    error: function (data, ret) {
                                        console.log(data, ret);
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function(row){
                                        if(row.status == 4){
                                            if(row.demand_test_finish && row.is_test_finish_hidden == 1){//操作权限及显示权限
                                                if(row.test_group == 1 && row.test_is_finish == 0){
                                                    return true;
                                                }else{
                                                    return false;
                                                }
                                            }
                                        }

                                    }
                                },
                                {
                                    name: 'add_online',
                                    text: __('上线'),
                                    title: __('上线'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    icon: 'fa fa-magic',
                                    url: 'demand/it_web_demand/add_online',
                                    confirm: '确定上线？',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
                                    },
                                    error: function (data, ret) {
                                        console.log(data, ret);
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function(row){
                                        if(row.status == 5){
                                            if(row.demand_add_online){//操作权限
                                                if(row.test_group == 1){
                                                    if(row.entry_user_confirm == 0){
                                                        return false;
                                                    }else{
                                                        return true;
                                                    }
                                                }else{
                                                    return true;
                                                }
                                            }
                                        }else{
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'test_record_bug',
                                    text: __('记录问题'),
                                    title: __('记录回归测试问题'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'demand/it_web_demand/test_record_bug',
                                    callback: function (data) {
                                    },
                                    visible: function(row){
                                        if(row.status == 6){
                                            if(row.demand_test_record_bug && row.is_test_record_hidden == 1){//操作权限及显示权限
                                                if(row.return_test_is_finish == 0){
                                                    return true;
                                                }else{
                                                    return false;
                                                }
                                            }
                                        }
                                    }
                                },
                                {
                                    name: 'test_group_finish',
                                    text: __('通过测试'),
                                    title: __('通过回归测试'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    url: 'demand/it_web_demand/test_group_finish/is_all_test/1',
                                    confirm: '请确定是否  <b>通过测试</b>',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
                                        //如果需要阻止成功提示，则必须使用return false;
                                        //return false;
                                    },
                                    error: function (data, ret) {
                                        console.log(data, ret);
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function(row){
                                        if(row.status == 6){
                                            if(row.demand_test_finish && row.is_test_finish_hidden == 1){//操作权限及显示权限
                                                if(row.test_group == 1 && row.return_test_is_finish == 0){
                                                    return true;
                                                }else{
                                                    return false;
                                                }
                                            }
                                        }

                                    }
                                },
                                {
                                    name: 'detail_log',
                                    text: __('详情记录'),
                                    title: __('详情记录'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'demand/it_web_demand/detail_log/demand_type/2',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
                                        //如果需要阻止成功提示，则必须使用return false;
                                        //return false;
                                    },
                                    error: function (data, ret) {
                                        console.log(data, ret);
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function(row){
                                        if(row.test_group == 0 || row.test_group == 2){
                                            return false;
                                        }else{
                                            if(row.status == 7){
                                                return true;
                                            }else{
                                                if(row.status >= 4 && row.is_test_detail_log != 0){
                                                    return true;
                                                }else{
                                                    return false;
                                                }
                                            }
                                        }
                                    }
                                },
                                {
                                    name: 'edit',
                                    text: __(''),
                                    title: __('编辑'),
                                    icon: 'fa fa-pencil',
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    url: 'demand/it_web_demand/edit/demand_type/3',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
                                    },
                                    callback: function (data) {
                                    },
                                    visible: function(row){
                                        return true;
                                        if(row.status == 1 || row.status == 2){
                                            if(row.demand_del && row.is_entry_user_hidden == 1){//操作权限
                                                return true;
                                            }
                                        }else{
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'del',
                                    text: __(''),
                                    title: __('删除'),
                                    icon: 'fa fa-trash',
                                    classname: 'btn btn-xs btn-danger btn-magic btn-ajax',
                                    url: 'demand/it_web_demand/del',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
                                    },
                                    callback: function (data) {
                                    },
                                    visible: function(row){
                                        return true;
                                        if(row.status == 1 || row.status == 2){
                                            if(row.demand_del && row.is_entry_user_hidden == 1){//操作权限
                                                return true;
                                            }
                                        }else{
                                            return false;
                                        }
                                    }
                                },
                            ],
                            formatter: Table.api.formatter.buttons
                        },
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
            //需求详情

            $(document).on('click', ".web_distribution", function () {
                var id = $(this).attr('data');
                var options = {
                    shadeClose: false,
                    shade: [0.3, '#393D49'],
                    area: ['100%', '100%'], //弹出层宽高
                    callback: function (value) {

                    }
                };
                Fast.api.open('demand/it_web_demand/distribution?id=' + id, '分配', options);
            });
        },
        add: function () {
            Controller.api.bindevent();
            $(".editor_nkeditor", $("form[role=form]")).each(function () {
                var that = this;
                Nkeditor.create(that, {
                    width: '100%',
                    height: '50%',
                    filterMode: false,
                    wellFormatMode: false,
                    allowMediaUpload: true, //是否允许媒体上传
                    allowFileManager: true,
                    allowImageUpload: true,
                    wordImageServer: typeof Config.nkeditor != 'undefined' && Config.nkeditor.wordimageserver ? "127.0.0.1:10101" : "", //word图片替换服务器的IP和端口
                    urlType: Config.upload.cdnurl ? 'domain' : '',//给图片加前缀
                    cssPath: Fast.api.cdnurl('/assets/addons/nkeditor/plugins/code/prism.css'),
                    cssData: "body {font-size: 13px}",
                    fillDescAfterUploadImage: false, //是否在上传后继续添加描述信息
                    themeType: typeof Config.nkeditor != 'undefined' ? Config.nkeditor.theme : 'black', //编辑器皮肤,这个值从后台获取
                    fileManagerJson: Fast.api.fixurl("/addons/nkeditor/index/attachment/module/" + Config.modulename),
                    items: [
                        'source'
                    ],
                    afterCreate: function () {
                        var self = this;
                        //Ctrl+回车提交
                        Nkeditor.ctrl(document, 13, function () {
                            self.sync();
                            $(that).closest("form").submit();
                        });
                        Nkeditor.ctrl(self.edit.doc, 13, function () {
                            self.sync();
                            $(that).closest("form").submit();
                        });
                        //粘贴上传
                        $("body", self.edit.doc).bind('paste', function (event) {
                            var image, pasteEvent;
                            pasteEvent = event.originalEvent;
                            if (pasteEvent.clipboardData && pasteEvent.clipboardData.items) {
                                image = getImageFromClipboard(pasteEvent);
                                if (image) {
                                    event.preventDefault();
                                    Upload.api.send(image, function (data) {
                                        self.exec("insertimage", Fast.api.cdnurl(data.url));
                                    });
                                }
                            }
                        });
                        //挺拽上传
                        $("body", self.edit.doc).bind('drop', function (event) {
                            var image, pasteEvent;
                            pasteEvent = event.originalEvent;
                            if (pasteEvent.dataTransfer && pasteEvent.dataTransfer.files) {
                                images = getImageFromDrop(pasteEvent);
                                if (images.length > 0) {
                                    event.preventDefault();
                                    $.each(images, function (i, image) {
                                        Upload.api.send(image, function (data) {
                                            self.exec("insertimage", Fast.api.cdnurl(data.url));
                                        });
                                    });
                                }
                            }
                        });
                    },
                    //FastAdmin自定义处理
                    beforeUpload: function (callback, file) {
                        var file = file ? file : $("input.ke-upload-file", this.form).prop('files')[0];
                        Upload.api.send(file, function (data) {
                            var data = {code: '000', data: {url: Fast.api.cdnurl(data.url)}, title: '', width: '', height: '', border: '', align: ''};
                            callback(data);
                        });

                    },
                    //错误处理 handler
                    errorMsgHandler: function (message, type) {
                        try {
                            console.log(message, type);
                        } catch (Error) {
                            alert(message);
                        }
                    }
                });
            });
            $('.ke-edit-iframe').css('height', '240px');
        },
        edit: function () {
            Controller.api.bindevent();
            $(".editor_nkeditor", $("form[role=form]")).each(function () {
                var that = this;
                Nkeditor.create(that, {
                    width: '100%',
                    height: '50%',
                    filterMode: false,
                    wellFormatMode: false,
                    allowMediaUpload: true, //是否允许媒体上传
                    allowFileManager: true,
                    allowImageUpload: true,
                    wordImageServer: typeof Config.nkeditor != 'undefined' && Config.nkeditor.wordimageserver ? "127.0.0.1:10101" : "", //word图片替换服务器的IP和端口
                    urlType: Config.upload.cdnurl ? 'domain' : '',//给图片加前缀
                    cssPath: Fast.api.cdnurl('/assets/addons/nkeditor/plugins/code/prism.css'),
                    cssData: "body {font-size: 13px}",
                    fillDescAfterUploadImage: false, //是否在上传后继续添加描述信息
                    themeType: typeof Config.nkeditor != 'undefined' ? Config.nkeditor.theme : 'black', //编辑器皮肤,这个值从后台获取
                    fileManagerJson: Fast.api.fixurl("/addons/nkeditor/index/attachment/module/" + Config.modulename),
                    items: [
                        'source'
                    ],
                    afterCreate: function () {
                        var self = this;
                        //Ctrl+回车提交
                        Nkeditor.ctrl(document, 13, function () {
                            self.sync();
                            $(that).closest("form").submit();
                        });
                        Nkeditor.ctrl(self.edit.doc, 13, function () {
                            self.sync();
                            $(that).closest("form").submit();
                        });
                        //粘贴上传
                        $("body", self.edit.doc).bind('paste', function (event) {
                            var image, pasteEvent;
                            pasteEvent = event.originalEvent;
                            if (pasteEvent.clipboardData && pasteEvent.clipboardData.items) {
                                image = getImageFromClipboard(pasteEvent);
                                if (image) {
                                    event.preventDefault();
                                    Upload.api.send(image, function (data) {
                                        self.exec("insertimage", Fast.api.cdnurl(data.url));
                                    });
                                }
                            }
                        });
                        //挺拽上传
                        $("body", self.edit.doc).bind('drop', function (event) {
                            var image, pasteEvent;
                            pasteEvent = event.originalEvent;
                            if (pasteEvent.dataTransfer && pasteEvent.dataTransfer.files) {
                                images = getImageFromDrop(pasteEvent);
                                if (images.length > 0) {
                                    event.preventDefault();
                                    $.each(images, function (i, image) {
                                        Upload.api.send(image, function (data) {
                                            self.exec("insertimage", Fast.api.cdnurl(data.url));
                                        });
                                    });
                                }
                            }
                        });
                    },
                    //FastAdmin自定义处理
                    beforeUpload: function (callback, file) {
                        var file = file ? file : $("input.ke-upload-file", this.form).prop('files')[0];
                        Upload.api.send(file, function (data) {
                            var data = {code: '000', data: {url: Fast.api.cdnurl(data.url)}, title: '', width: '', height: '', border: '', align: ''};
                            callback(data);
                        });

                    },
                    //错误处理 handler
                    errorMsgHandler: function (message, type) {
                        try {
                            console.log(message, type);
                        } catch (Error) {
                            alert(message);
                        }
                    }
                });
            });
            $('.ke-edit-iframe').css('height', '240px');
            $(document).on('click', ".btn-sub", function () {
                var type = $(this).val();
                if(type == 'del'){
                    $("#demand_edit").attr('action','demand/it_web_demand/del');
                }
                if(type == 'edit'){
                    $("#demand_edit").attr('action','demand/it_web_demand/edit');
                }
                if(type == 'pending'){
                    $('#pm_audit_status').val(2);
                    $("#demand_edit").attr('action','demand/it_web_demand/edit');
                }
                if(type == 'sub'){
                    $('#pm_audit_status').val(3);
                    $("#demand_edit").attr('action','demand/it_web_demand/edit');
                }
                $("#demand_edit").submit();
            });

        },
        edit1: function () {
            Controller.api.bindevent();

            $(document).on('click', "#add_entry_user", function () {
                var user_id = $('#Copy_to_user_id').val();
                if(isNaN(parseInt(user_id))){
                    layer.alert('无效的选择');
                    return false;
                }
                var user_name = $("#Copy_to_user_id option:selected").text();
                var status = 0;
                $("#user_list .user_list input").each(function(){
                    var sel_userid=$(this).val();
                    if(user_id == sel_userid){
                        status = 1;
                    }
                });
                if(status == 1){
                    layer.alert('重复的抄送人');
                    return false;
                }
                var add_str = '<div class="user_list" id="userid_'+user_id+'"><span>'+user_name+'</span><a href="javascript:;" onclick="del_Entry_user('+user_id+')"> Ｘ </a><input type="hidden" name="row[copy_to_user_id][]" value="'+user_id+'"></div>'
                $('#user_list').append(add_str);
            });
        },
        distribution: function () {
            Controller.api.bindevent();

            $(document).on('change', ".web_group_status", function () {
                var status_val = $(this).val();
                if(status_val){
                    if(status_val == 2){
                        $('.web_html').css('display','none');
                    }
                    if(status_val == 1){
                        $('.web_html').css('display','block');
                    }
                }
            });
            $(document).on('change', ".php_group_status", function () {
                var status_val = $(this).val();
                if(status_val){
                    if(status_val == 2){
                        $('.php_html').css('display','none');
                    }
                    if(status_val == 1){
                        $('.php_html').css('display','block');
                    }
                }
            });
            $(document).on('change', ".app_group_status", function () {
                var status_val = $(this).val();
                if(status_val){
                    if(status_val == 2){
                        $('.app_html').css('display','none');
                    }
                    if(status_val == 1){
                        $('.app_html').css('display','block');
                    }
                }
            });

            $(document).on('click', ".btn-sub", function () {
                var type = $(this).val();
                $('#input_'+type).val('2');
                $("#form_"+type).submit();
            });
        },
        distribution1: function () {
            Controller.api.bindevent();

            $(function(){
                var status = $('#status').val();
                if(status == 3){
                    var web_designer_group = $('#Web_designer_group').val();
                    if(web_designer_group == 1){
                        $('.Web_designer_class').show();
                    }

                    var phper_group = $('#phper_group').val();
                    if(phper_group == 1){
                        $('.phper_class').show();
                    }

                    var app_group = $('#app_group').val();
                    if(app_group == 1){
                        $('.app_class').show();
                    }
                }
            });

            /*前端分配 start*/
            $(document).on('click', "#add_web_designer_user", function () {
                var user_id = $('#web_designer_user').val();
                if(isNaN(parseInt(user_id))){
                    layer.alert('无效的选择');
                    return false;
                }
                var user_name = $("#web_designer_user option:selected").text();

                var status = 0;
                $("#web_designer_user_list .user_list input").each(function(){
                    var sel_userid=$(this).val();
                    if(user_id == sel_userid){
                        status = 1;
                    }
                });
                if(status == 1){
                    layer.alert('重复的责任人');
                    return false;
                }
                var add_str = '<div class="user_list" id="userid_'+user_id+'"><span>'+user_name+'</span><a href="javascript:;" onclick="del_Entry_user('+user_id+')"> Ｘ </a><input type="hidden" name="row[web_designer_user_id][]" value="'+user_id+'"></div>'
                $('#web_designer_user_list').append(add_str);
            });
            /*前端分配 end*/

            /*后端分配 start*/
            $(document).on('click', "#add_phper_user", function () {
                var user_id = $('#phper_user').val();
                if(isNaN(parseInt(user_id))){
                    layer.alert('无效的选择');
                    return false;
                }
                var user_name = $("#phper_user option:selected").text();

                var status = 0;
                $("#phper_user_list .user_list input").each(function(){
                    var sel_userid=$(this).val();
                    if(user_id == sel_userid){
                        status = 1;
                    }
                });
                if(status == 1){
                    layer.alert('重复的责任人');
                    return false;
                }
                var add_str = '<div class="user_list" id="userid_'+user_id+'"><span>'+user_name+'</span><a href="javascript:;" onclick="del_Entry_user('+user_id+')"> Ｘ </a><input type="hidden" name="row[phper_user_id][]" value="'+user_id+'"></div>'
                $('#phper_user_list').append(add_str);
            });
            /*后端分配 end*/

            /*app分配 start*/
            $(document).on('click', "#add_app_group", function () {
                var user_id = $('#app_user').val();
                if(isNaN(parseInt(user_id))){
                    layer.alert('无效的选择');
                    return false;
                }
                var user_name = $("#app_user option:selected").text();

                var status = 0;
                $("#app_user_list .user_list input").each(function(){
                    var sel_userid=$(this).val();
                    if(user_id == sel_userid){
                        status = 1;
                    }
                });
                if(status == 1){
                    layer.alert('重复的责任人');
                    return false;
                }
                var add_str = '<div class="user_list" id="userid_'+user_id+'"><span>'+user_name+'</span><a href="javascript:;" onclick="del_Entry_user('+user_id+')"> Ｘ </a><input type="hidden" name="row[app_user_id][]" value="'+user_id+'"></div>'
                $('#app_user_list').append(add_str);
            });
            /*app分配 end*/
        },
        test_handle: function () {
            Controller.api.bindevent();

            $(document).on('click', ".btn-sub", function () {
                var type = $(this).val();
                if(type == 'tongguo_yes'){
                    $('#tongguo_status').val('1');
                    $("#tongguo_form").submit();
                }
                if(type == 'tongguo_no'){
                    $('#tongguo_status').val('2');
                    $("#tongguo_form").submit();
                }
            });
        },
        test_distribution: function () {
            Controller.api.bindevent();

            $(function(){
                var status = $('#status').val();
                if(status == 1){
                    var test_group = $('#test_group').val();
                    if(test_group == 1){
                        $('.test_class').show();
                    }
                }
            });

            /*测试确认、分配 start*/
            $(document).on('click', "#add_test_user", function () {
                var user_id = $('#test_user').val();
                if(isNaN(parseInt(user_id))){
                    layer.alert('无效的选择');
                    return false;
                }
                var user_name = $("#test_user option:selected").text();

                var status = 0;
                $("#test_user_list .user_list input").each(function(){
                    var sel_userid=$(this).val();
                    if(user_id == sel_userid){
                        status = 1;
                    }
                });
                if(status == 1){
                    layer.alert('重复的责任人');
                    return false;
                }
                var add_str = '<div class="user_list" id="userid_'+user_id+'"><span>'+user_name+'</span><a href="javascript:;" onclick="del_Entry_user('+user_id+')"> Ｘ </a><input type="hidden" name="row[test_user_id][]" value="'+user_id+'"></div>'
                $('#test_user_list').append(add_str);
            });
            /*测试确认、分配 end*/
        },
        detail: function () {
            Controller.api.bindevent();


            $(document).on('change', ".check_value,#web_designer_user_id,#phper_user_id,#app_user_id,#test_user_id", function () {
                var layer_index = layer.load(2, {
                    shade: [0.2,'#000']
                });

                var id = $('#demand_id').val();

                var is_small_probability = 0;
                if($('#is_small_probability').is(":checked")){
                    is_small_probability = 1;
                }
                var is_low_level_error = 0;
                if($('#is_low_level_error').is(":checked")){
                    is_low_level_error = 1;
                }
                var is_difficult = 0;
                if($('#is_difficult').is(":checked")){
                    is_difficult = 1;
                }

                var web_designer_user_id = $('#web_designer_user_id').val();
                var phper_user_id = $('#phper_user_id').val();
                var app_user_id = $('#app_user_id').val();
                var test_user_id = $('#test_user_id').val();

                $.ajax({
                    type: "POST",
                    url: "demand/it_web_demand/detail",
                    dataType: "json",
                    cache: false,
                    async: false,
                    data: {
                        id: id,
                        is_small_probability: is_small_probability,
                        is_low_level_error: is_low_level_error,
                        is_difficult: is_difficult,
                        web_designer_user_id: web_designer_user_id,
                        phper_user_id: phper_user_id,
                        app_user_id: app_user_id,
                        test_user_id: test_user_id
                    },
                    success: function (json) {
                        Toastr.success(json.msg);
                        layer.close(layer_index);
                        parent.$('#table').bootstrapTable('refresh');
                    }
                });
            });

            $(document).on('click', ".sub_review", function () {
                var layer_index = layer.load(2, {
                    shade: [0.2,'#000']
                });

                var form_id = $(this).attr('data');
                var content = $('#c_'+form_id).val();
                var id = $('#demand_id').val();
                if(form_id == 'test_review'){
                    var type = 1;
                }else{
                    var type = 2;
                }

                $.ajax({
                    type: "POST",
                    url: "demand/it_web_demand/demand_review",
                    dataType: "json",
                    cache: false,
                    async: false,
                    data: {
                        pid: id,
                        type: type,
                        content: content,
                    },
                    success: function (json) {
                        if(json.data){
                            var str = '<li class="item"><p><span class="name">'+json.data.group_name+'</span><span class="time">'+json.data.create_time+'</span></p><p class="text-content">'+json.data.content+'</p></li>'
                            $('#'+form_id).append(str);
                            $('#c_'+form_id).val('');
                        }


                        Toastr.success(json.msg);
                        layer.close(layer_index);
                    }
                });
            });



        },
        group_finish: function () {
            Controller.api.bindevent();
        },
        detail_log: function () {
            Controller.api.bindevent();
        },
        test_finish_opt: function () {
            Controller.api.bindevent();
        },
        test_record_bug: function () {
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
                //点击评审，弹出窗口
                ge_pm_status: function (value, row, index) {
                    if(row.pm_audit_status == 1){
                        return '<div><span class="check_pm_status status1_color">待审</span></div>';
                    }
                    if(row.pm_audit_status == 2){
                        return '<div><span class="check_pm_status status2_color">Pending</span></div>';
                    }
                    if(row.pm_audit_status == 3){
                        return '<div><span class="check_pm_status status3_color">通过</span></div>';
                    }

                },
                //开发进度点击弹窗
                get_develop_status: function (value, row, index) {
                    if(row.status >= 2){
                        if(row.develop_finish_status == 1){
                            return '<div><span class="check_develop_status status1_color">未响应</span></div>';
                        }else if (row.develop_finish_status == 2){
                            return '<div><span class="check_develop_status status1_color">开发中</span></div>';
                        }else{
                            if(row.status == 4){
                                return '<div><span class="check_develop_status status3_color">开发完成</span></div>';
                            }else{
                                return '<div><span class="check_develop_status status4_color">开发完成</span></div>';
                            }

                        }
                    }else{
                        return '-';
                    }
                },
                //测试进度点击弹窗
                get_test_status: function (value, row, index) {
                    if(row.status >= 2){
                        if(row.test_status == 1){
                            return '<div><span class="check_test_status status1_color">未确认</span></div>';
                        }else if (row.test_status == 2){
                            if(row.test_group == 1){
                                return '<div><span class="check_test_status status3_color">待测试</span></div>';
                            }else{
                                return '<div><span class="check_test_status status3_color">无需测试</span></div>';
                            }

                        }else if (row.test_status == 3){
                            return '<div><span class="check_test_status status1_color">待通过</span></div>';
                        }else if (row.test_status == 4){
                            return '<div><span class="check_test_status status1_color">待上线</span></div>';
                        }else if (row.test_status == 5){
                            return '<div><span class="check_test_status status3_color">已上线</span></div>';
                        }
                    }else{
                        return '-';
                    }
                },
                //完成确认
                get_user_confirm: function (value, row, index) {
                    if(row.test_status == 5){
                        //状态=5才可以点击通过
                        if(row.demand_pm_status && row.entry_user_id == Config.admin_id){
                            //当前登录人有产品确认权限，并且当前登录人就是录入人，则一个按钮
                            if(row.entry_user_confirm == 1 && row.pm_confirm == 1){
                                return '已确认';
                            }else{
                                return '<div><span class="check_user_confirm status1_color">确认</span></div>';
                            }
                        }else{
                            //如果是产品
                            if(row.demand_pm_status){
                                if(row.pm_confirm == 1){
                                    //产品已经确认
                                    if(row.entry_user_confirm == 1){
                                        return '已确认';
                                    }else{
                                        return '产品已确认';
                                    }
                                }else{
                                    return '<div><span class="check_user_confirm status1_color">确认</span></div>';
                                }
                            }
                            //如果是提出人
                            if(row.entry_user_id == Config.admin_id){
                                if(row.entry_user_confirm == 1){
                                    //提出人已经确认
                                    if(row.pm_confirm == 1){
                                        return '已确认';
                                    }else{
                                        return '提出人已确认';
                                    }
                                }else{
                                    return '<div><span class="check_user_confirm status1_color">确认</span></div>';
                                }
                            }
                            //如果是其他人
                            if(row.entry_user_confirm == 1 && row.pm_confirm == 1){
                                return '已确认';
                            }else{
                                if(row.entry_user_confirm == 1){
                                    return '提出人已确认';
                                }
                                if(row.pm_confirm == 1){
                                    return '产品已确认';
                                }
                                return '未确认';
                            }
                        }
                    }else{
                        return '-'
                    }
                },

                //详情记录点击查看
                get_detail: function (value, row, index) {
                    return '<div><span class="check_detail">查看</span></div>';
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

            },
            events: {//绑定事件的方法
                //点击标题，弹出窗口
                gettitle: {
                    //格式为：方法名+空格+DOM元素
                    'click .btn-gettitle': function (e, value, row, index) {
                        Backend.api.open('demand/it_web_demand/edit/type/view/ids/' +row.id, __('任务查看'), { area: ['70%', '70%'] });
                    }
                },
                //点击评审，弹出窗口
                ge_pm_status: {
                    'click .check_pm_status': function (e, value, row, index) {
                        Backend.api.open('demand/it_web_demand/edit/type/pm_audit/ids/' +row.id, __('任务评审'), { area: ['70%', '70%'] });
                    }
                },
                //开发进度，弹出窗口
                get_develop_status:{
                    'click .check_develop_status': function (e, value, row, index) {
                        Backend.api.open('demand/it_web_demand/distribution/ids/' +row.id, __('开发进度'), { area: ['80%', '55%'] });
                    }
                },
                //测试进度
                get_test_status: {
                    'click .check_test_status': function (e, value, row, index) {
                        Backend.api.open('demand/it_web_demand/test_handle/ids/' +row.id, __('测试进度'), { area: ['40%', '50%'] });
                    }
                },
                //完成确认
                get_user_confirm: {
                    'click .check_user_confirm': function (e, value, row, index) {

                        layer.confirm('确认本需求？', {
                            btn: ['确认','取消'] //按钮
                        }, function(){

                            Backend.api.ajax({
                                url:'demand/it_web_demand/add/is_user_confirm/1/ids/' +row.id,
                            }, function(data, ret){
                                $("#table").bootstrapTable('refresh');
                                Layer.closeAll();

                            }, function(data, ret){
                                //失败的回调
                                Layer.closeAll();
                                return false;
                            });

                        }, function(){
                            Layer.closeAll();
                        });



                        //Backend.api.open('demand/it_web_demand/test_handle/ids/' +row.id, __('测试进度'), { area: ['40%', '50%'] });
                    }
                },

                //详情记录点击查看
                get_detail: {
                    'click .check_detail': function (e, value, row, index) {
                        Backend.api.open('demand/it_web_demand/detail/ids/' +row.id, __('详情记录'), { area: ['70%', '55%'] });
                    }
                },


            }            
        }
    };
    return Controller;
});

function del_Entry_user(user_id){
    $("#userid_"+user_id).remove();
}

function update_responsibility_detail(val,classstr){
    var is_val = $(val).val();
    if(is_val == 1){
        $('.'+classstr).show();
    }else{
        $('.'+classstr).hide();
    }
}

function update_responsibility_user(val){
    var is_val = $(val).val();
    $('.responsibility_user_id').attr('name','');
    $('#responsibility_user_id_'+is_val).attr('name','row[responsibility_user_id]');
}


function onchangeSelect(num) {
    $("input[name='row[is_small_probability]']").val(num.value);
}


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
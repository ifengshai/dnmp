define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'demand/it_web_demand/index' + location.search,
                    add_url: 'demand/it_web_demand/add',
                    edit_url: 'demand/it_web_demand/edit',
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
                            searchList: { 1: 'Zeelool', 2: 'Voogueme', 3: 'Nihao' , 4: 'Wesee', 5: '魔晶', 6: 'Orther'},
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
                        {field: 'testgroup', title: __('Test_group'),operate:false},
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
                                    name: 'del',
                                    text: __('删除'),
                                    title: __('删除'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    url: 'demand/it_web_demand/del',
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
                                        }else{
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'edit',
                                    text: __('编辑'),
                                    title: __('编辑'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'demand/it_web_demand/edit',
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
                                        }else{
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'test_distribution',
                                    text: __('测试确认'),
                                    title: __('测试确认'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'demand/it_web_demand/test_distribution',
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
                                    url: 'demand/it_web_demand/group_finish',
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
        edit: function () {
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
        group_finish: function () {
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
                getcontent: function (value) {
                    if (value == null || value == undefined) {
                        value = '';
                    }
                    return '<span class="btn-getcontent check_demand_content" data = "' + value + '" style="">查 看</span>';
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
                getcontent: {
                    //格式为：方法名+空格+DOM元素
                    'click .btn-getcontent': function (e, value, row, index) {
                        var str = '标题：'+row.title+'<br><hr>内容：'+value;
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

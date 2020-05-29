define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'demand/testmanage/it_test_optimize/index' + location.search,
                    add_url: 'demand/testmanage/it_test_optimize/add',
                    edit_url: 'demand/testmanage/it_test_optimize/edit',
                    //del_url: 'demand/testmanage/it_test_optimize/del',
                    multi_url: 'demand/testmanage/it_test_optimize/multi',
                    table: 'it_test_optimize',
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
                        // {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'optimize_type', 
                         title: __('Optimize_type'),
                         searchList :{0:'未操作',1:'bug',2:'需求'},
                         custom:{ 1: 'blue', 2: 'red', 3: 'yellow'},
                         formatter: Table.api.formatter.status
                        },
                        {field: 'optimize_site_type', title: __('Optimize_site_type')},
                        {field: 'optimize_title', title: __('Optimize_title')},
                        {field: 'optimize_description', title: __('描述'),
                        events: Controller.api.events.getcontent,
                        formatter: Controller.api.formatter.getcontent,     
                        },
                        {field: 'optimize_status', title: __('Optimize_status'),
                        searchList: { 1 :'待处理', 2 :'已处理', 3 :'不处理'},
                        custom: { 1: 'yellow', 2: 'blue', 3: 'red'},
                        formatter: Table.api.formatter.status
                        },
                        {field: 'operate_status', 
                         title: __('Operate_status'),
                         searchList: { 0 :'未安排', 1 :'已安排'},
                         custom: { 1: 'yellow', 2: 'blue'},
                         formatter: Table.api.formatter.status                        
                        },
                        {field: 'create_person', title: __('Create_person')},
                        {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                         buttons:[
                            // {
                            //     name: 'detail',
                            //     text: '详情',
                            //     title: __('查看详情'),
                            //     extend: 'data-area = \'["100%","100%"]\'',
                            //     classname: 'btn btn-xs btn-primary btn-dialog',
                            //     icon: 'fa fa-list',
                            //     url: Config.moduleurl + '/itemmanage/item/detail',
                            //     callback: function (data) {
                            //         Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                            //     },
                            //     visible: function (row) {
                            //         //返回true时按钮显示,返回false隐藏
                            //         return true;
                            //     }
                            // },


                            {
                                name: 'plan',
                                text: '安排',
                                title: __('安排'),
                                classname: 'btn btn-xs btn-success btn-dialog',
                                icon: 'fa fa-pencil',
                                url: 'demand/testmanage/it_test_optimize/plan',
                                extend: 'data-area = \'["60%","60%"]\'',
                                callback: function (data) {
                                    Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                },
                                visible: function (row) {
                                    if(1 == row.optimize_status){
                                        return true;
                                    }
                                        return false;
                                }
                            },
                            {
                                name: 'no_processing',
                                text: '暂不处理',
                                title: __('暂不处理'),
                                classname: 'btn btn-xs btn-success btn-ajax',
                                icon: 'fa fa-pencil',
                                url: 'demand/testmanage/it_test_optimize/not_handle',
                                confirm: '确认暂不处理吗',
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
                                    if(1 == row.optimize_status){
                                        return true;
                                    }
                                        return false;
                                },
                            },
                             {
                                 name: 'delete',
                                 text: __(''),
                                 title: __('删除'),
                                 icon: 'fa fa-trash',
                                 classname: 'btn btn-xs btn-danger btn-magic btn-ajax',
                                 url: 'demand/testmanage/it_test_optimize/del',
                                 confirm: '确认删除？',
                                 success: function (data, ret) {
                                     table.bootstrapTable('refresh');
                                 },
                                 callback: function (data) {
                                 },
                                 visible: function(row){
                                     if(Config.is_test_opt_del == 1){
                                         return true;
                                     }
                                     return true;
                                 }
                             },
                             {
                                 name: 'edit',
                                 text: __(''),
                                 title: __('编辑'),
                                 icon: 'fa fa-pencil',
                                 classname: 'btn btn-xs btn-success btn-editone',
                                 url: 'demand/testmanage/it_test_optimize/edit',
                                 success: function (data, ret) {
                                     table.bootstrapTable('refresh');
                                 },
                                 callback: function (data) {
                                 },
                                 visible: function(row){
                                     if(Config.is_test_opt_edit == 1){
                                         return true;
                                     }
                                     return true;
                                 }
                             }
                         ]   
                        }
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
        plan:function(){
            Controller.api.bindevent();
        },
        api: {
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
                        var str = '标题：'+row.optimize_title+'<br><hr>内容：'+value;
                        Layer.open({
                            closeBtn: 1,
                            title: "详情",
                            content: str,
                            area:['80%','80%'],
                            anim: 0
                        });
                    }
                }
            },            
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
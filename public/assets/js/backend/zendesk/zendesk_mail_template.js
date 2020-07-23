define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'zendesk/zendesk_mail_template/index' + location.search,
                    add_url: 'zendesk/zendesk_mail_template/add',
                    edit_url: 'zendesk/zendesk_mail_template/edit',
                    //del_url: 'zendesk/zendesk_mail_template/del',
                    multi_url: 'zendesk/zendesk_mail_template/multi',
                    table: 'zendesk_mail_template',
                }
            });

            var table = $("#table");
            $(document).on('click',".problem_desc_info",function(){
                var problem_desc = $(this).attr('name');
                //Layer.alert(problem_desc);
                Layer.open({
                    closeBtn: 1,
                    title: '内容',
                    area: ['900px', '500px'],
                    content:problem_desc
                });
                return false;
            });

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {
                            field: 'template_platform', 
                            title: __('Template_platform'),
                            searchList:$.getJSON('saleaftermanage/sale_after_task/getAjaxOrderPlatformList'),
                            custom:{1:'blue',2:'yellow',3:'green',4:'red'},
                            formatter:Table.api.formatter.status,
                        },
                        {field: 'template_name', title: __('Template_name'),operate:'LIKE %...%'},
                        {field: 'template_description', title: __('Template_description')},
                        {
                            field: 'template_permission', 
                            title: __('Template_permission'),
                            searchList: { 1: '公共', 2: '私有'},
                            custom: { 1: 'yellow', 2: 'blue'},
                            formatter: Table.api.formatter.status
                        },
                        {field: 'template_content', title: __('Template_content'),formatter:Controller.api.formatter.getClear,operate:false},
                        {field: 'template_category', title: __('Template_category')},
                        {
                            field: 'is_active', 
                            title: __('Is_active'),
                            searchList: { 1: '启用', 2: '禁用'},
                            custom: { 1: 'blue', 2: 'yellow'},
                            formatter: Table.api.formatter.status                            
                        },
                        {field: 'used_time', title: __('Used_time')},
                        {field: 'create_person', title: __('Create_person')},
                        {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                        buttons: [
                            {
                                name: 'detail',
                                text: '详情',
                                title: __('查看详情'),
                                extend: 'data-area = \'["100%","100%"]\'',
                                classname: 'btn btn-xs btn-primary btn-dialog',
                                icon: 'fa fa-list',
                                url: 'zendesk/zendesk_mail_template/detail',
                                callback: function (data) {
                                    Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                },
                                visible: function (row) {
                                    //返回true时按钮显示,返回false隐藏
                                    return true;
                                }
                            },
                            {
                                name: 'edit',
                                text: '编辑',
                                title: __('编辑'),
                                classname: 'btn btn-xs btn-success btn-dialog',
                                icon: 'fa fa-pencil',
                                url:  'zendesk/zendesk_mail_template/edit',
                                extend: 'data-area = \'["100%","100%"]\'',
                                callback: function (data) {
                                    Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                },
                                visible: function (row) {
                                        return true;
                                }
                            },
                            {
                                name: 'start',
                                text: '启用',
                                title: __('start'),
                                classname: 'btn btn-xs btn-danger btn-ajax',
                                icon: 'fa fa-remove',
                                confirm: '确定要启用吗',
                                url: 'zendesk/zendesk_mail_template/start',
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
                                    if(row.is_active == 2){
                                        return true;
                                    }
                                        return false;
                                        
                                }

                            },
                            {
                                name: 'forbidden',
                                text: '禁用',
                                title: __('forbidden'),
                                classname: 'btn btn-xs btn-danger btn-ajax',
                                icon: 'fa fa-remove',
                                confirm: '确定要禁用吗',
                                url: 'zendesk/zendesk_mail_template/forbidden',
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
                                    if(row.is_active == 1){
                                        return true;
                                    }
                                        return false;
                                        
                                }

                            }                            
                        ]
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
            //商品启用
            $(document).on('click', '.btn-start', function () {
                var ids = Table.api.selectedids(table);
                Layer.confirm(
                    __('确定要启用模板吗'),
                    function (index) {
                        Backend.api.ajax({
                            url: "zendesk/zendesk_mail_template/start",
                            data: { ids: ids }
                        }, function (data, ret) {
                            table.bootstrapTable('refresh');
                            Layer.close(index);
                        });
                    }
                );
            });
            //刷新邮件模板
            $(document).on('click', '.btn-refresh-template', function () {
                Layer.confirm(
                    __('确定要刷新邮件模板吗'),
                    function (index) {
                        Backend.api.ajax({
                            url: "zendesk/zendesk_mail_template/refreshTemplate",
                        }, function (data, ret) {
                            table.bootstrapTable('refresh');
                            Layer.close(index);
                        },function(data,ret){
                            Layer.alert(ret.msg);
                            return false;
                        });
                    }
                );
            });
            //商品禁用
            $(document).on('click', '.btn-forbidden', function () {
                var ids = Table.api.selectedids(table);
                Layer.confirm(
                    __('确定要禁用模板吗'),
                    function (index) {
                        Backend.api.ajax(
                            {
                            url: "zendesk/zendesk_mail_template/forbidden",
                            data: { ids: ids }
                        }, function (data, ret) {
                            table.bootstrapTable('refresh');
                            Layer.close(index);
                        });
                    }
                );
            });            
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        detail:function(){
            Controller.api.bindevent();
            Form.api.bindevent($("form[role=form]"));
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            formatter:{
                getClear:function(value){
                    if (value == null || value == undefined) {
                        return '';
                    } else {
                         var tem = value;
                            // .replace(/&lt;/g, "<")
                            // .replace(/&gt;/g, ">")
                            // .replace(/&quot;/g, "\"")
                            // .replace(/&apos;/g, "'")
                            // .replace(/&amp;/g, "&")
                            // .replace(/&nbsp;/g, '').replace(/<\/?.+?\/?>/g, '').replace(/<[^>]+>/g, "")
                           //.replace(/<\/?.+?\/?>/g, '').replace(/<[^>]+>/g, "")
                        if(tem.length<=10){
                            //console.log(row.id);
                            return tem;
                        }else{
                            return tem.substr(0, 10)+'<span class="problem_desc_info" name = "'+tem+'" style="color:red;">...</span>';

                        }
                    }
                }                
            }

        }
    };
    return Controller;
});
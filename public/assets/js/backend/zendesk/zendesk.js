define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'jq-tags', 'jqui','template'], function ($, undefined, Backend, Table, Form , JqTags , Jqui , Template) {

    
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'zendesk/zendesk/index' + location.search,
                    edit_url: 'zendesk/zendesk/edit',
                    table: 'zendesk',
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
                        {field: 'id', title: __('Id')},
                        {field: 'ticket_id', title: __('Ticket_id')},
                        {field: 'subject', title: __('Subject'),operate:false},
                        {field: 'email', title: __('Email')},
                        //{field: 'assign_id', title: __('Assgin_id'),operate: false,visible:false},
                        {field: 'admin.nickname', title: __('Assign_id')},
                        {field: 'status', title: __('Status'), custom: { 1: 'danger', 2: 'success', 3: 'blue', 4: 'orange', 5: 'gray' }, searchList: { 1: 'New', 2: 'Open', 3: 'Pending', 4: 'Solved', 5: 'Close' }, formatter: Table.api.formatter.status },
                        {
                            field: 'tags', title: __('Tags'), searchList: function (column) {
                                return Template('tagstpl', {});
                            },visible: false
                        },
                        {field: 'tag_format', title: __('Tags'),operate:false},
                        {field: 'priority', title: __('priority'), custom: { 0: 'success', 1: 'gray', 2: 'yellow', 3: 'blue', 4: 'danger' }, searchList: { 0: '无', 1: 'Low', 2: 'Normal', 3: 'High', 4: 'Urgent' }, formatter: Table.api.formatter.status },
                        {field: 'channel', title: __('Channel')},
                        {field: 'type', title: __('type'), custom: { 1: 'yellow', 2: 'blue' }, searchList: { 1: 'Zeelool', 2: 'Voogueme' }, formatter: Table.api.formatter.status },
                        {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange'},
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, buttons: [
                                {
                                    name: 'edit',
                                    text: function(row){
                                        if(row.status == 5){
                                            return '查看';
                                        }
                                        return __('Answer');
                                    },
                                    title: function (row) {
                                        return __('Answer') + '【' + row.ticket_id + '】' + row.subject;
                                    },
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: '',
                                    url: 'zendesk/zendesk/edit',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function(row){
                                        //console.log(row.assign_id)
                                        if(row.assign_id != Config.admin_id){
                                            return false;
                                        }
                                        return true;
                                    }
                                }

                            ], formatter: Table.api.formatter.operate
                        }]
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
                    }else{
                        delete filter.me_task;
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
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
            //删除商品数据
            $(document).on('click', '.merge', function () {
                var nid = $(this).data('nid');
                var pid = $(this).data('pid');
                var subject = $(this).data('subject');
                var ticket_id = $('.merge-input').val();
                if (ticket_id) {
                    nid = ticket_id;
                }
                if (nid) {
                    $.ajax({
                        type: "POST",
                        url: "zendesk/zendesk/getTicket",
                        dataType: "json",
                        cache: false,
                        async: false,
                        data: {
                            nid: nid,
                            pid: pid
                        },
                        success: function (json) {
                            var data = json.data;
                            if(json.code == 0){
                                Layer.msg(json.msg);
                                //$('#modal-default2').modal('hide');
                            }else{
                                $('#modal-default').modal('hide')
                                $('#modal-default2').modal('show');
                                $('.nid').val(data.ticket_id);
                                $('.merge-ticket-id').html(data.ticket_id);
                                $('.merge-subject').html(data.subject);
                                $('.merge-content-in').html('Request #'+ pid +' "'+ subject +'" was closed and merged into this request. Last comment in request #'+pid+'.' +
                                    ''+data.lastComment);
                                $('.merge-content-to').html('This request was closed and merged into request #'+data.ticket_id+' "'+data.subject+'".');
                            }

                        }
                    });
                }
                return false;
            });
            $(document).on('click', '.btn-merge-submit', function () {
                var data = $(this).parents('form').serialize();
                $.ajax({
                    type: "POST",
                    url: "zendesk/zendesk/setMerge",
                    dataType: "json",
                    cache: false,
                    async: false,
                    data: data,
                    success: function (json) {
                        if(json.code == 1) {
                            window.location.reload();
                        }else if(json.code == 0) {
                            Layer.msg(json.msg);
                            return false;
                        }
                    },
                    error: function (json) {
                        return false;

                    }
                })
                return false;
            });

            //抄送人标签输入
            $('#ccs').tagsInput({
                width: 'auto',
                defaultText: '输入后回车确认',
                minInputWidth: 110,
                height: 'auto',
                placeholderColor: '#999',
                autocomplete_url:'zendesk/zendesk/getEmail',
                onChange:function(input,mail){
                    var strRegex = /^(\w-*\.*)+@(\w-?)+(\.\w{2,})+$/;
                    if(mail != undefined){
                        if(!strRegex.test(mail)){
                            Layer.msg('请输入正确的邮箱地址');
                            input.removeTag(mail);
                        }
                    }
                }
            });

            $(document).on('change','.macro-apply',function(){
                var id = $(this).val();
                var ticket_id = $('.ticket_id').val();
                if(id){
                    $.ajax({
                        type: "POST",
                        url: "zendesk/zendesk_mail_template/getTemplate",
                        dataType: "json",
                        cache: false,
                        async: false,
                        data: {
                            id:id,
                            ticket_id: ticket_id
                        },
                        success: function (json) {
                            //修改回复内容，状态，priority，tags
                            if(json.template_content){
                                $('.ticket-content').summernote("code",json.template_content);
                            }
                            if(json.mail_status) {
                                $('.ticket-status').val(json.mail_status);
                            }
                            if(json.mail_level) {
                                $('.ticket-priority').val(json.mail_level);
                            }
                            if(json.mail_tag) {
                                $('.ticket-tags').val(json.mail_tag);
                            }
                            if(json.mail_subject) {
                                $('.ticket-subject').val(json.mail_subject);

                            }
                            $('.selectpicker ').selectpicker('refresh');
                            Layer.msg('应用成功');
                            return false;
                        },
                        error: function(json){
                            return false;

                        }
                    })
                }
            });
            $(document).on('click','.post-search',function(){
               var text = $('.post-search-input').val();
               var type = $('.search-post-type').val();
                $.ajax({
                    type: "POST",
                    url: "zendesk/zendesk/searchPosts",
                    dataType: "json",
                    cache: false,
                    async: false,
                    data: {
                        text: text,
                        type: type
                    },
                    success: function (json) {
                        $('.search-posts').html(json.html);
                        $('.show-posts').html(json.post_html);
                        return false;
                    },
                    error: function(json){
                        return false;

                    }
                });
            });
            $(document).on('click','.card-link',function(){
               var link = $(this).data('link');
               var title = $(this).data('title');
                $('.ticket-content').summernote("createLink",{
                    text: title,
                    url: link,
                    isNowWindow: true
                });
                $(this).next('button').show();
            });
            $(document).on('mouseenter','.card',function(){
                var num = $(this).data('num');
               // console.log(num);
                $('.show-posts').find('.post-row').eq(num).show().siblings().hide();
            })
            $(document).on('click','.change-ticket',function(){
                var title = $(this).data('title');
                var status = $(this).data('status');
                parent.$(".layui-layer-title")[0].innerText= title;
                if(status == 5){
                    parent.$(".layui-layer-footer").hide();
                }else{
                    parent.$(".layui-layer-footer").show();
                }
            });
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
        }
    };
    return Controller;
});
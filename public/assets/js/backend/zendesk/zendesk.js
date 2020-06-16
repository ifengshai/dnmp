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
                search: false,
                showToggle:false,
                cardView: false,
                searchFormVisible: true,
                columns: [
                    [
                        {field: 'id', title: __('Id'),sortable: true},
                        {field: 'ticket_id', title: __('Ticket_id'),sortable: true},
                        {field: 'subject', title: __('Subject'),operate:false,formatter: function(value){return value.toString().substr(0, 100)}},
                        {field: 'email', title: __('Email'),operate:'LIKE %...%'},
                        {field: 'content', title: __('关键字'),visible:false},
                        //{field: 'assign_id', title: __('Assgin_id'),operate: false,visible:false},
                        {
                            field: 'admin.nickname',
                            title: __('Assign_id'),
                            align: 'left',
                            searchList: $.getJSON('zendesk/zendesk_agents/getAgentsList')
                        },
                        {field: 'status', title: __('Status'), custom: { 1: 'danger', 2: 'success', 3: 'blue', 4: 'orange', 5: 'gray'}, searchList: { 1: 'New', 2: 'Open', 3: 'Pending', 4: 'Solved', 5: 'Close'}, formatter: Table.api.formatter.status },
                        {
                            field: 'tags', title: __('Tags'), searchList: function (column) {
                                return Template('tagstpl', {});
                            },visible: false
                        },
                        {
                            field: 'status_type', title: __('类型'),custom: { 1: 'danger', 2: 'success', 3: 'blue', 4: 'orange' }, searchList: { 1: '待处理', 2: '新增', 3: '已处理', 4: '待分配' }, formatter: Table.api.formatter.status,visible:false
                        },
                        {field: 'priority', title: __('priority'), custom: { 0: 'success', 1: 'gray', 2: 'yellow', 3: 'blue', 4: 'danger' }, searchList: { 0: '无', 1: 'Low', 2: 'Normal', 3: 'High', 4: 'Urgent' }, formatter: Table.api.formatter.status },
                        {field: 'channel', title: __('Channel')},
                        {field: 'type', title: __('type'), custom: { 1: 'yellow', 2: 'blue' }, searchList: { 1: 'Zeelool', 2: 'Voogueme' }, formatter: Table.api.formatter.status },
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange',sortable: true},
                        {field: 'zendesk_update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange',sortable: true},
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
                                    classname: 'btn btn-xs btn-success',
                                    icon: '',
                                    url: 'zendesk/zendesk/edit',
                                    extend: 'data-area = \'["100%","100%"]\' target=\'_blank\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function(row){
                                        //console.log(row.assign_id)
                                        if( Config.admin_id == 1 || Config.admin_id == 75){
                                            return true;
                                        }
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
            //判断搜索时的条件
            $('.form-commonsearch .btn-success').on('click',function(){
                var status_type = $('.form-commonsearch').find('select[name="status_type"]').val();
                var create_time = $('#zendesk_update_time').val();
                if(status_type == 2 && !create_time){
                    Toastr.error('请选择更新时间');
                    return false;
                }
            })
            // 启动和暂停按钮
            $(document).on("click", ".btn-start", function () {
                var url = $(this).data('url');
                $.post(url,{},function(data){
                    if(data.code == 1) {
                        Layer.msg('申请成功');
                        table.bootstrapTable('refresh', {});
                    }else{
                        Layer.msg(data.msg);
                    }

                },'json')
                return false;
                //在table外不可以使用添加.btn-change的方法
                //只能自己调用Table.api.multi实现
                //如果操作全部则ids可以置为空
                // var ids = Table.api.selectedids(table);
                // Table.api.multi("changestatus", ids.join(","), table, this);
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
            Form.api.bindevent($("form[role=form]"), function (data, ret) {
                top.window.$("ul.nav-addtabs li.active").find(".fa-remove").trigger("click");
                Fast.api.close();
                window.open("about:blank","_self").close();
            }, function (data, ret) {
                Toastr.success("失败");
            });
            $(document).on('change','.macro-apply',function(){
                var id = $(this).val();
                var email = $('.email').val();
                var type = $('.search-post-type').val();
                if(id){
                    $.ajax({
                        type: "POST",
                        url: "zendesk/zendesk_mail_template/getTemplateAdd",
                        dataType: "json",
                        cache: false,
                        async: false,
                        data: {
                            id:id,
                            email: email,
                            type: type
                        },
                        success: function (json) {
                            if(json.code != undefined){
                                Toastr.error(json.msg);
                                return false;
                            }
                            //修改回复内容，状态，priority，tags
                            if(json.template_content){
                                var code = $('.ticket-content').summernote('code');
                                var template_content = json.template_content;
                                if(code != '<p><br></p>'){
                                    template_content = code + template_content;
                                }
                                $('.ticket-content').summernote("code",template_content);
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
        },
        edit: function () {
            Controller.api.bindevent();
            Form.api.bindevent($("form[role=form]"), function (data, ret) {
                top.window.$("ul.nav-addtabs li.active").find(".fa-remove").trigger("click");
                Fast.api.close();
                window.open("about:blank","_self").close();
            }, function (data, ret) {
                Toastr.success("失败");
            });
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
                                var code = $('.ticket-content').summernote('code');
                                var template_content = json.template_content;
                                if(code != '<p><br></p>'){
                                    template_content = code + template_content;
                                }
                                $('.ticket-content').summernote("code",template_content);
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
            $(document).on('click', ".create_ticket", function () {
                var order_number=$(".order_info tr:eq(1) td:eq(0)").find('a').html();
                var options = {
                    shadeClose: false,
                    shade: [0.3, '#393D49'],
                    area: ['100%', '100%'], //弹出层宽高
                    callback: function (value) {

                    }
                };
                Fast.api.open('saleaftermanage/work_order_list/add?order_number=' +order_number, '分配', options);
            });
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
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

            },
        }
    };
    return Controller;
});
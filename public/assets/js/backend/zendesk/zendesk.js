define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'zendesk/zendesk/index' + location.search,
                    add_url: 'zendesk/zendesk/add',
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
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'ticket_id', title: __('Ticket_id')},
                        {field: 'subject', title: __('Subject')},
                        {field: 'email', title: __('Email')},
                        {field: 'admin.nickname', title: __('Assign_id')},
                        {field: 'status', title: __('Status'), custom: { 1: 'danger', 2: 'success', 3: 'blue', 4: 'orange', 5: 'gray' }, searchList: { 1: 'New', 2: 'Open', 3: 'Pending', 4: 'Solved', 5: 'Close' }, formatter: Table.api.formatter.status },
                        {field: 'tag_format', title: __('Tags')},
                        {field: 'priority', title: __('priority'), custom: { 0: 'success', 1: 'gray', 2: 'yellow', 3: 'blue', 4: 'danger' }, searchList: { 0: '无', 1: 'Low', 2: 'Normal', 3: 'High', 4: 'Urgent' }, formatter: Table.api.formatter.status },
                        {field: 'channel', title: __('Channel')},
                        {field: 'type', title: __('type'), custom: { 1: 'yellow', 2: 'blue' }, searchList: { 1: 'Zeelool', 2: 'Voogueme' }, formatter: Table.api.formatter.status },
                        {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange'},
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, buttons: [
                                {
                                    name: 'edit',
                                    text: __('Answer'),
                                    title:function (row) {
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
                                        if(row.status == 4 || row.status == 5){
                                            return false;
                                        }else{
                                            return true;
                                        }

                                    }
                                }

                            ], formatter: Table.api.formatter.operate
                        }                    ]
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
            //删除商品数据
            $(document).on('click', '.merge', function () {
               var nid = $(this).data('nid');
               var pid = $(this).data('pid');
               var subject = $(this).data('subject');
               var ticket_id = $('.merge-input').val();
               if(ticket_id){
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
                            $('.nid').val(json.ticket_id);
                            $('.merge-ticket-id').html(json.ticket_id);
                            $('.merge-subject').html(json.subject);
                            $('.merge-content-in').html('Request #'+ pid +' "'+ subject +'" was closed and merged into this request. Last comment in request #'+pid+'.' +
                                ''+json.lastComment);
                            $('.merge-content-to').html('This request was closed and merged into request #'+json.ticket_id+' "'+json.subject+'".');
                        }
                    });
                }
            });
            $(document).on('click','.btn-merge-submit',function(){
               var data = $(this).parents('form').serialize();
               $.ajax({
                   type: "POST",
                   url: "zendesk/zendesk/setMerge",
                   dataType: "json",
                   cache: false,
                   async: false,
                   data: data,
                   success: function (json) {
                       return false;
                   },
                   error: function(json){
                        return false;

                   }
               })
               return false;
            });
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
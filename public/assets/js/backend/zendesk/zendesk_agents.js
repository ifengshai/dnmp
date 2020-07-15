define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'zendesk/zendesk_agents/index' + location.search,
                    add_url: 'zendesk/zendesk_agents/add',
                    edit_url: 'zendesk/zendesk_agents/edit',
                    del_url: 'zendesk/zendesk_agents/del',
                    table: 'zendesk_agents',
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
                        {field: 'admin.nickname', title: __('admin.nickname')},
                        {field: 'nickname', title: __('nickname')},
                        // {field: 'admin.email', title: __('admin.email')},
                        {field: 'agent.account_user', title: __('Name'),operate: false},
                        {field: 'type', title: __('type'), custom: { 1: 'blue', 2: 'yellow' }, searchList: { 1: 'Zeelool', 2: 'Voogueme' }, formatter: Table.api.formatter.status },
                        {field: 'agent_type', title: __('Agent_type'), custom: { 1: 'success', 2: 'danger' }, searchList: { 1: '邮件组', 2: '电话组' }, formatter: Table.api.formatter.status },
                        {field: 'count', title: __('Count')},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
            $(document).on('change','.site-type',function(){
                var type = $(this).val();
                $.ajax({
                    type: "POST",
                    url: "zendesk/zendesk_agents/getAgents",
                    dataType: "json",
                    cache: false,
                    async: false,
                    data: {
                        type:type
                    },
                    success: function (json) {
                        $('.account-select').html(json);
                    }
                });
            });
        },
        edit: function () {
            Controller.api.bindevent();
            $(document).on('change','.site-type',function(){
                var type = $(this).val();
                $.ajax({
                    type: "POST",
                    url: "zendesk/zendesk_agents/getAgents",
                    dataType: "json",
                    cache: false,
                    async: false,
                    data: {
                        type:type
                    },
                    success: function (json) {
                        $('.account-select').html(json);
                    }
                });
            });
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
                //站点名和账户名二级联动
                // $(document).on('change','#type',function(){
                //     var platform = $(this).val();
                //     if(platform == null){
                //         Layer.alert('请选择站点');
                //         return false;
                //     }
                //     //根据承接部门查找出承接人
                //     Backend.api.ajax({
                //         url:'zendesk/zendesk_agents/get_zendesk_account',
                //         data:{platform:platform}
                //     }, function(data, ret){
                //         var rs = ret.data;
                //         var x;
                //         $("#name").html('');
                //         var str = '';
                //         for( x in rs ){
                //             str +='<option value="'+x+'">' + rs[x]+'</option>';
                //         }
                //         $("#name").append(str);
                //         $("#name").selectpicker('refresh');
                //         return false;
                //     }, function(data, ret){
                //         alert(ret.msg);
                //         console.log(ret);
                //         return false;
                //     });
                //     //console.log($(this).val());
                // });
            }
        }
    };
    return Controller;
});
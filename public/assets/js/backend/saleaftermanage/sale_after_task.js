define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'saleaftermanage/sale_after_task/index' + location.search,
                    add_url: 'saleaftermanage/sale_after_task/add',
                    edit_url: 'saleaftermanage/sale_after_task/edit',
                    del_url: 'saleaftermanage/sale_after_task/del',
                    multi_url: 'saleaftermanage/sale_after_task/multi',
                    table: 'sale_after_task',
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
                        {field: 'task_number', title: __('Task_number')},
                        {field: 'order_platform', title: __('Order_platform')},
                        {field: 'order_number', title: __('Order_number')},
                        {field: 'order_status', title: __('Order_status')},
                        {field: 'dept_id', title: __('Dept_id')},
                        {field: 'rep_id', title: __('Rep_id')},
                        {field: 'prty_id', title: __('Prty_id')},
                        {field: 'problem_id', title: __('Problem_id')},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate},
                        {field:'Item_info',title:__('Item_info')},
                        {field:'Customer_name',title:__('Customer_name')},
                        {field:'Customer_email',title:__('Customer_email')},
                        {field:'Item_name',title:__('Item_name')},
                        {field:'Item_sku',title:__('Item_sku')},
                        {field:'Item_qty_ordered',title:__('Item_qty_ordered')},
                        {field:'Recipe_type',title:__('Recipe_type')},
                        {field:'Lens_type',title:__('Lens_type')},
                        {field:'Coating_film_type',title:__('Coating_film_type')},
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
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
                //查询订单详情并生成任务单号
                $(document).on('blur','#c-order_number',function(){
                    var ordertype = $('#c-order_platform').val();
                    var order_number = $('#c-order_number').val();
                    if(ordertype<=0){
                        alert('请选择正确的平台');
                        return false;
                    }
                    Backend.api.ajax({
                        url:'saleaftermanage/sale_after_task/ajax',
                        data:{ordertype:ordertype,order_number:order_number}
                    }, function(data, ret){
                        //成功的回调
                        //alert(ret);
                        console.log(ret);
                        return false;
                    }, function(data, ret){
                        //失败的回调
                        //alert(ret.msg);
                        console.log(ret);
                        return false;
                    });
                });
                //显示/隐藏三级问题
                $(document).on('click','.issueLevel',function(){
                    var issueId = $(this).attr("id");
                    var node = $('#display-'+issueId);
                    if(node.is(':hidden')){
                        node.show();
                    }else{
                        node.hide();
                    }
                });
            }
        }
    };
    return Controller;
});
define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'infosynergytaskmanage/info_synergy_task/index' + location.search,
                    add_url: 'infosynergytaskmanage/info_synergy_task/add',
                    edit_url: 'infosynergytaskmanage/info_synergy_task/edit',
                    del_url: 'infosynergytaskmanage/info_synergy_task/del',
                    multi_url: 'infosynergytaskmanage/info_synergy_task/multi',
                    table: 'info_synergy_task',
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
                        {field: 'synergy_number', title: __('Synergy_number')},
                        {field: 'synergy_order_id', title: __('Synergy_order_id'),searchList:$.getJSON('infosynergytaskmanage/info_synergy_task/getOrderType'),formatter:Controller.api.formatter.synergyOrderId},
                        // {field: 'synergy_order_id', title: __('哈哈'),searchList:{"1":"haha","2":"呵呵","3":"嘻嘻"},formatter: Table.api.formatter.status},
                        {field: 'synergy_order_number', title: __('Synergy_order_number')},
                        {field: 'order_platform', title: __('Order_platform'),searchList:$.getJSON('saleaftermanage/sale_after_task/getAjaxOrderPlatformList'),formatter: Controller.api.formatter.orderDevice},
                        {field: 'dept', title: __('Dept_id')},
                        {field: 'rep', title: __('Rep_id')},
                        {field: 'prty_id', title: __('Prty_id'),searchList: {0:'未选择',1:'高级',2:'中级',3:'低级'},formatter: Controller.api.formatter.prtyDevice},
                        {field: 'synergy_task_id', title: __('Synergy_task_id'),visible:false},
                        {field: 'info_synergy_task_category.name', title: __('Synergy_task_id'),operate:false},
                        {field: 'create_person', title: __('Create_person')},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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
            },
            formatter:{
                orderDevice:function (value) {
                    var str2 = '';
                    if(value == 1){
                        str2= 'zeelool';
                    }else if(value==2){
                        str2= 'voogueme';
                    }else if(value==3){
                        str2 = 'nihao';
                    }
                    return str2;
                },
                prtyDevice: function (value) {
                    var str = '';
                    if (value == 1) {
                        str = '<span style = "color:red;">高级</span>';
                    } else if (value == 2) {
                        str = '<span style = "color:blue;">中级</span>';
                    } else if(value == 3){
                        str = '低级';
                    }
                    return str;
                },
                synergyOrderId:function(value){
                    var synergyOrderIdStr = '';
                    switch(value) {
                        case 2:
                            synergyOrderIdStr = '订单';
                            break;
                        case 3:
                            synergyOrderIdStr = '采购单';
                            break;
                        case 4:
                            synergyOrderIdStr = '质检单';
                            break;
                        case 5:
                            synergyOrderIdStr = '入库单';
                            break;
                        case 6:
                            synergyOrderIdStr = '出库单';
                            break;
                        case 7:
                            synergyOrderIdStr = '库存盘点单';
                            break;
                        default:
                            synergyOrderIdStr = '无';
                            break;
                    }
                    return synergyOrderIdStr;
                },
            }
        }
    };
    return Controller;
});
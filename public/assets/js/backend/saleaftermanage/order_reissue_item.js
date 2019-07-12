define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'saleaftermanage/order_reissue_item/index' + location.search,
                    add_url: 'saleaftermanage/order_reissue_item/add',
                    edit_url: 'saleaftermanage/order_reissue_item/edit',
                    del_url: 'saleaftermanage/order_reissue_item/del',
                    multi_url: 'saleaftermanage/order_reissue_item/multi',
                    table: 'order_reissue_item',
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
                        {field: 'reissue_order_id', title: __('Reissue_order_id')},
                        {field: 'sku', title: __('Sku')},
                        {field: 'qty_ordered', title: __('Qty_ordered')},
                        {field: 'reissue_type', title: __('Reissue_type')},
                        {field: 'index_type', title: __('Index_type')},
                        {field: 'prescription_type', title: __('Prescription_type')},
                        {field: 'coatiing_name', title: __('Coatiing_name')},
                        {field: 'od_sph', title: __('Od_sph')},
                        {field: 'os_sph', title: __('Os_sph')},
                        {field: 'od_cyl', title: __('Od_cyl')},
                        {field: 'os_cyl', title: __('Os_cyl')},
                        {field: 'od_axis', title: __('Od_axis')},
                        {field: 'os_axis', title: __('Os_axis')},
                        {field: 'pd_l', title: __('Pd_l')},
                        {field: 'pd_r', title: __('Pd_r')},
                        {field: 'pd', title: __('Pd')},
                        {field: 'os_add', title: __('Os_add')},
                        {field: 'od_add', title: __('Od_add')},
                        {field: 'total_add', title: __('Total_add')},
                        {field: 'is_visable', title: __('Is_visable')},
                        {field: 'created_time', title: __('Created_time'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'od_pv', title: __('Od_pv')},
                        {field: 'od_bd', title: __('Od_bd')},
                        {field: 'od_pv_r', title: __('Od_pv_r')},
                        {field: 'od_bd_r', title: __('Od_bd_r')},
                        {field: 'os_pv', title: __('Os_pv')},
                        {field: 'os_bd', title: __('Os_bd')},
                        {field: 'os_pv_r', title: __('Os_pv_r')},
                        {field: 'os_bd_r', title: __('Os_bd_r')},
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
            }
        }
    };
    return Controller;
});
define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'saleaftermanage/workmanage/work_order_change_sku/index' + location.search,
                    add_url: 'saleaftermanage/workmanage/work_order_change_sku/add',
                    edit_url: 'saleaftermanage/workmanage/work_order_change_sku/edit',
                    del_url: 'saleaftermanage/workmanage/work_order_change_sku/del',
                    multi_url: 'saleaftermanage/workmanage/work_order_change_sku/multi',
                    table: 'work_order_change_sku',
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
                        {field: 'work_id', title: __('Work_id')},
                        {field: 'increment_id', title: __('Increment_id')},
                        {field: 'platform_type', title: __('Platform_type')},
                        {field: 'original_name', title: __('Original_name')},
                        {field: 'original_sku', title: __('Original_sku')},
                        {field: 'original_number', title: __('Original_number')},
                        {field: 'change_type', title: __('Change_type')},
                        {field: 'change_sku', title: __('Change_sku')},
                        {field: 'change_number', title: __('Change_number')},
                        {field: 'recipe_type', title: __('Recipe_type')},
                        {field: 'lens_type', title: __('Lens_type')},
                        {field: 'coating_type', title: __('Coating_type')},
                        {field: 'second_name', title: __('Second_name')},
                        {field: 'zsl', title: __('Zsl')},
                        {field: 'od_sph', title: __('Od_sph')},
                        {field: 'od_cyl', title: __('Od_cyl')},
                        {field: 'od_axis', title: __('Od_axis')},
                        {field: 'od_add', title: __('Od_add')},
                        {field: 'pd_r', title: __('Pd_r')},
                        {field: 'od_pv', title: __('Od_pv')},
                        {field: 'od_bd', title: __('Od_bd')},
                        {field: 'od_pv_r', title: __('Od_pv_r')},
                        {field: 'od_bd_r', title: __('Od_bd_r')},
                        {field: 'os_sph', title: __('Os_sph')},
                        {field: 'os_cyl', title: __('Os_cyl')},
                        {field: 'os_axis', title: __('Os_axis')},
                        {field: 'os_add', title: __('Os_add')},
                        {field: 'pd_l', title: __('Pd_l')},
                        {field: 'os_pv', title: __('Os_pv')},
                        {field: 'os_bd', title: __('Os_bd')},
                        {field: 'os_pv_r', title: __('Os_pv_r')},
                        {field: 'os_bd_r', title: __('Os_bd_r')},
                        {field: 'create_person', title: __('Create_person')},
                        {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
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
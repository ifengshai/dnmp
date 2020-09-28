define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'bootstrap-table-jump-to'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'warehouse/product_bar_code/index' + location.search,
                    add_url: 'warehouse/product_bar_code/add',
                    table: 'product_bar_code'
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
                        { checkbox: true },
                        { field: 'id', title: __('Id') },
                        { field: 'name', title: __('名称'), operate: false },
                        { field: 'number', title: __('条码数量'), operate: false },
                        { field: 'range', title: __('编码范围'), operate: false },
                        {
                            field: 'status', title: __('状态'), operate: false,
                            custom: { 1: 'success', 0: 'danger' },
                            searchList: { 1: '已打印', 0: '未打印' },
                            formatter: Table.api.formatter.status
                        },
                        { field: 'create_person', title: __('创建人') },
                        { field: 'create_time', title: __('创建时间'), operate: 'RANGE' },
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate,
                            buttons: [
                            {
                                name: 'print',
                                text: __('打印'),
                                title: __('打印'),
                                classname: 'btn btn-xs btn-click',
                                // url: 'warehouse/product_bar_code/print_label',
                                // extend:'target="_blank"',
                                click:function(e, data){
                                    var s_obj = $(this).parent().parent().find('.text-danger');
                                    s_obj.html('<i class="fa fa-circle"></i> 已打印');
                                    s_obj.removeClass('text-danger').addClass('text-success');
                                    $(this).remove();
                                    window.open(Config.moduleurl + '/warehouse/product_bar_code/print_label/ids/' + data.id, '_blank');
                                },
                                visible: function (row) {
                                    //返回true时按钮显示,返回false隐藏
                                    if (row.status == 0) {
                                        return true;
                                    } else {
                                        return false;
                                    }
                                }
                            }

                        ], formatter: Table.api.formatter.operate
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
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
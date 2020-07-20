define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'jqui'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'warehouse/transfer_order/index' + location.search,
                    add_url: 'warehouse/transfer_order/add',
                    edit_url: 'warehouse/transfer_order/edit',
                    del_url: 'warehouse/transfer_order/del',
                    multi_url: 'warehouse/transfer_order/multi',
                    table: 'transfer_order',
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
                        { field: 'transfer_order_number', title: __('Transfer_order_number') },
                        // {field: 'call_out_site', title: __('Call_out_site')},
                        // {field: 'call_in_site', title: __('Call_in_site')},
                        // {field: 'remark', title: __('Remark')},
                        {
                            field: 'status', title: __('Status'), custom: { 0: 'success', 1: 'yellow', 2: 'blue', 3: 'danger', 4: 'gray' },
                            searchList: { 0: '新建', 1: '待审核', 2: '已审核', 3: '已拒绝', 4: '已取消' },
                            formatter: Table.api.formatter.status
                        },
                        { field: 'create_time', title: __('Create_time'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime },
                        { field: 'create_person', title: __('Create_person') },
                        { field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
            $(document).on('click', '.btn-add', function () {
                var content = $('#table-content table tbody').html();
                $('.table_list table tbody').append(content);
                Controller.api.bindevent();
            })

            //删除商品数据
            $(document).on('click', '.btn-del', function () {
                $(this).parent().parent().remove();
            })

            //获取sku信息
            $(document).on('change', '.sku', function () {
                var sku = $(this).val();
                var platform_type = $('.call_out_site.selectpicker').val();
                var _this = $(this);
                if (!sku || !platform_type) {
                    Toastr.error('SKU和调出虚拟仓不能为空');
                    return false;
                }
                Backend.api.ajax({
                    url: 'warehouse/transfer_order/getSkuData',
                    data: { sku: sku, platform_type: platform_type }
                }, function (data, ret) {
                    _this.parent().parent().find('.sku_stock').val(data);
                }, function (data, ret) {
                    Fast.api.error(ret.msg);
                });

            })
        },
        edit: function () {
            Controller.api.bindevent();
            $(document).on('click', '.btn-add', function () {
                var content = $('#table-content table tbody').html();
                $('.table_list table tbody').append(content);
                Controller.api.bindevent();
            })

            //删除商品数据
            $(document).on('click', '.btn-del', function () {
                $(this).parent().parent().remove();
            })
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));

                //模糊匹配订单
                $('.sku').autocomplete({
                    source: function (request, response) {
                        $.ajax({
                            type: "POST",
                            url: "ajax/ajaxGetLikeOriginSku",
                            dataType: "json",
                            cache: false,
                            async: false,
                            data: {
                                origin_sku: request.term
                            },
                            success: function (json) {
                                var data = json.data;
                                response($.map(data, function (item) {
                                    return {
                                        label: item,//下拉框显示值
                                        value: item,//选中后，填充到input框的值
                                        //id:item.bankCodeInfo//选中后，填充到id里面的值
                                    };
                                }));
                            }
                        });
                    },
                    delay: 10,//延迟100ms便于输入
                    select: function (event, ui) {
                        $("#bankUnionNo").val(ui.item.id);//取出在return里面放入到item中的属性
                    },
                    scroll: true,
                    pagingMore: true,
                    max: 5000
                });
            }
        }
    };
    return Controller;
});
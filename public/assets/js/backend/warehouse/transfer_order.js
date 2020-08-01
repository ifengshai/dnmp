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
                    // del_url: 'warehouse/transfer_order/del',
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
                        { field: 'id', title: __('Id'), operate: false },
                        { field: 'transfer_order_number', title: __('Transfer_order_number') },
                        // {field: 'call_out_site', title: __('Call_out_site')},
                        // {field: 'call_in_site', title: __('Call_in_site')},
                        // {field: 'remark', title: __('Remark')},
                        {
                            field: 'status', title: __('Status'), custom: { 0: 'success', 1: 'yellow', 2: 'blue', 3: 'danger', 4: 'gray' },
                            searchList: { 0: '新建', 1: '待审核', 2: '已审核', 3: '已拒绝', 4: '已取消' },
                            formatter: Table.api.formatter.status
                        },
                        { field: 'sku', title: ('SKU'), visible: false },

                        { field: 'create_time', title: __('Create_time'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime },
                        { field: 'create_person', title: __('Create_person') },
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate, buttons: [
                                {
                                    name: 'detail',
                                    text: '详情',
                                    title: __('Detail'),
                                    classname: 'btn btn-xs  btn-primary btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'warehouse/transfer_order/detail',
                                    extend: 'data-area = \'["80%","80%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                },
                                {
                                    name: 'edit',
                                    text: '',
                                    title: __('Edit'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-pencil',
                                    url: 'warehouse/transfer_order/edit',
                                    extend: 'data-area = \'["80%","80%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        if (row.status == 0) {
                                            return true;
                                        }
                                        return false;
                                    }
                                },
                                {
                                    name: 'edit',
                                    text: '',
                                    title: __('Edit'),
                                    classname: 'btn btn-xs btn-cancel btn-danger',
                                    icon: 'fa fa-remove',
                                    // url: 'warehouse/transfer_order/cancel',
                                    // callback: function (data) {
                                    //     Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    // },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        if (row.status == 0) {
                                            return true;
                                        }
                                        return false;
                                    }
                                },
                            ]
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            //审核通过
            $(document).on('click', '.btn-open', function () {
                var ids = Table.api.selectedids(table);
                Backend.api.ajax({
                    url: Config.moduleurl + '/warehouse/transfer_order/setStatus',
                    data: { ids: ids, status: 2 }
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })
            //取消调拨单
            $(document).on('click', '.btn-cancel', function () {
                // var ids = $(this).data('id');
                var ids = Table.api.selectedids(table);
                Layer.confirm(
                    __('确定要取消吗'),
                    function (index) {
                        Backend.api.ajax({
                            url: "warehouse/transfer_order/cancel",
                            data: { ids: ids }
                        }, function (data, ret) {
                            table.bootstrapTable('refresh');
                            Layer.close(index);
                        });
                    }
                );
            });
            //审核拒绝
            $(document).on('click', '.btn-close', function () {
                var ids = Table.api.selectedids(table);
                Backend.api.ajax({
                    url: Config.moduleurl + '/warehouse/transfer_order/setStatus',
                    data: { ids: ids, status: 3 }
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })
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
                var _this = $(this);
                var id = $(this).parent().parent().find('.item_id').val();
                if (id) {
                    Layer.confirm(__('确定删除此数据吗?'), function () {
                        _this.parent().parent().remove();
                        Backend.api.ajax({
                            url: Config.moduleurl + '/warehouse/transfer_order/deleteItem',
                            data: { id: id }
                        }, function () {
                            Layer.closeAll();
                        });
                    });
                } else {
                    // _this.parent().parent().remove();
                }
            })
        },
        detail: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));

                $(document).on('click', '.btn-status', function () {
                    $('.status').val(1);
                })

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
            }
        }
    };
    return Controller;
});
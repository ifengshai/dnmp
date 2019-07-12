define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                searchFormVisible: true,
                extend: {
                    index_url: 'purchase/contract/index' + location.search,
                    add_url: 'purchase/contract/add',
                    edit_url: 'purchase/contract/edit',
                    del_url: 'purchase/contract/del',
                    multi_url: 'purchase/contract/multi',
                    table: 'contract',
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
                        { field: 'contract_number', title: __('Contract_number') },
                        { field: 'contract_name', title: __('Contract_name') },
                        { field: 'supplier.supplier_name', title: __('Supplier_name') },
                        { field: 'supplier.supplier_type', title: __('Supplier_type'), searchList: { 1: '镜片', 2: '镜架', 3: '眼镜盒', 4: '镜布' }, formatter: Controller.api.formatter.supplier_type },
                        { field: 'status', title: __('Status'), custom: { 0: 'success', 1: 'yellow', 2: 'blue', 3: 'danger', 4: 'gray' }, searchList: { 0: '新建', 1: '待审核', 2: '已通过', 3: '已拒绝', 4: '已取消' }, formatter: Table.api.formatter.status },
                        { field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange' },
                        { field: 'create_person', title: __('Create_person') },
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, buttons: [
                                {
                                    name: 'detail',
                                    text: '详情',
                                    title: __('Detail'),
                                    classname: 'btn btn-xs  btn-primary  btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'purchase/contract/detail',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                },
                                {
                                    name: 'cancel',
                                    text: '取消',
                                    title: '取消',
                                    classname: 'btn btn-xs btn-danger btn-cancel',
                                    icon: 'fa fa-remove',
                                    url: 'purchase/contract/cancel',
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
                                    url: 'purchase/contract/edit',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                }

                            ], formatter: Table.api.formatter.operate
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
                    url: '/admin/purchase/contract/setStatus',
                    data: { ids: ids, status: 2 }
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })

            //审核拒绝
            $(document).on('click', '.btn-close', function () {
                var ids = Table.api.selectedids(table);
                Backend.api.ajax({
                    url: '/admin/purchase/contract/setStatus',
                    data: { ids: ids, status: 3 }
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })

            //取消
            $(document).on('click', '.btn-cancel', function (e) {
                e.preventDefault();
                var url = $(this).attr('href');
                Backend.api.ajax({
                    url: url,
                    data: { status: 4 }
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })


        },
        add: function () {
            Controller.api.bindevent(function (data, ret) {
                location.href = data;
            });
            //移除
            $(document).on('click', '.btn-del', function () {
                $(this).parent().parent().remove();
            })

            //数量触发事件
            $(document).on('blur', '.num', function () {
                var num = $(this).val();
                var price = $(this).parent().next().find('.price').val();
                if (num && price) {
                    var total = num * 1 * price;
                    $(this).parent().next().next().find('.total').val(total);
                }
                //计算总数量
                var allnum = 0;
                $('.num').each(function () {
                    var num = $(this).val();
                    allnum += num*1;
                })
                $('.allnum').val(allnum);
                
                //计算总金额
                var alltotal = 0;
                $('.total').each(function () {
                    var total = $(this).val();
                    alltotal += total*1;
                })
                $('.alltotal').val(alltotal);
            })

            //单价触发事件
            $(document).on('blur', '.price', function () {
                var price = $(this).val();
                var num = $(this).parent().prev().find('.num').val();
                if (num && price) {
                    var total = num * 1 * price;
                    $(this).parent().next().find('.total').val(total);
                }
                var allnum = 0;
                $('.num').each(function () {
                    var num = $(this).val();
                    allnum += num*1;
                })
                $('.allnum').val(allnum);

                //计算总金额
                var alltotal = 0;
                $('.total').each(function () {
                    var total = $(this).val();
                    alltotal += total*1;
                })
                $('.alltotal').val(alltotal);
            })


        },
        edit: function () {
            Controller.api.bindevent(function (data, ret) {
                location.href = data;
            });
            //删除商品数据
            $(document).on('click', '.btn-del', function () {
                $(this).parent().parent().remove();
                var id = $(this).parent().parent().find('.item_id').val();
                if (id) {
                    Backend.api.ajax({
                        url: '/admin/purchase/contract/deleteItem',
                        data: { id: id }
                    });
                }
            })

            //数量触发事件
            $(document).on('blur', '.num', function () {
                var num = $(this).val();
                var price = $(this).parent().next().find('.price').val();
                if (num && price) {
                    var total = num * 1 * price;
                    $(this).parent().next().next().find('.total').val(total);
                }
                //计算总数量
                var allnum = 0;
                $('.num').each(function () {
                    var num = $(this).val();
                    allnum += num*1;
                })
                $('.allnum').val(allnum);
                
                //计算总金额
                var alltotal = 0;
                $('.total').each(function () {
                    var total = $(this).val();
                    alltotal += total*1;
                })
                $('.alltotal').val(alltotal);
            })

            //单价触发事件
            $(document).on('blur', '.price', function () {
                var price = $(this).val();
                var num = $(this).parent().prev().find('.num').val();
                if (num && price) {
                    var total = num * 1 * price;
                    $(this).parent().next().find('.total').val(total);
                }
                var allnum = 0;
                $('.num').each(function () {
                    var num = $(this).val();
                    allnum += num*1;
                })
                $('.allnum').val(allnum);

                //计算总金额
                var alltotal = 0;
                $('.total').each(function () {
                    var total = $(this).val();
                    alltotal += total*1;
                })
                $('.alltotal').val(alltotal);
            })
        },
        detail: function () {
            Controller.api.bindevent();
        },
        api: {
            formatter: {
                supplier_type: function (value, row, index) {
                    var str = '';
                    if (value == 1) {
                        str = '镜片';
                    } else if (value == 2) {
                        str = '镜架';
                    } else if (value == 3) {
                        str = '眼镜盒';
                    } else if (value == 4) {
                        str = '镜布';
                    }
                    return str;
                }
            },
            bindevent: function (success, error) {
                $(document).on('click', '.btn-status', function () {
                    $('.status').val(1);
                })

                Form.api.bindevent($("form[role=form]"), success, error);

                $(document).on('click', '.btn-add', function () {
                    var content = $('#table-content table tbody').html();
                    $('.caigou table tbody').append(content);
                })



                //切换结算方式
                $(document).on('change', '.settlement_method', function () {
                    var v = $(this).val();
                    if (v == 3) {
                        $('.deposit_amount').removeClass('hidden');
                        $('.final_amount').removeClass('hidden');
                    } else {
                        $('.deposit_amount').addClass('hidden');
                        $('.final_amount').addClass('hidden');
                    }

                })

                //异步获取供应商的数据
                $(document).on('change', '.supplier', function () {
                    var id = $(this).val();
                    Backend.api.ajax({
                        url: '/admin/purchase/contract/getSupplierData',
                        data: { id: id }
                    }, function (data, ret) {
                        $('#c-seller_address').val(data.address);
                        $('#c-seller_phone').val(data.telephone);
                    });
                })
            }
        }
    };
    return Controller;
});
define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'financepurchase/purchase_pay/index',
                    add_url: 'financepurchase/purchase_pay/add',
                    edit_url: 'financepurchase/purchase_pay/edit',
                    // del_url: 'user/user/del',
                    // multi_url: 'user/user/multi',
                    // table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                // sortName: 'id',
                searchList:true,
                columns: [
                    [
                        // {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'order_number', title: __('付款申请单号'), operate: 'LIKE'},
                        {field: 'supplier_name', title: __('供应商名称'), operate: 'LIKE'},
                        {
                            field: 'pay_type', title: __('付款类型'), custom: { 0: 'success', 1: 'yellow', 2: 'blue', 3: 'danger'},
                            searchList: { 0: '新建', 1: '预付款', 2: '全款预付', 3: '尾款'},
                            formatter: Table.api.formatter.status
                        },
                        {field: 'pay_grand_total', title: __('付款金额（￥）'), visible: false},
                        {field: 'base_currency_code', title: __('付款币种'), visible: false},
                        {
                            field: 'status', title: __('状态'), custom: { 0: 'success', 1: 'yellow', 2: 'blue', 3: 'danger'},
                            searchList: { 0: '新建', 1: '待审核', 2: '审核通过', 3: '审核拒绝', 4: '已完成', 5: '已取消'},
                            formatter: Table.api.formatter.status
                        },
                        {field: 'create_person', title: __('创建人'), operate: 'LIKE'},
                        {field: 'create_time', title: __('创建时间'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, buttons: [
                                {
                                    name: 'detail',
                                    text: '详情',
                                    title: __('Detail'),
                                    classname: 'btn btn-xs  btn-primary  btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'financepurchase/purchase_pay/detail',
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
                                    name: 'edit',
                                    text: '编辑',
                                    title: __('Edit'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-pencil',
                                    url: 'financepurchase/purchase_pay/edit',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        if (row.status == 0) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'cancel',
                                    text: '取消',
                                    title: '取消',
                                    classname: 'btn btn-xs btn-danger btn-cancel',
                                    icon: 'fa fa-remove',
                                    url: 'financepurchase/purchase_pay/cancel',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        if (row.status == 0) {
                                            return true;
                                        } else {
                                            return false;
                                        }

                                    }
                                },
                            ], formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });
            //审核取消
            $(document).on('click', '.btn-cancel', function (e) {
                e.preventDefault();
                var url = $(this).attr('href');
                Backend.api.ajax({
                    url: url,
                    data: {status: 5}
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
            //获取采购单信息
            $(document).on('change', '#purchase_number', function () {
                var purchase_number = $(this).val();
                var pay_type = $('#pay_type').val();
                var _this = $(this);
                if (pay_type == 0) {
                    Layer.alert('请先选择付款类型');
                    return false;
                }
                Backend.api.ajax({
                    url: 'financepurchase/purchase_pay/getPurchaseDetail',
                    data: {purchase_number: purchase_number,pay_type:pay_type}
                }, function (data, ret) {
                    //渲染基本信息
                    $('#purchase_type').val(data.purchase_order.purchase_type)
                    $('#purchase_number').val(data.purchase_order.purchase_number)
                    $('#purchase_name').val(data.purchase_order.purchase_name)
                    $('#supplier_name').val(data.data.supplier_name)
                    $('#supplier_time').val(data.data.period)
                    $('#linkname').val(data.data.linkname)
                    $('#opening_bank_address').val(data.data.opening_bank)
                    $('#bank_account').val(data.data.bank_account)
                    $('#base_currency_code').val(data.data.currency)
                    //渲染采购事由
                    $('#id').val(data.purchase_detail.id)
                    $('#name').val(data.purchase_detail.sku)
                    $('#number').val(data.purchase_order.purchase_number)
                    $('#type').val(data.purchase_detail.type)
                    $('#num').val(data.purchase_detail.purchase_num)
                    $('#single').val(data.purchase_detail.purchase_price)
                    $('#money').val(data.purchase_detail.purchase_total)
                    $('#purchase_total').val(data.purchase_order.purchase_total)
                    $('#supplier_id').val(data.data.id)
                    $('#purchase_id').val(data.purchase_order.id)
                    if (pay_type == 1) {
                        $('#pay_grand_total').val(($('#purchase_total').val() * 0.3).toFixed(2));
                    }else if(pay_type == 2){
                        $('#pay_grand_total').val($('#purchase_total').val());
                        $('#pay_rate').val(1);
                    }
                }, function (data, ret) {
                    Fast.api.error(ret.msg);
                });

            })
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
                //付款类型
                $(document).on('change', '#pay_type', function () {
                    var val = $(this).val();
                    //预付款
                    if (val == 1) {
                        $('#pay_rate').val('0.3');
                        $('#fuikuanbili').removeClass('hidden');
                        $('#purchase_text').html('采购单号:');
                        purchase_total = ($('#purchase_total').val() * 0.3).toFixed(2);
                        $('#pay_grand_total').val(purchase_total);
                    } else if(val == 3) {
                        $('#purchase_text').html('结算单号:');
                        $('#fuikuanbili').addClass('hidden');
                    } else {//全款预付
                        $('#fuikuanbili').addClass('hidden');
                        $('#purchase_text').html('采购单号:');
                        $('#pay_rate').val('1');
                        purchase_total = $('#purchase_total').val()
                        $('#pay_grand_total').val(purchase_total);
                    }
                });
                //保存 跟提交审核做校验 付款类型不能为空
                $(document).on('click', '.btn-save', function () {
                    $('#status').val(0);
                    //付款类型
                    var pay_type = $('#pay_type').val();
                    if (pay_type == 0) {
                        Layer.alert('请先选择付款类型');
                        return false;
                    }
                })
                $(document).on('click', '.btn-save1', function () {
                    //提交审核传状态为1
                    $('#status').val(1);
                    var pay_type = $('#pay_type').val();
                    if (pay_type == 0) {
                        Layer.alert('请先选择付款类型');
                        return false;
                    }
                })

            }
        }
    };
    return Controller;
});
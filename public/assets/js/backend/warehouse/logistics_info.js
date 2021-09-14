define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'bootstrap-table-jump-to'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'warehouse/logistics_info/index' + location.search,
                    table: 'logistics_info',
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
                        {field: 'id', title: __('Id'), operate: false},
                        {field: 'logistics_number', title: __('物流单号'), operate: 'like'},
                        {
                            field: 'type', title: __('单据类型'),
                            custom: {1: 'success', 2: 'success', 3: 'success'},
                            searchList: {1: '采购单', 2: '退销单', 3: '退货单'},
                            formatter: Table.api.formatter.status
                        },
                        {field: 'order_number', title: __('关联单号')},
                        {field: 'supplier_sku', title: __('供应商SKU')},
                        {field: 'purchase_num', title: __('采购数量'), operate: false},
                        // { field: 'purchase_name', title: __('采购名称'), operate: false },
                        {field: 'sku', title: __('sku'), operate: false},
                        {field: 'supplier_name', title: __('供应商名称'), operate: false},
                        {field: 'batch_id', title: __('关联批次ID')},
                        {
                            field: 'status', title: __('签收状态'), custom: {1: 'success', 0: 'danger'},
                            searchList: {1: '已签收', 0: '未签收'},
                            formatter: Table.api.formatter.status
                        },

                        {
                            field: 'receiving_warehouse', title: __('收货仓')
                        },
                        {
                            field: 'sign_warehouse', title: __('签收仓')
                        },
                        {
                            field: 'sign_time', title: __('签收时间'), operate: 'RANGE', addclass: 'datetimerange'
                        },
                        {
                            field: 'sign_person', title: __('签收人')
                        },
                        {
                            field: 'is_check_order', title: __('质检状态'), custom: {1: 'success', 0: 'danger'},
                            searchList: {1: '已质检', 0: '未质检'},
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'is_new_product', title: __('是否为新品采购单'), custom: {1: 'success', 0: 'danger'},
                            searchList: {1: '是', 0: '否'}, operate: false,
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'factory_type', title: __('工厂类型'), custom: {1: 'success', 0: 'danger'},
                            searchList: {1: '贸易', 0: '工厂'}, operate: false,
                            formatter: Table.api.formatter.status
                        },
                        {field: 'createtime', title: __('创建时间'), operate: 'RANGE', addclass: 'datetimerange'},
                        {field: 'create_person', title: __('创建人')},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'detail',
                                    text: '创建质检单',
                                    title: __('创建质检单'),
                                    classname: 'btn btn-xs  btn-success  btn-dialog',
                                    icon: 'fa fa-plus',
                                    url: 'warehouse/check/add',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        if (row.status == 1 && row.type == 1) {
                                            return true;
                                        }
                                        return false;
                                    }
                                },
                                {
                                    name: 'signin',
                                    text: __('签收'),
                                    title: __('签收'),
                                    classname: 'btn btn-xs btn-success btn-ajax',
                                    url: 'warehouse/logistics_info/is_wrong_sign',
                                    // confirm: '确定要签收吗',
                                    success: function (data, ret) {
                                        if ((ret.msg) == 1) {
                                            //询问框
                                            layer.confirm('收货仓与签收仓不一致，确定签收吗？', {
                                                btn: ['确定', '取消']
                                            }, function () {
                                                $.ajax({
                                                    type: "POST",
                                                    url: Config.moduleurl + '/warehouse/logistics_info/signin',
                                                    data: {id: ret.data},
                                                    success: function (data) {
                                                        layer.msg(data.msg);
                                                        $(".btn-refresh").trigger("click");
                                                    },
                                                    error: function (data, ret) {
                                                        Layer.alert(ret.msg);
                                                        return false;
                                                    },
                                                });
                                            });
                                        } else {
                                            //询问框
                                            layer.confirm('确定要签收吗？', {
                                                btn: ['确定', '取消']
                                            }, function () {
                                                $.ajax({
                                                    type: "POST",
                                                    url: Config.moduleurl + '/warehouse/logistics_info/signin',
                                                    data: {id: ret.data},
                                                    success: function (data) {
                                                        layer.msg(data.msg);
                                                        $(".btn-refresh").trigger("click");
                                                    },
                                                    error: function (data, ret) {
                                                        Layer.alert(ret.msg);
                                                        return false;
                                                    },
                                                });
                                            });
                                        }
                                    },
                                    error: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    callback: function (data) {
                                    },
                                    visible: function (row) {
                                        if (row.status == 0 && row.type == 1) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                }

                            ],
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);


            //批量签收
            $(document).on('click', '.btn-open', function () {
                var ids = Table.api.selectedids(table);
                $.ajax({
                    type: "POST",
                    url: Config.moduleurl + '/warehouse/logistics_info/is_wrong_sign_batch',
                    data: {ids: ids},
                    success: function (data, ret) {
                        console.log(data)
                        console.log(ret)
                        if ((data.msg) == 1) {
                            //询问框
                            layer.confirm('物流单号' + data.data + '收货仓与签收仓不一致，确定签收吗？', {
                                btn: ['确定', '取消']
                            }, function () {
                                $.ajax({
                                    type: "POST",
                                    url: Config.moduleurl + '/warehouse/logistics_info/batch_signin',
                                    data: {ids: ids},
                                    success: function (data) {
                                        layer.msg(data.msg);
                                        $(".btn-refresh").trigger("click");
                                    },
                                    error: function (data) {
                                        Layer.alert(data.msg);
                                        return false;
                                    },
                                });
                            });
                        } else if ((data.msg) == 0){
                            //询问框
                            layer.confirm('确定要签收吗？', {
                                btn: ['确定', '取消']
                            }, function () {
                                $.ajax({
                                    type: "POST",
                                    url: Config.moduleurl + '/warehouse/logistics_info/batch_signin',
                                    data: {ids: ids},
                                    success: function (data) {
                                        console.log(data)
                                        layer.msg(data.msg);
                                        $(".btn-refresh").trigger("click");
                                    },
                                    error: function (data) {
                                        console.log(data)
                                        Layer.alert(data.msg);
                                        return false;
                                    },
                                });
                            });
                        }else{
                            Layer.alert(data.msg);
                            return false;
                        }
                    },error: function (data) {

                        Layer.alert(data.msg);
                        return false;
                    }
                });
            });
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
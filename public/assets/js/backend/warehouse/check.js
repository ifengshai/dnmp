define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                searchFormVisible: true,
                extend: {
                    index_url: 'warehouse/check/index' + location.search,
                    add_url: 'warehouse/check/add',
                    edit_url: 'warehouse/check/edit',
                    del_url: 'warehouse/check/del',
                    multi_url: 'warehouse/check/multi',
                    table: 'check_order',
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
                        { field: 'check_order_number', title: __('Check_order_number') },
                        { field: 'type', title: __('Type'), custom: { 1: 'success', 2: 'success' }, searchList: { 1: '采购质检', 2: '退货质检' }, formatter: Table.api.formatter.status },
                        { field: 'purchase_order.purchase_number', title: __('Purchase_id') },
                        { field: 'order_number', title: __('Order_number') },
                        { field: 'supplier.supplier_name', title: __('Supplier_id') },
                        { field: 'remark', title: __('Remark'), operate: false },
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
                                    url: 'warehouse/check/detail',
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
                                    text: '',
                                    title: __('Edit'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-pencil',
                                    url: 'warehouse/check/edit',
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
        },
        add: function () {
            Controller.api.bindevent();
            //上传文件
            $(document).on('click', '.pluploads', function () {
                var _this = $(this);
                var url = _this.parent().parent().parent().find('.unqualified_images').val();
                Fast.api.open(
                    'warehouse/check/uploads?img_url=' + url, '上传文件', {
                        callback: function (data) {
                            _this.parent().parent().parent().find('.unqualified_images').val(data.unqualified_images);
                        }
                    }
                )
            })


            //移除
            $(document).on('click', '.btn-del', function () {
                $(this).parent().parent().remove();
            })

            $(document).on('click', '.btn-add', function () {
                var content = $('#table-content table tbody').html();
                $('.caigou table tbody').append(content);
            })



        },
        uploads: function () {
            Controller.api.bindevent(function (data, ret) {
                //这里是表单提交处理成功后的回调函数，接收来自php的返回数据
                Fast.api.close(data);//这里是重点
                return false;
            });

            $('.unqualified_images').change();

        },
        edit: function () {
            Controller.api.bindevent();
            //上传文件
            $(document).on('click', '.pluploads', function () {
                var _this = $(this);
                var url = _this.parent().parent().parent().find('.unqualified_images').val();
                Fast.api.open(
                    'warehouse/check/uploads?img_url=' + url, '上传文件', {
                        callback: function (data) {
                            _this.parent().parent().parent().find('.unqualified_images').val(data.unqualified_images);
                        }
                    }
                )
            })

            //删除商品数据
            $(document).on('click', '.btn-del', function () {
                $(this).parent().parent().remove();
                var id = $(this).parent().parent().find('.item_id').val();
                if (id) {
                    Backend.api.ajax({
                        url: '/admin/warehouse/check/deleteItem',
                        data: { id: id }
                    });
                }
            })

            //新增
            $(document).on('click', '.btn-add', function () {
                var content = $('#table-content table tbody').html();
                $('.caigou table tbody').append(content);
            })
        },
        detail: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function (success, error) {


                Form.api.bindevent($("form[role=form]"), success, error);


                //切换质检类型
                $(document).on('change', '.type', function () {
                    var type = $(this).val();
                    if (type == 1) {
                        $('.order_number').addClass('hidden');
                        $('.purchase_id_number').removeClass('hidden');
                        $('#c-order_number').val('');
                    } else {
                        $('.order_number').removeClass('hidden');
                        $('.purchase_id_number').addClass('hidden');
                        $('.purchase_id').val('');
                    }
                })

                //计算不合格数量及合格率
                $(document).on('blur', '.arrivals_num', function () {
                    var check_num = $(this).parent().prev().find('input').val();
                    var arrivals_num = $(this).val();
                    var quantity_num = $(this).parent().next().find('.quantity_num').val();
                    var sample_num = $(this).parent().next().next().find('.sample_num').val();
                    var not_quantity_num = arrivals_num * 1 - quantity_num * 1 - sample_num * 1;

                    $(this).parent().next().next().next().find('input').val(not_quantity_num);
                    $(this).parent().next().next().next().next().find('input').val(Math.round(quantity_num / arrivals_num * 100, 2));
                })

                //计算不合格数量及合格率
                $(document).on('blur', '.quantity_num', function () {
                    var check_num = $(this).parent().prev().prev().find('input').val();
                    var arrivals_num = $(this).parent().prev().find('input').val();
                    var quantity_num = $(this).val();
                    var sample_num = $(this).parent().next().find('.sample_num').val();
                    var not_quantity_num = arrivals_num * 1 - quantity_num * 1 - sample_num * 1;
                   
                    $(this).parent().next().next().find('input').val(not_quantity_num);
                    $(this).parent().next().next().next().find('input').val(Math.round(quantity_num / arrivals_num * 100, 2));
                })

                //计算不合格数量及合格率
                $(document).on('blur', '.sample_num', function () {
                    var check_num = $(this).parent().prev().prev().prev().find('input').val();
                    var arrivals_num = $(this).parent().prev().prev().find('input').val();
                    var quantity_num = $(this).parent().prev().find('input').val();
                    var sample_num = $(this).val();
                    var not_quantity_num = arrivals_num * 1 - quantity_num * 1 - sample_num * 1;

                    $(this).parent().next().find('input').val(not_quantity_num);
                    $(this).parent().next().next().find('input').val(Math.round(quantity_num / arrivals_num * 100, 2));
                })


                //切换合同 异步获取合同数据
                $(document).on('change', '.purchase_id', function () {
                    var id = $(this).val();
                    if (id) {
                        var url = '/admin/warehouse/check/getPurchaseData';
                        Backend.api.ajax({
                            url: url,
                            data: { id: id }
                        }, function (data, ret) {

                            $('.supplier').val(data.supplier_id);
                            //循环展示商品信息
                            var shtml = ' <tr><th>SKU</th><th>供应商SKU</th><th>采购数量</th><th>已质检数量</th><th>到货数量</th><th>合格数量</th><th>留样数量</th><th>不合格数量</th><th>合格率</th><th>备注</th><th>上传图片</th><th>操作</th></tr>';
                            $('.caigou table tbody').html('');
                            for (var i in data.item) {
                                shtml += ' <tr><td><input id="c-purchase_remark" class="form-control" name="sku[]" type="text" value="' + data.item[i].sku + '"></td>'
                                shtml += ' <td><input id="c-purchase_remark" class="form-control" name="supplier_sku[]" type="text" value="' + data.item[i].supplier_sku + '"></td>'
                                shtml += ' <td><input id="c-purchase_remark" class="form-control purchase_num" name="purchase_num[]" type="text" redeonly value="' + data.item[i].purchase_num + '"></td>'
                                shtml += ' <td><input id="c-purchase_remark" class="form-control check_num" name="check_num[]" type="text" readonly value="' + data.item[i].check_num + '"></td>'
                                shtml += ' <td><input id="c-purchase_remark" class="form-control arrivals_num" name="arrivals_num[]" type="text"></td>'
                                shtml += ' <td><input id="c-purchase_remark" class="form-control quantity_num" name="quantity_num[]" type="text"></td>'
                                shtml += ' <td><input id="c-purchase_remark" class="form-control sample_num" name="sample_num[]" type="text"></td>'
                                shtml += ' <td><input id="c-purchase_remark" class="form-control unqualified_num" name="unqualified_num[]" type="text"></td>'
                                shtml += '  <td><input id="c-purchase_remark" class="form-control quantity_rate" name="quantity_rate[]" type="text">%</td>'
                                shtml += ' <td><input style="width: 200px;" id="c-purchase_remark" class="form-control remark" name="remark[]" type="text"></td>'
                                shtml += ' <td><input id="c-unqualified_images" style="width: 150px;" class="form-control unqualified_images" size="200" readonly name="unqualified_images[]" type="text"></td>'

                                shtml += ' <td><span><button type="button" id="plupload-unqualified_images" class="btn btn-danger pluploads" data-input-id="c-unqualified_images" data-mimetype="image/gif,image/jpeg,image/png,image/jpg,image/bmp" data-multiple="true" data-maxcount="3" data-preview-id="p-unqualified_images"><i class="fa fa-upload"></i>'
                                shtml += ' 上传</button></span>'

                                shtml += ' <a href="javascript:;" class="btn btn-danger btn-del" title="删除"><i class="fa fa-trash"></i>删除</a>'
                                shtml += ' </td>'

                                shtml += ' </tr>'
                            }
                            $('.caigou table tbody').append(shtml);
                        });
                    }

                })


            }
        }
    };
    return Controller;
});
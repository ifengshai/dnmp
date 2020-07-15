define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                searchFormVisible: true,

                extend: {
                    index_url: 'purchase/new_product_replenish_order/index' + location.search,
                    add_url: 'purchase/new_product_replenish_order/add',
                    edit_url: 'purchase/new_product_replenish_order/edit',
                    del_url: 'purchase/new_product_replenish_order/del',
                    multi_url: 'purchase/new_product_replenish_order/multi',
                    table: 'new_product_replenish_order',
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
                        {field: 'sku', title: __(' sku')},
                        {field: 'replenishment_num', title: __('Replenishment_num'), operate: false},
                        {
                            field: 'status',
                            title: __('状态'),
                            custom: {1: 'blue', 2: 'danger', 3: 'orange', 4: 'red'},
                            searchList: {1: '待分配', 2: '待处理', 3: '部分处理', 4: '已处理'},
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'is_verify',
                            title: __('审核状态'),
                            custom: {0: 'blue', 1: 'green', 2: 'danger'},
                            searchList: {0: '待审核', 1: '审核通过', 2: '审核拒绝'},
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'create_time',
                            title: __('Create_time'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime
                        },
                        // {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate},
                        {
                            field: 'operate', title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: function (value, row, index) {
                                var that = $.extend({}, this);
                                $(table).data("operate-edit", null); // 列表页面隐藏 .编辑operate-edit  - 删除按钮operate-del
                                that.table = table;
                                return Table.api.formatter.operate.call(that, value, row, index);
                            }
                            // formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });


            // 为表格绑定事件
            Table.api.bindevent(table);
            //商品审核通过
            $(document).on('click', '.btn-pass', function () {
                var ids = Table.api.selectedids(table);
                Layer.confirm(
                    __('确定要审核通过吗'),
                    function (index) {
                        Backend.api.ajax({
                            url: "purchase/new_product_replenish_order/morePassAudit",
                            data: {ids: ids}
                        }, function (data, ret) {
                            table.bootstrapTable('refresh');
                            Layer.close(index);
                        });
                    }
                );
            });
            //商品审核拒绝
            $(document).on('click', '.btn-refused', function () {
                var ids = Table.api.selectedids(table);
                Layer.confirm(
                    __('确定要审核拒绝吗'),
                    function (index) {
                        Backend.api.ajax({
                            url: "purchase/new_product_replenish_order/moreAuditRefused",
                            data: {ids: ids}
                        }, function (data, ret) {
                            table.bootstrapTable('refresh');
                            Layer.close(index);
                        });
                    }
                );
            });
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        distribution: function () {
            // 初始化表格参数配置
            Table.api.init({
                singleSelect: true,
                showJumpto: true,
                searchFormVisible: true,
                extend: {
                    index_url: 'purchase/new_product_replenish_order/distribution' + location.search,
                    table: 'item',
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
                        // {checkbox: true},
                        {

                            field: '', title: __('序号'), formatter: function (value, row, index) {
                                var options = table.bootstrapTable('getOptions');
                                var pageNumber = options.pageNumber;
                                var pageSize = options.pageSize;

                                //return (pageNumber - 1) * pageSize + 1 + index;
                                return 1 + index;
                            }, operate: false, visible: false
                        },
                        {field: 'id', title: __('Id'), operate: false},
                        {field: 'sku', title: __(' sku'), operate: 'LIKE'},
                        {
                            field: 'status',
                            title: __('状态'),
                            custom: {1: 'blue', 2: 'danger', 3: 'orange', 4: 'red'},
                            searchList: {1: '待分配', 2: '待处理', 3: '部分处理', 4: '已处理'},
                            formatter: Table.api.formatter.status
                        },
                        {field: 'replenishment_num', title: __('Replenishment_num'), operate: false},
                        {
                            field: 'supplier',
                            title: __('分配数量'),
                            operate: false,
                            formatter: function (value, rows) {
                                var all_user_name = '';
                                if (value.length > 0) {
                                    for (i = 0, len = value.length; i < len; i++) {
                                        // all_user_name += '<div class="step_recept"><b class="step">' + value[i].name + '：</b><input id="'+ rows +'" type="text" class="form-control" style="display: inline-block;width: 180px;text-align: center;" value="'+ value[i].num +'"></div>';
                                        all_user_name += '<div class="step_recept"><b class="step">' + value[i].supplier_name + '：</b><b class="recept text-red">' + value[i].distribute_num + '</b></div>';
                                    }
                                }
                                return all_user_name;
                            },
                        },
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate,
                            buttons: [
                                // {
                                //     name: 'passAudit',
                                //     text: '确认分配',
                                //     title: __('确认分配'),
                                //     classname: 'btn btn-xs btn-success btn-confirmdis',
                                //     icon: 'fa fa-pencil',
                                //     visible: function (row) {
                                //         //返回true时按钮显示,返回false隐藏
                                //         if (row.status == 1) {
                                //             return true;
                                //         } else {
                                //             return false;
                                //         }
                                //     }
                                // },
                                {
                                    name: 'distribution_detail',
                                    text: __('确认分配'),
                                    title: __('确认分配'),
                                    icon: 'fa fa-pencil',
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    url: 'purchase/new_product_replenish_order/distribute_detail',
                                    visible: function (row) {
                                            //返回true时按钮显示,返回false隐藏
                                            if (row.status == 1) {
                                                return true;
                                            } else {
                                                return false;
                                            }
                                        },
                                    callback: function (data) {
                                    }
                                },

                            ],
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            //需求单确认分配
            // $(document).on('click', '.btn-confirmdis', function () {
            //     var ids = Table.api.selectedids(table);
            //     Layer.confirm(
            //         __('确定分配吗'),
            //         function (index) {
            //             Backend.api.ajax({
            //                 url: "purchase/new_product_replenish_order/distribution_confirm",
            //                 data: {ids: ids,}
            //             }, function (data, ret) {
            //                 table.bootstrapTable('refresh');
            //                 Layer.close(index);
            //             });
            //         }
            //     );
            // });
        },
        handle: function () {
            // 初始化表格参数配置
            Table.api.init({
                singleSelect: true,
                showJumpto: true,
                searchFormVisible: true,
                extend: {
                    index_url: 'purchase/new_product_replenish_order/handle' + location.search,
                    table: 'item',
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
                        // {checkbox: true},
                        {

                            field: '', title: __('序号'), formatter: function (value, row, index) {
                                var options = table.bootstrapTable('getOptions');
                                var pageNumber = options.pageNumber;
                                var pageSize = options.pageSize;

                                //return (pageNumber - 1) * pageSize + 1 + index;
                                return 1 + index;
                            }, operate: false, visible: false
                        },
                        {field: 'id', title: __('Id'), operate: false},
                        {field: 'sku', title: __(' sku'), operate: 'LIKE'},
                        {field: 'num', title: __('总需求数量'), operate: false},
                        {field: 'supplier_name', title: __('供应商'), operate: false},
                        {field: 'distribute_num', title: __('分配数量'), operate: false},
                        {field: 'real_dis_num', title: __('实际采购数量'), operate: false},
                        {field: 'purchase_person', title: __('采购负责人'), operate: false},
                        {
                            field: 'status',
                            title: __('状态'),
                            custom: {1: 'green', 2: 'danger'},
                            searchList: { 1: '未采购', 2: '已采购'},
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate,
                            buttons: [

                                {
                                    name: 'distribution_detail',
                                    text: __('创建采购单'),
                                    title: __('创建采购单'),
                                    extend:'data-area = \'["100%", "100%"]\' data-shade = \'[0.3, "#393D49"]\'',
                                    icon: 'fa fa-pencil',
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    url: 'purchase/new_product_replenish_order/purchase_order',
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        if (row.status == 1) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    },
                                    callback: function (data) {
                                    }
                                },

                            ],
                            formatter: Table.api.formatter.operate
                        }                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            //创建采购单
            $(document).on('click', '.btn-createPurchaseOrder', function () {
                var ids = Table.api.selectedids(table);
                Layer.confirm(
                    __('确定要创建采购单吗'),
                    function (index) {
                        Layer.closeAll();
                        var options = {
                            shadeClose: false,
                            shade: [0.3, '#393D49'],
                            area: ['100%', '100%'], //弹出层宽高
                            callback: function (value) {

                            }
                        };
                        // Fast.api.open('purchase/purchase_order/add?new_product_ids=' + ids.join(','), '创建采购单', options);
                        Fast.api.open('purchase/new_product_replenish_order/purchase_order?new_product_ids=' + ids.join(','), '创建采购单', options);

                    }
                );
            });
        },

        distribute_detail: function () {
            Controller.api.bindevent();
            $(document).on('click', '.btn-add', function () {
                var content = $('#table-content table tbody').html();
                $('.caigou table tbody').append(content);
                $('.selectpicker').selectpicker('refresh');
            })

            $(document).on('click', '.btn-del', function () {
                $('.selectpicker').selectpicker('refresh');
                $(this).parent().parent().remove();
            })
        },
        purchase_order: function () {
            Controller.api.bindevent();
            var z = 0;
            $(document).on('click', '.btn-add', function () {
                var content = $('#table-content table tbody').html();
                $('.purchase-table tbody').append(content);

                Controller.api.bindevent();
            })

            $(document).on('click', '.btn-del', function () {

                $(this).parent().parent().remove();
                var total = 0;
                $('.goods_total').each(function () {
                    var purchase_total = $(this).val();
                    total += purchase_total * 1;
                })
                //商品总价
                $('.total').val(total);
                //运费
                var freight = $('.freight').val();
                //总计
                $('.purchase_total').val(total + freight * 1);
            })

            $(document).on('click', '.btn-arrival-del', function () {
                $(this).parent().remove();
                z--;
            })

            //获取sku信息
            $(document).on('change', '.sku', function () {
                var sku = $(this).val();
                var supplier_id = $('.supplier.selectpicker').val();
                var _this = $(this);
                if (!sku) {
                    return false;
                }
                Backend.api.ajax({
                    url: 'ajax/getSkuList',
                    data: { sku: sku, supplier_id: supplier_id }
                }, function (data, ret) {
                    _this.parent().parent().find('.product_name').val(data.name);
                    _this.parent().parent().find('.supplier_sku').val(data.supplier_sku);
                }, function (data, ret) {
                    Fast.api.error(ret.msg);
                });

            })

            $(document).on('click', '.btn-addplus', function () {
                // var content = $('#arrival-content').html();
                // $('#arrival_div').append(content);
                var html = '<div class="list" style="margin-top:20px;">\n' +
                    '<input type="hidden" name="row[is_batch]" value="1">' +
                    '    <label class="control-label col-xs-12 col-sm-2" style="width:150px;">批次预计到货时间：</label>\n' +
                    '    <div class="col-xs-12 col-sm-3" style="margin-bottom: 20px;">\n' +
                    '        <input id="c-arrival_time" class="form-control datetimepicker arrival_time" data-rule="required" data-date-format="YYYY-MM-DD HH:mm:ss" data-use-current="true" name="batch_arrival_time[]" type="text" value="' + Config.newdatetime + '">\n' +
                    '    </div>\n' +
                    '    <a href="javascript:;" class="btn btn-danger btn-arrival-del" title="删除"><i class="fa fa-trash"></i>删除</a>\n' +
                    '    <table id="caigou-table" style="width:80%;">\n' +
                    '        <tr>\n' +
                    '            <th>SKU</td>\n' +
                    '            <th>到货数量</td>\n' +
                    '        </tr>\n';
                var els = $('.purchase-table').find('.sku');

                for (var i = 0, j = els.length; i < j; i++) {
                    var sku = els[i].value;
                    html += '<tr>\n' +
                        '            <td>\n' +
                        '                <input id="c-purchase_remark" class="form-control" readonly name="batch_sku[' + z + '][' + i + ']" value="' + sku + '" type="text">\n' +
                        '            </td>\n' +
                        '            <td><input id="c-purchase_remark" class="form-control arrival_num"  name="arrival_num[' + z + '][' + i + ']" type="text"></td>\n' +
                        '        </tr>\n';

                }
                z++;
                html += '    </table>\n' +
                    '</div>';
                $('#arrival_div').append(html);

                Controller.api.bindevent();
            })


            //异步获取供应商的数据
            $(document).on('change', '.supplier.selectpicker', function () {
                var id = $(this).val();
                Backend.api.ajax({
                    url: Config.moduleurl + '/purchase/contract/getSupplierData',
                    data: { id: id }
                }, function (data, ret) {
                    $('.supplier_address').val(data.address);
                });
            })

            if ($('.supplier.selectpicker').val()) {

                $('.supplier.selectpicker').change();
            }

            //切换合同 异步获取合同数据
            $(document).on('change', '.contract_id', function () {
                var id = $(this).val();
                if (id) {
                    var url = Config.moduleurl + '/purchase/purchase_order/getContractData';
                    Backend.api.ajax({
                        url: url,
                        data: { id: id }
                    }, function (data, ret) {
                        $('.contract_name').val(data.contract_name);
                        $('.delivery_address').val(data.delivery_address);
                        $('.delivery_stime').val(data.delivery_stime);
                        $('.delivery_etime').val(data.delivery_etime);
                        $('.contract_stime').val(data.contract_stime);
                        $('.contract_etime').val(data.contract_etime);
                        $('.contract_images').val(data.contract_images);
                        $('#c-contract_images').change();

                        $(".supplier").selectpicker('val', data.supplier_id);//默认选中
                        $(".supplier_address").val(data.supplier_address);
                        $(".total").val(data.total);
                        $(".freight").val(data.freight);
                        $(".deposit_amount").val(data.deposit_amount);
                        $(".final_amount").val(data.final_amount);
                        $(".settlement_method").val(data.settlement_method);
                        $('.address').val(data.delivery_address);
                        if (data.settlement_method == 3) {
                            $('.deposit_amount').removeClass('hidden');
                            $('.final_amount').removeClass('hidden');
                        }

                        $('.freight').attr("readonly", "readonly");;

                        //总计
                        var purchase_total = data.total * 1 + data.freight * 1;
                        $('.purchase_total').val(purchase_total);


                        //循环展示商品信息
                        var shtml = ' <tr><th>SKU</td><th>产品名称</td><th>供应商sku</td><th>采购数量（个）</td><th>采购单价（元）</td><th>总价（元）</td></tr>';
                        $('.purchase-table tbody').html('');
                        $('#toolbar').remove();
                        for (var i in data.item) {
                            var sku = data.item[i].sku;
                            if (!sku) {
                                sku = '';
                            }
                            shtml += '<tr><td><input id="c-purchase_remark" class="form-control sku" name="sku[]" readonly value="' + sku + '" type="text"></td>'
                            shtml += '<td><input id="c-purchase_remark" class="form-control product_name" readonly name="product_name[]" value="' + data.item[i].product_name + '" type="text"></td>'
                            shtml += '<td><input id="c-purchase_remark" class="form-control supplier_sku" readonly name="supplier_sku[]" value="' + data.item[i].supplier_sku + '" type="text"></td>'
                            shtml += '<td><input id="c-purchase_remark" class="form-control purchase_num" readonly name="purchase_num[]" value="' + data.item[i].num + '" type="text"></td>'
                            shtml += '<td><input id="c-purchase_remark" class="form-control purchase_price" readonly name="purchase_price[]" value="' + data.item[i].price + '" type="text"></td>'
                            shtml += '<td><input id="c-purchase_remark" class="form-control goods_total" readonly name="purchase_total[]" value="' + data.item[i].total + '" type="text"></td>'
                            // shtml += '<td><a href="javascript:;" class="btn btn-danger btn-del" title="删除"><i class="fa fa-trash"></i> 删除</a></td>'
                            shtml += '</tr>'
                        }
                        $('.purchase-table tbody').append(shtml);

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
                    });
                }

            })

        },

        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
        }
    };
    return Controller;
});
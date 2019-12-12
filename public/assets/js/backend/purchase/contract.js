define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'jqui','bootstrap-table-jump-to'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                extend: {
                    index_url: 'purchase/contract/index' + location.search,
                    add_url: 'purchase/contract/add',
                    edit_url: 'purchase/contract/edit',
                    // del_url: 'purchase/contract/del',
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
                                    name: 'submitAudit',
                                    text: '提交审核',
                                    title: __('提交审核'),
                                    classname: 'btn btn-xs btn-success btn-ajax',
                                    icon: 'fa fa-leaf',
                                    url: 'purchase/contract/audit',
                                    confirm: '确认提交审核吗',
                                    success: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        $(".btn-refresh").trigger("click");
                                        //如果需要阻止成功提示，则必须使用return false;
                                        //return false;
                                    },
                                    error: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        if (row.status < 1) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    },
                                },
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
                                        if (row.status < 1) {
                                            return true;
                                        } else {
                                            return false;
                                        }
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
                                        if (row.status < 1) {
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

            //审核通过
            $(document).on('click', '.btn-open', function () {
                var ids = Table.api.selectedids(table);
                Backend.api.ajax({
                    url: Config.moduleurl + '/purchase/contract/setStatus',
                    data: { ids: ids, status: 2 }
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })

            //审核拒绝
            $(document).on('click', '.btn-close', function () {
                var ids = Table.api.selectedids(table);
                Backend.api.ajax({
                    url: Config.moduleurl + '/purchase/contract/setStatus',
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
                    allnum += num * 1;
                })
                $('.allnum').val(allnum);

                //计算总金额
                var alltotal = 0;
                $('.total').each(function () {
                    var total = $(this).val();
                    alltotal += total * 1;
                })

                //运费
                var freight = $('#c-freight').val();
                alltotal += freight * 1;
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
                    allnum += num * 1;
                })
                $('.allnum').val(allnum);

                //计算总金额
                var alltotal = 0;
                $('.total').each(function () {
                    var total = $(this).val();
                    alltotal += total * 1;
                })

                //运费
                var freight = $('#c-freight').val();
                alltotal += freight * 1;
                $('.alltotal').val(alltotal);
            })


            //单价触发事件
            $(document).on('blur', '#c-freight', function () {
                var freight = $(this).val();
                //计算总金额
                var alltotal = 0;
                $('.total').each(function () {
                    var total = $(this).val();
                    alltotal += total * 1;
                })

                alltotal += freight * 1;
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
                        url: Config.moduleurl + '/purchase/contract/deleteItem',
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
                    allnum += num * 1;
                })
                $('.allnum').val(allnum);

                //计算总金额
                var alltotal = 0;
                $('.total').each(function () {
                    var total = $(this).val();
                    alltotal += total * 1;
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
                    allnum += num * 1;
                })
                $('.allnum').val(allnum);

                //计算总金额
                var alltotal = 0;
                $('.total').each(function () {
                    var total = $(this).val();
                    alltotal += total * 1;
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

                //追加数据
                $(document).on('click', '.btn-add', function () {
                    var content = $('#table-content table tbody').html();
                    $('.caigou table tbody').append(content);


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
                    var _this = $(this);
                    if (!sku) {
                        return false;
                    }
                    Backend.api.ajax({
                        url: 'ajax/getSkuList',
                        data: { sku: sku }
                    }, function (data, ret) {
                        _this.parent().parent().find('.product_name').val(data.name);
                        _this.parent().parent().find('.supplier_sku').val(data.supplier_sku);
                    }, function (data, ret) {
                        Fast.api.error(ret.msg);
                    });

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
                    if (id) {
                        Backend.api.ajax({
                            url: Config.moduleurl + '/purchase/contract/getSupplierData',
                            data: { id: id }
                        }, function (data, ret) {
                            $('#c-seller_address').val(data.address);
                            $('#c-seller_phone').val(data.telephone);
                        });
                    }

                })

                 //选中的开始时间和现在的时间比较
                 $(document).on('dp.change', '.delivery_stime', function () {
                    var time_value = $(this).val();
                    var end_time = $('.delivery_etime').val();

                    function getNow(s) {
                        return s < 10 ? '0' + s: s;
                    }

                    var myDate = new Date();

                    var year=myDate.getFullYear();        //获取当前年
                    var month=myDate.getMonth()+1;   //获取当前月
                    var date=myDate.getDate();            //获取当前日
                    var h=myDate.getHours();              //获取当前小时数(0-23)
                    var m=myDate.getMinutes()-10;          //获取当前分钟数(0-59)
                    var s=myDate.getSeconds();
                    var now=year+'-'+getNow(month)+"-"+getNow(date)+" "+getNow(h)+':'+getNow(m)+":"+getNow(s);


                    if (time_value > end_time) {
                        Layer.alert('开始时间不能大于结束时间！！');
                        $(this).val(end_time);
                        return false;
                    } 

                });

                //选中的开始时间和现在的时间比较
                $(document).on('dp.change', '.delivery_etime', function () {
                    var time_value = $('.delivery_stime').val();
                    var end_time = $(this).val();

                    function getNow(s) {
                        return s < 10 ? '0' + s: s;
                    }

                    var myDate = new Date();

                    var year=myDate.getFullYear();        //获取当前年
                    var month=myDate.getMonth()+1;   //获取当前月
                    var date=myDate.getDate();            //获取当前日
                    var h=myDate.getHours();              //获取当前小时数(0-23)
                    var m=myDate.getMinutes()-10;          //获取当前分钟数(0-59)
                    var s=myDate.getSeconds();
                    var now=year+'-'+getNow(month)+"-"+getNow(date)+" "+getNow(h)+':'+getNow(m)+":"+getNow(s);


                    if (time_value > end_time) {
                        Layer.alert('开始时间不能大于结束时间！！');
                        $(this).val(time_value);
                        return false;
                    } 

                });


                 //选中的开始时间和现在的时间比较
                 $(document).on('dp.change', '.contract_stime', function () {
                    var time_value = $(this).val();
                    var end_time = $('.contract_etime').val();

                    function getNow(s) {
                        return s < 10 ? '0' + s: s;
                    }

                    var myDate = new Date();

                    var year=myDate.getFullYear();        //获取当前年
                    var month=myDate.getMonth()+1;   //获取当前月
                    var date=myDate.getDate();            //获取当前日
                    var h=myDate.getHours();              //获取当前小时数(0-23)
                    var m=myDate.getMinutes()-10;          //获取当前分钟数(0-59)
                    var s=myDate.getSeconds();
                    var now=year+'-'+getNow(month)+"-"+getNow(date)+" "+getNow(h)+':'+getNow(m)+":"+getNow(s);


                    if (time_value > end_time) {
                        Layer.alert('开始时间不能大于结束时间！！');
                        $(this).val(end_time);
                        return false;
                    } 

                });

                $(document).on('dp.change', '.contract_etime', function () {
                    var time_value = $('.contract_stime').val();
                    var end_time = $(this).val();

                    function getNow(s) {
                        return s < 10 ? '0' + s: s;
                    }

                    var myDate = new Date();

                    var year=myDate.getFullYear();        //获取当前年
                    var month=myDate.getMonth()+1;   //获取当前月
                    var date=myDate.getDate();            //获取当前日
                    var h=myDate.getHours();              //获取当前小时数(0-23)
                    var m=myDate.getMinutes()-10;          //获取当前分钟数(0-59)
                    var s=myDate.getSeconds();
                    var now=year+'-'+getNow(month)+"-"+getNow(date)+" "+getNow(h)+':'+getNow(m)+":"+getNow(s);

                    if (time_value > end_time) {
                        Layer.alert('开始时间不能大于结束时间！！');
                        $(this).val(time_value);
                        return false;
                    } 
                });
            }
        }
    };
    return Controller;
});
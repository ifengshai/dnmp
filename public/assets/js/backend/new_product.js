define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'jqui', 'fast'], function ($, undefined, Backend, Table, Form, undefined, Fast) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                searchFormVisible: true,
                extend: {
                    index_url: 'new_product/index' + location.search,
                    add_url: 'new_product/add',
                    edit_url: 'new_product/edit',
                    // del_url: 'new_product/del',
                    multi_url: 'new_product/multi',
                    table: 'new_product',
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
                        {
                            field: '', title: __('序号'), formatter: function (value, row, index) {
                                var options = table.bootstrapTable('getOptions');
                                var pageNumber = options.pageNumber;
                                var pageSize = options.pageSize;
                                return (pageNumber - 1) * pageSize + 1 + index;
                            }, operate: false
                        },
                        { field: 'id', title: __('Id'), operate: false, visible: false },
                        { field: 'sku', title: __('Sku') },
                        { field: 'name', title: __('Name') },
                        { field: 'supplier.supplier_name', title: __('供应商名称') },
                        { field: 'supplier_sku', title: __('供应商SKU') },
                        {
                            field: 'item_status', title: __('选品状态'),
                            custom: { 1: 'success', 2: 'blue', 3: 'danger', 4: 'gray' },
                            searchList: { 1: '待选品', 2: '选品通过', 3: '选品拒绝', 4: '已取消' },
                            formatter: Table.api.formatter.status
                        },
                        { field: 'create_person', title: __('Create_person') },
                        { field: 'create_time', title: __('Create_time'), operate: 'RANGE', addclass: 'datetimerange' },

                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, buttons: [
                                {
                                    name: 'detail',
                                    text: '详情',
                                    title: __('查看详情'),
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-list',
                                    url: '/admin/new_product/detail',
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
                                    url: '/admin/new_product/edit',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        //console.log(row.item_status);
                                        if (row.item_status == 1) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                },
                                
                                {
                                    name: 'auditRefused',
                                    text: '取消',
                                    title: __('取消'),
                                    classname: 'btn btn-xs btn-danger btn-ajax',
                                    icon: 'fa fa-remove',
                                    url: '/admin/new_product/cancel',
                                    confirm: '确认取消吗',
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
                                        if (row.item_status == 1 || row.item_status == 2) {
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

            //商品审核通过
            $(document).on('click', '.btn-passAudit', function () {
                var ids = Table.api.selectedids(table);
                Layer.confirm(
                    __('确定要审核通过吗'),
                    function (index) {
                        Backend.api.ajax({
                            url: "/admin/new_product/passAudit",
                            data: { ids: ids }
                        }, function (data, ret) {
                            table.bootstrapTable('refresh');
                            Layer.close(index);
                        });
                    }
                );
            });
            //商品审核拒绝
            $(document).on('click', '.btn-auditRefused', function () {
                var ids = Table.api.selectedids(table);
                Layer.confirm(
                    __('确定要审核拒绝吗'),
                    function (index) {
                        Backend.api.ajax({
                            url: "/admin/new_product/auditRefused",
                            data: { ids: ids }
                        }, function (data, ret) {
                            table.bootstrapTable('refresh');
                            Layer.close(index);
                        });
                    }
                );
            });

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
                        Fast.api.open('purchase/purchase_order/add?new_product_ids=' + ids.join(','), '创建采购单', options);
                    }
                );
            });
        },
        add: function () {
            Controller.api.bindevent();
           

            //采集1688商品信息
            $(document).on('click', '.btn-caiji', function () {
                var link = $('#c-link').val();
                if (!link) {
                    Layer.alert('请先填写商品链接！！');
                    return false;
                }
                Layer.load();
                Backend.api.ajax({
                    url: 'new_product/ajaxCollectionGoodsDetail',
                    data: { link: link }
                }, function (data, ret) {
                    Layer.closeAll();
                    //循环展示商品信息
                    var shtml = ' <tr><th>商品名称</th><th>商品颜色</th><th>供应商SKU</th><th>单价</th><th>操作</th></tr>';
                    $('.caigou table tbody').html('');
                    for (var i in data) {
                        shtml += '<tr><td><input id="c-name" class="form-control c-name" name="row[name][]" value="' + data[i].title + '" type="text"></td>'
                        shtml += '<td><input id="c-color" class="form-control" name="row[color][]" value="' + data[i].color + '" type="text"></td>'
                        shtml += '<td><input id="c-supplier_sku" class="form-control" name="row[supplier_sku][]" value="' + data[i].cargoNumber + '" type="text"></td>'
                        shtml += '<td><input id="c-price" class="form-control" name="row[price][]" value="' + data[i].price + '" type="text"></td>'
                        shtml += '<td><a href="javascript:;" class="btn btn-danger btn-del" title="删除"><i class="fa fa-trash"></i> 删除</a></td>'
                        shtml += '<input  class="form-control" name="row[skuid][]" value="' + data[i].skuId + '" type="hidden">'
                        shtml += '</tr>'
                    }
                    $('.caigou table tbody').append(shtml);

                }, function (data, ret) {
                    //失败的回调
                    Layer.closeAll();
                    return false;
                });
            })
        },
        edit: function () {
            Controller.api.bindevent();
           
        },
        detail: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));

                //多项添加商品名称和颜色
                $(document).on('click', '.btn-add', function () {
                    $(".selectpicker").selectpicker('refresh');
                    var content = $('#table-content table tbody').html();
                    //console.log(content);
                    $('.caigou table tbody').append(content);
                    // Form.api.bindevent($("form[role=form]"));
                });
                $(document).on('click', '.btn-del', function () {
                    $(this).parent().parent().remove();
                });
                //根据商品分类的不同请求不同属性页面
                $(document).on('change', '#choose_category_id', function () {
                    var categoryId = $('#choose_category_id').val();
                    Backend.api.ajax({
                        url: 'new_product/ajaxCategoryInfo',
                        data: { categoryId: categoryId }
                    }, function (data, ret) {
                        var resultData = ret.data;
                        $('.ajax-add').remove();
                        //console.log(resultData);
                        $('#item-stock').after(resultData);
                        Form.api.bindevent($("form[role=form]"));
                        $(".selectpicker").selectpicker('refresh');

                        return false;
                    }, function (data, ret) {
                        //失败的回调
                        alert(ret.msg);
                        return false;
                    });
                });
                //采购类型和采购产地二级联动
                $(document).on('change', '#c-procurement_type', function () {
                    var arrIds = $(this).val();
                    console.log(arrIds);
                    if (arrIds == 0) {
                        Layer.alert('请选择采购类型');
                        return false;
                    }
                    //线上采购
                    if (arrIds == 1) {
                        $('#c-procurement_origin').html('');
                        var str = '<option value="O">线上采购</option>';
                        $('#c-procurement_origin').append(str);
                        $("#c-procurement_origin").selectpicker('refresh');
                    } else {
                        Backend.api.ajax({
                            url: 'new_product/ajaxGetProOrigin',
                        }, function (data, ret) {
                            var rs = ret.data;
                            var r;
                            $('#c-procurement_origin').html('');
                            var str = '';
                            for (r in rs) {
                                str += '<option value="' + r + '">' + rs[r] + '</option>';
                            }
                            $('#c-procurement_origin').append(str);
                            $("#c-procurement_origin").selectpicker('refresh');
                        }, function (data, ret) {

                        });
                    }
                });
                //模糊匹配原始sku
                $('#c-origin_skus').autocomplete({

                    source: function (request, response) {
                        var origin_sku = $('#c-origin_skus').val();
                        $.ajax({
                            type: "POST",
                            url: "new_product/ajaxGetLikeOriginSku",
                            dataType: "json",
                            cache: false,
                            async: false,
                            data: {
                                origin_sku: origin_sku
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
                //根据选择的sku找出关于sku的商品
                $(document).on('change', '#c-origin_skus', function () {
                    var categoryId = $('#choose_category_id').val();
                    var sku = $('#c-origin_skus').val();
                    if (categoryId == 0) {
                        Layer.alert('请先选择商品分类');
                        return false;
                    }
                    Backend.api.ajax({
                        url: 'new_product/ajaxItemInfo',
                        data: { categoryId: categoryId, sku: sku }
                    }, function (data, ret) {
                        //Form.api.bindevent($("form[role=form]"));
                        var resultData = ret.data;
                        // console.log(resultData.procurement_type);
                        //console.log(resultData);
                        $('.newAddition').remove();
                        //$('#c-procurement_type').eq(2).attr("selected",true);
                        $("#c-procurement_type").find("option[value=" + resultData.procurement_type + "]").prop("selected", true);
                        $("#c-procurement_origin").find("option[value=" + resultData.procurement_origin + "]").prop("selected", true);
                        $("#c-frame_texture").find("option[value=" + resultData.frame_texture + "]").prop("selected", true);
                        $("#c-shape").find("option[value=" + resultData.shape + "]").prop("selected", true);
                        $("#c-frame_type").find("option[value=" + resultData.frame_type + "]").prop("selected", true);
                        $("#c-frame_shape").find("option[value=" + resultData.frame_shape + "]").prop("selected", true);
                        $("#c-frame_gender").find("option[value=" + resultData.frame_gender + "]").prop("selected", true);
                        $("#c-frame_size").find("option[value=" + resultData.frame_size + "]").prop("selected", true);
                        $("#c-glasses_type").find("option[value=" + resultData.glasses_type + "]").prop("selected", true);
                        $("#c-frame_is_recipe").find("option[value=" + resultData.frame_is_recipe + "]").prop("selected", true);
                        $("#c-frame_piece").find("option[value=" + resultData.frame_piece + "]").prop("selected", true); $("#c-frame_temple_is_spring").find("option[value=" + resultData.frame_temple_is_spring + "]").prop("selected", true);
                        $("#c-frame_is_adjust_nose_pad").find("option[value=" + resultData.frame_is_adjust_nose_pad + "]").prop("selected", true);
                        $("#c-frame_is_advance").find("option[value=" + resultData.frame_is_advance + "]").prop("selected", true);
                        $('#c-frame_bridge').val(resultData.frame_bridge);
                        $('#c-frame_height').val(resultData.frame_height);
                        $('#c-frame_width').val(resultData.frame_width);
                        $('#c-frame_length').val(resultData.frame_length);
                        $('#c-frame_temple_length').val(resultData.frame_temple_length);
                        $('#c-weight').val(resultData.frame_weight);
                        $('#c-mirror_width').val(resultData.mirror_width);
                        $('#c-problem_desc').html(resultData.frame_remark);
                        $('.note-editable').html(resultData.frame_remark);
                        $('#item-count').val(resultData.itemCount);
                        //$(".editor").textarea
                        $(".addition").remove();
                        $(".redact").before(function () {
                            var Str = '';
                            Str += '<div class="caigou ajax-add newAddition">' +
                                '<p style="font-size: 16px;"><b>产品信息</b></p>' +
                                '<div>' +
                                '<div id="toolbar" class="toolbar">' +
                                '<a href="javascript:;" class="btn btn-success btn-add" title="增加"><i class="fa fa-plus"></i> 增加</a>' +
                                '</div>' +
                                '<table id="caigou-table">' +
                                '<tr>' +
                                '<th>商品名称</th>' +
                                '<th>商品颜色</th>' +
                                '<th>供应商SKU</th>' +
                                '<th>操作</th>' +
                                '</tr>';
                            for (var j = 0, len = resultData.itemArr.length; j < len; j++) {
                                var newItem = resultData.itemArr[j];
                                Str += '<tr>';
                                Str += '<td><input id="c-name" class="form-control" name="row[name][]" type="text" value="' + newItem.name + '" disabled="disabled"></td>';
                                Str += '<td><input id="c-color" class="form-control" name="row[color][]" type="text" value="' + newItem.frame_color + '" disabled="disabled"></td>';
                                Str += '<td><input id="c-color" class="form-control" name="row[supplier_sku][]" type="text" value="' + newItem.supplier_sku + '" disabled="disabled"></td>';
                                Str += '<td><a href="javascript:;" class="btn btn-danger btn-del" title="删除"><i class="fa fa-trash"></i>删除</a></td>';
                                Str += '</tr>';
                            }
                            Str += '</table>' +
                                '</div>' +
                                '</div>';
                            return Str;
                        });
                        $(".selectpicker").selectpicker('refresh');
                        return false;
                    }, function (data, ret) {
                        //失败的回调
                        alert(ret.msg);
                        return false;
                    });
                });
                //根据填写的商品名称找出商品是否重复
                $(document).on('blur', '.c-name', function () {
                    var name = $(this).val();
                    if (name.length > 0) {
                        Backend.api.ajax({
                            url: 'new_product/ajaxGetInfoName',
                            data: { name: name }
                        }, function (data, ret) {
                            console.log(ret.data);
                            $('.btn-success').removeClass('btn-disabled disabled');
                            return false;
                        }, function (data, ret) {
                            //失败的回调
                            $('.btn-success').addClass('btn-disabled disabled');
                            alert(ret.msg);
                            return false;
                        });
                    }
                    console.log(name);
                });
            }
        },
        frame: function () {
            Controller.api.bindevent();
            Form.api.bindevent($("form[role=form]"));
        },
        ajaxCategoryInfo: function () {
            Controller.api.bindevent();
            Form.api.bindevent($("form[role=form]"));
            Form.events.datetimepicker($("form"));
        },
    };
    return Controller;
});
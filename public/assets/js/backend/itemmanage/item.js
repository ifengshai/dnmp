define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'jqui', 'bootstrap-table-jump-to'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'itemmanage/item/index' + location.search,
                    add_url: 'itemmanage/item/add',
                    edit_url: 'itemmanage/item/edit',
                    //del_url: 'itemmanage/item/del',
                    multi_url: 'itemmanage/item/multi',
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
                        { checkbox: true },
                        {
                            field: '', title: __('序号'), formatter: function (value, row, index) {
                                var options = table.bootstrapTable('getOptions');
                                var pageNumber = options.pageNumber;
                                var pageSize = options.pageSize;

                                //return (pageNumber - 1) * pageSize + 1 + index;
                                return 1 + index;
                            }, operate: false
                        },
                        { field: 'id', title: __('Id'), operate: false },
                        { field: 'name', title: __('Name') },
                        { field: 'origin_sku', title: __('Origin_sku'), operate: 'LIKE' },
                        { field: 'sku', title: __('Sku'), operate: 'LIKE' },
                        { field: 'price', title: __('参考进价'), operate: false },
                        {
                            field: 'brand_id',
                            title: __('Brand_id'),
                            searchList: $.getJSON('itemmanage/item/ajaxGetItemBrandList'),
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'category_id', title: __('Category_id'),
                            searchList: $.getJSON('itemmanage/item/ajaxGetItemCategoryList'),
                            formatter: Table.api.formatter.status
                            //formatter: Controller.api.formatter.devicess
                        },
                        {
                            field: 'item_status', title: __('Item_status'),
                            searchList: { 1: '新建', 2: '待审核', 3: '审核通过', 4: '审核拒绝', 5: '取消' },
                            custom: { 1: 'yellow', 2: 'blue', 3: 'success', 4: 'red', 5: 'danger' },
                            formatter: Table.api.formatter.status
                        },
                        { field: 'stock', title: __('Stock') },
                        {
                            field: 'is_open', title: __('Is_open'),
                            searchList: { 1: '启用', 2: '禁用' },
                            custom: { 1: 'blue', 2: 'yellow' },
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'is_new',
                            title: __('Is_new'),
                            searchList: { 1: '是', 2: '不是' },
                            custom: { 1: 'blue', 2: 'red' },
                            formatter: Table.api.formatter.status
                        },
                        // {
                        //     field: 'is_presell',
                        //     title: __('Is_presell'),
                        //     searchList: { 1: '不是', 2: '是' },
                        //     custom: { 1: 'blue', 2: 'red' },
                        //     formatter: Table.api.formatter.status
                        // },
                        { field: 'create_person', title: __('Create_person') },
                        { field: 'create_time', title: __('Create_time'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime },
                        {
                            field: 'operate', width: "120px", title: __('操作'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'detail',
                                    text: '详情',
                                    title: __('查看详情'),
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-list',
                                    url: Config.moduleurl + '/itemmanage/item/detail',
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
                                    url: Config.moduleurl + '/itemmanage/item/edit',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        // return true;
                                        //返回true时按钮显示,返回false隐藏
                                        //console.log(row.item_status);
                                        if (row.item_status == 1 || row.item_status == 4) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'submitAudit',
                                    text: '提交审核',
                                    title: __('提交审核'),
                                    classname: 'btn btn-xs btn-success btn-ajax',
                                    icon: 'fa fa-pencil',
                                    url: Config.moduleurl + '/itemmanage/item/audit',
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
                                        if (row.item_status == 1) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    },
                                },
                                {
                                    name: 'passAudit',
                                    text: '审核通过',
                                    title: __('审核通过'),
                                    classname: 'btn btn-xs btn-success btn-ajax',
                                    icon: 'fa fa-pencil',
                                    url: Config.moduleurl + '/itemmanage/item/passAudit',
                                    confirm: '确认审核通过吗',
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
                                        if (row.item_status == 2) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'auditRefused',
                                    text: '审核拒绝',
                                    title: __('审核拒绝'),
                                    classname: 'btn btn-xs btn-danger btn-ajax',
                                    icon: 'fa fa-pencil',
                                    url: Config.moduleurl + '/itemmanage/item/auditRefused',
                                    confirm: '确认审核拒绝吗',
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
                                        if (row.item_status == 2) {
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
                                    icon: 'fa fa-pencil',
                                    url: Config.moduleurl + '/itemmanage/item/cancel',
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
                                        if (row.item_status == 1) {
                                            return true;
                                        }
                                        return false;
                                    }
                                }
                            ]
                        },
                        //{field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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
                            url: "itemmanage/item/morePassAudit",
                            data: { ids: ids }
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
                            url: "itemmanage/item/moreAuditRefused",
                            data: { ids: ids }
                        }, function (data, ret) {
                            table.bootstrapTable('refresh');
                            Layer.close(index);
                        });
                    }
                );
            });
            //商品启用
            $(document).on('click', '.btn-start', function () {
                var ids = Table.api.selectedids(table);
                Layer.confirm(
                    __('确定要启用商品吗'),
                    function (index) {
                        Backend.api.ajax({
                            url: "itemmanage/item/startItem",
                            data: { ids: ids }
                        }, function (data, ret) {
                            table.bootstrapTable('refresh');
                            Layer.close(index);
                        });
                    }
                );
            });
            //商品禁用
            $(document).on('click', '.btn-forbidden', function () {
                var ids = Table.api.selectedids(table);
                Layer.confirm(
                    __('确定要禁用商品吗'),
                    function (index) {
                        Backend.api.ajax({
                            url: "itemmanage/item/forbiddenItem",
                            data: { ids: ids }
                        }, function (data, ret) {
                            table.bootstrapTable('refresh');
                            Layer.close(index);
                        });
                    }
                );
            });
            //商品移入回收站
            $(document).on('click', '.btn-MoveRecycle', function () {
                var ids = Table.api.selectedids(table);
                Layer.confirm(
                    __('确定要移入回收站吗'),
                    function (index) {
                        Backend.api.ajax({
                            url: "itemmanage/item/moveRecycle",
                            data: { ids: ids }
                        }, function (data, ret) {
                            table.bootstrapTable('refresh');
                            Layer.close(index);
                        });
                    }
                );
            });
			//批量导出xls 
            $('.btn-batch-export-xls').click(function () {
                var ids = Table.api.selectedids(table);
                if (ids.length > 0) {
                    window.open(Config.moduleurl + '/itemmanage/item/batch_export_xls?ids=' + ids, '_blank');
                } else {
                    var options = table.bootstrapTable('getOptions');
                    var search = options.queryParams({});
                    var filter = search.filter;
                    var op = search.op;
                    window.open(Config.moduleurl + '/itemmanage/item/batch_export_xls?filter=' + filter + '&op=' + op, '_blank');
                }
                
            });
        },
        add: function () {
            Controller.api.bindevent();
            $(document).on('click', '.btn-status', function () {
                $('#status').val(2);
            });
        },
        edit: function () {
            Controller.api.bindevent();
            $(document).on('click', '.btn-status', function () {
                $('#status').val(2);
            });
            $('.btn-add').hide();
            $('.btn-del').hide();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
                //通过审核
                //多项添加商品名称和颜色
                $(document).on('click', '.btn-add', function () {
                    var item_type = $('#item_type').val();
                    $(".selectpicker").selectpicker('refresh');
                    if(3 == item_type){
                        var content = $('#table-content2 table tbody').html();
                        $('.caigou table tbody').append(content);
                    }else{
                        var content = $('#table-content table tbody').html();
                        $('.caigou table tbody').append(content);
                    }
                });
                $(document).on('click', '.btn-del', function () {
                    $(this).parent().parent().remove();
                });
                //根据商品分类的不同请求不同属性页面
                $(document).on('change', '#choose_category_id', function () {
                    var categoryId = $('#choose_category_id').val();
                    Backend.api.ajax({
                        url: 'itemmanage/item/ajaxCategoryInfo',
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
                            url: 'itemmanage/item/ajaxGetProOrigin',
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
                //根据随机数、采购类型、采购产地异步判断origin_sku是否存在
                $(document).on('change', '#c-frame_texture', function () {
                    var frame_texture = $(this).val();
                    var procurement_origin = $('#c-procurement_origin').val();
                    var origin_sku = $('#c-origin_sku').val();
                    Backend.api.ajax({
                        url: 'itemmanage/item/checkOriginIsExist',
                        data: { frame_texture: frame_texture, procurement_origin: procurement_origin, origin_sku: origin_sku }
                    }, function (data, ret) {
                        $('.btn-success').removeClass('btn-disabled disabled');
                        return false;
                    }, function (data, ret) {
                        //失败的回调
                        $('.btn-success').addClass('btn-disabled disabled');
                        alert(ret.msg);
                        return false;
                    });
                });
                //模糊匹配原始sku
                $('#c-origin_skus').autocomplete({
                    source: function (request, response) {
                        var origin_sku = $('#c-origin_skus').val();
                        $.ajax({
                            type: "POST",
                            url: "itemmanage/item/ajaxGetLikeOriginSku",
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
                    if (sku == '') {
                        Layer.alert('请输入您的商品SKU');
                        return false;
                    }
                    Backend.api.ajax({
                        url: 'itemmanage/item/ajaxItemInfo',
                        data: { categoryId: categoryId, sku: sku }
                    }, function (data, ret) {
                        var resultData = ret.data;
                        if (resultData == -1) {
                            Layer.alert('输入的商品SKU有误，请重新输入');
                        }
                        if ((resultData != false) && (resultData.type == 1)) {
                            $('.newAddition').remove();
                            if (resultData.procurement_type) {
                                $("#c-procurement_type").find("option[value=" + resultData.procurement_type + "]").prop("selected", true);
                            } else {
                                $("#c-procurement_type").val("");
                            }
                            if (resultData.procurement_origin) {
                                $("#c-procurement_origin").find("option[value=" + resultData.procurement_origin + "]").prop("selected", true);
                            } else {
                                $("#c-procurement_origin").val();
                            }
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
                            if (resultData.origin_sku) {
                                $(".addition").remove();
                                $(".redact").after(function () {
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
                                        '<th>商品进价</th>'+
                                        '<th>操作</th>' +
                                        '</tr>';
                                    for (var j = 0, len = resultData.itemArr.length; j < len; j++) {
                                        var newItem = resultData.itemArr[j];
                                        Str += '<tr>';
                                        Str += '<td><input id="c-name" class="form-control" name="row[name][]" type="text" value="' + newItem.name + '" disabled="disabled"></td>';
                                        Str += '<td><div class="col-xs-12 col-sm-12">';
                                        Str += '<select  id="c-color" data-rule="required" class="form-control " name="row[color][]" disabled="disabled">';
                                        Str += '<option value="' + newItem.frame_color + '">' + newItem.frame_color_value + '</option>';
                                        Str += '</select></td>';
                                        Str += '<td><input id="c-name" class="form-control" name="row[price][] type="text" value="'+ newItem.price +'" disabled="disabled"></td>';
                                        Str += '<td><a href="javascript:;" class="btn btn-danger btn-del" title="删除"><i class="fa fa-trash"></i>删除</a></td>';
                                        Str += '</tr>';
                                    }
                                    Str += '</table>' +
                                        '</div>' +
                                        '</div>';
                                    return Str;
                                });
                            }
                            $(".selectpicker").selectpicker('refresh');
                        }else if((resultData != false) && (resultData.type >= 3)){
                            console.log(resultData);
                            //console.log(resultData.accessory_texture);
                            $("#c-frame_texture").find("option[value="+resultData.accessory_texture+"]").prop("selected", true);
                            $('#item-count').val(resultData.itemCount);
                            if (resultData.origin_sku) {
                                $(".addition").remove();
                                $(".redact").after(function () {
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
                                        '<th>商品进价</th>'+
                                        '<th>操作</th>' +
                                        '</tr>';
                                    for (var j = 0, len = resultData.itemArr.length; j < len; j++) {
                                        var newItem = resultData.itemArr[j];
                                        Str += '<tr>';
                                        Str += '<td><input id="c-name" class="form-control" name="row[name][]" type="text" value="' + newItem.name + '" disabled="disabled"></td>';
                                        Str += '<td><div class="col-xs-12 col-sm-12">';
                                        Str += '<select  id="c-color" data-rule="required" class="form-control " name="row[color][]" disabled="disabled">';
                                        Str += '<option value="' + newItem.accessory_color + '">' + newItem.accessory_color + '</option>';
                                        Str += '</select></td>';
                                        Str += '<td><input id="c-name" class="form-control" name="row[price][] type="text" value="'+ newItem.price +'" disabled="disabled"></td>';
                                        Str += '<td><a href="javascript:;" class="btn btn-danger btn-del" title="删除"><i class="fa fa-trash"></i>删除</a></td>';
                                        Str += '</tr>';
                                    }
                                    Str += '</table>' +
                                        '</div>' +
                                        '</div>';
                                    return Str;
                                });
                            }
                            $(".selectpicker").selectpicker('refresh');                            
                        }else{
                            Layer.alert('旧商品SKU信息暂时没有同步...请耐心等待');
                        }
                        return false;
                    }, function (data, ret) {
                        //失败的回调
                        Layer.alert(ret.msg);
                        return false;
                    });
                });
                //根据填写的商品名称找出商品是否重复
                // $(document).on('blur', '.c-name', function () {
                //     var name = $(this).val();
                //     if (name.length > 0) {
                //         Backend.api.ajax({
                //             url: 'itemmanage/item/ajaxGetInfoName',
                //             data: { name: name }
                //         }, function (data, ret) {
                //             console.log(ret.data);
                //             $('.btn-success').removeClass('btn-disabled disabled');
                //             return false;
                //         }, function (data, ret) {
                //             //失败的回调
                //             $('.btn-success').addClass('btn-disabled disabled');
                //             alert(ret.msg);
                //             return false;
                //         });
                //     }
                // });
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
        detail: function () {
            Form.api.bindevent($("form[role=form]"));
        },
        images: function () {
            Form.api.bindevent($("form[role=form]"));
            $(document).on('click', '.btn-status', function () {
                $('#status').val(2);
            });
        },
        recycle: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                extend: {
                    index_url: 'itemmanage/item/recycle' + location.search,
                    add_url: 'itemmanage/item/add',
                    //edit_url: 'itemmanage/item/edit',
                    // del_url: 'itemmanage/item/del',
                    multi_url: 'itemmanage/item/multi',
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
                        { checkbox: true },
                        {
                            field: '', title: __('序号'), formatter: function (value, row, index) {
                                var options = table.bootstrapTable('getOptions');
                                var pageNumber = options.pageNumber;
                                var pageSize = options.pageSize;

                                //return (pageNumber - 1) * pageSize + 1 + index;
                                return 1 + index;
                            }, operate: false
                        },
                        { field: 'id', title: __('Id'), operate: false },
                        { field: 'name', title: __('Name') },
                        { field: 'origin_sku', title: __('Origin_sku'), operate: 'LIKE' },
                        { field: 'sku', title: __('Sku'), operate: 'LIKE' },
                        {
                            field: 'brand_id',
                            title: __('Brand_id'),
                            searchList: $.getJSON('itemmanage/item/ajaxGetItemBrandList'),
                            formatter: Table.api.formatter.status,
                            operate: false
                        },
                        {
                            field: 'category_id', title: __('Category_id'),
                            searchList: $.getJSON('itemmanage/item/ajaxGetItemCategoryList'),
                            formatter: Table.api.formatter.status,
                            operate: false
                        },
                        {
                            field: 'item_status', title: __('Item_status'),
                            searchList: { 1: '保存', 2: '提交审核', 3: '审核通过', 4: '审核拒绝', 5: '取消' },
                            custom: { 1: 'yellow', 2: 'blue', 3: 'success', 4: 'red', 5: 'danger' },
                            formatter: Table.api.formatter.status,
                            operate: false
                        },
                        { field: 'stock', title: __('Stock'), operate: false },
                        {
                            field: 'is_open', title: __('Is_open'),
                            searchList: { 1: '启用', 2: '禁用', 3: '回收站' },
                            custom: { 1: 'blue', 2: 'yellow', 3: 'red' },
                            formatter: Table.api.formatter.status,
                            operate: false
                        },
                        {
                            field: 'is_new',
                            title: __('Is_new'),
                            searchList: { 1: '是', 2: '不是' },
                            custom: { 1: 'blue', 2: 'red' },
                            formatter: Table.api.formatter.status,
                            operate: false
                        },
                        // {
                        //     field: 'is_presell',
                        //     title: __('Is_presell'),
                        //     searchList: { 1: '不是', 2: '是' },
                        //     custom: { 1: 'blue', 2: 'red' },
                        //     formatter: Table.api.formatter.status,
                        //     operate: false
                        // },
                        { field: 'create_person', title: __('Create_person'), operate: false },
                        { field: 'create_time', title: __('Create_time'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime },
                        {
                            field: 'operate', width: "120px", title: __('操作'), table: table, formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'detail',
                                    text: '详情',
                                    title: __('查看详情'),
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-list',
                                    url: Config.moduleurl + '/itemmanage/item/detail',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                },
                                {
                                    name: 'passAudit',
                                    text: '还原',
                                    title: __('还原'),
                                    classname: 'btn btn-xs btn-success btn-ajax',
                                    icon: 'fa fa-pencil',
                                    url: Config.moduleurl + '/itemmanage/item/oneRestore',
                                    confirm: '确认还原吗',
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
                                        return true;
                                    }
                                }
                            ]
                        },
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
                            url: "itemmanage/item/morePassAudit",
                            data: { ids: ids }
                        }, function (data, ret) {
                            table.bootstrapTable('refresh');
                            Layer.close(index);
                        });
                    }
                );
            });
            //商品还原
            $(document).on('click', '.btn-restore', function () {
                var ids = Table.api.selectedids(table);
                Layer.confirm(
                    __('确定要还原吗'),
                    function (index) {
                        Backend.api.ajax({
                            url: "itemmanage/item/moreRestore",
                            data: { ids: ids }
                        }, function (data, ret) {
                            table.bootstrapTable('refresh');
                            Layer.close(index);
                        });
                    }
                );
            });
            //商品移入回收站
            $(document).on('click', '.btn-MoveRecycle', function () {
                var ids = Table.api.selectedids(table);
                Layer.confirm(
                    __('确定要移入回收站吗'),
                    function (index) {
                        Backend.api.ajax({
                            url: "itemmanage/item/moveRecycle",
                            data: { ids: ids }
                        }, function (data, ret) {
                            table.bootstrapTable('refresh');
                            Layer.close(index);
                        });
                    }
                );
            });
        },
        goods_stock_list: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'itemmanage/item/goods_stock_list' + location.search,
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
                        { checkbox: true },
                        {
                            field: '', title: __('序号'), formatter: function (value, row, index) {
                                var options = table.bootstrapTable('getOptions');
                                var pageNumber = options.pageNumber;
                                var pageSize = options.pageSize;
                                return (pageNumber - 1) * pageSize + 1 + index;
                            }, operate: false
                        },
                        { field: 'id', title: __('Id'), operate: false },
                        { field: 'sku', title: __('Sku'), operate: 'like' },
                        // { field: 'name', title: __('Name') },

                        { field: 'stock', title: __('总库存'), operate: false },
                        
                        { field: 'available_stock', title: __('可用库存'), operate: false },
                        { field: 'zeelool_stock', title: __('虚拟仓库存Zeelool'), operate: false },
                        { field: 'voogueme_stock', title: __('虚拟仓库存Voogueme'), operate: false },
                        { field: 'nihao_stock', title: __('虚拟仓库存Nihao'), operate: false },
                        { field: 'meeloog_stock', title: __('虚拟仓库存Meeloog'), operate: false },
                        { field: 'wesee_stock', title: __('虚拟仓库存Wesee'), operate: false },
                        { field: 'amazon_stock', title: __('虚拟仓库存Amazon'), operate: false },
                        { field: 'distribution_occupy_stock', title: __('配货占用库存'), operate: false },
                        {
                            field: '', title: __('仓库实时库存'), operate: false, formatter: function (value, row) {
                                return row.stock - row.distribution_occupy_stock;
                            }
                        },
                        { field: 'occupy_stock', title: __('订单占用库存'), operate: false },
                        { field: 'sample_num', title: __('留样库存'), operate: false },

                        { field: 'on_way_stock', title: __('在途库存'), operate: false },
                        { field: 'is_open', title: __('SKU启用状态'), searchList: { 1: '启用', 2: '禁用', 3: '回收站' }, formatter: Table.api.formatter.status },
                        {
                            field: 'operate', title: __('操作'), table: table, formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'detail',
                                    text: '详情',
                                    title: __('查看详情'),
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-list',
                                    url: Config.moduleurl + '/itemmanage/item/goods_stock_detail',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏+
                                        return true;
                                    }
                                }
                            ]
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        presell: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'itemmanage/item/presell' + location.search,
                    add_url: 'itemmanage/item/add_presell',
                    edit_url: 'itemmanage/item/edit_presell',
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
                        { checkbox: true },
                        {
                            field: '', title: __('序号'), formatter: function (value, row, index) {
                                var options = table.bootstrapTable('getOptions');
                                var pageNumber = options.pageNumber;
                                var pageSize = options.pageSize;
                                return (pageNumber - 1) * pageSize + 1 + index;
                            }, operate: false
                        },
                        { field: 'id', title: __('Id'), operate: false },
                        { field: 'sku', title: __('Sku'), operate: 'like' },
                        { field: 'name', title: __('Name') },
                        { field: 'available_stock', title: __('可用库存'), operate: false },
                        { field: 'occupy_stock', title: __('占用库存'), operate: false },
                        { field: 'presell_num', title: __('预售数量'), operate: false },
                        { field: 'presell_residue_num', title: __('预售剩余数量'), operate: false },
                        { field: 'presell_status', title: __('预售状态'), searchList: { 0: '未开始', 1: '预售中', 2: '已结束' }, formatter: Table.api.formatter.status },
                        { field: 'presell_create_time', title: __('预售开始时间'), operate: false },
                        { field: 'presell_end_time', title: __('预售结束时间'), operate: false },
                        { field: 'is_open', title: __('SKU启用状态'), searchList: { 1: '启用', 2: '禁用', 3: '回收站' }, formatter: Table.api.formatter.status },
                        {
                            field: 'operate', title: __('操作'), table: table, formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'detail',
                                    text: '详情',
                                    title: __('查看详情'),
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-list',
                                    url: Config.moduleurl + '/itemmanage/item/goods_stock_detail',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                },
                                {
                                    name: 'openStart',
                                    text: '开启预售',
                                    title: __('开启预售'),
                                    classname: 'btn btn-xs btn-danger btn-ajax',
                                    url: Config.moduleurl + '/itemmanage/item/openStart',
                                    confirm: '确定要开启预售吗',
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
                                        if (row.presell_status != 1) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    },
                                },
                                {
                                    name: 'openEnd',
                                    text: '结束预售',
                                    title: __('结束预售'),
                                    classname: 'btn btn-xs btn-danger btn-ajax',
                                    url: Config.moduleurl + '/itemmanage/item/openEnd',
                                    confirm: '确定要结束预售吗',
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
                                        if (row.presell_status != 2) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    },
                                },
                                {
                                    name: 'edit_presell',
                                    text: '编辑预售',
                                    title: __('编辑预售'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-pencil',
                                    url: Config.moduleurl + '/itemmanage/item/edit_presell',
                                    //extend: 'data-area = \'["50%","50%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                            return true;
                                    }
                                },
                                {
                                    name: 'history',
                                    text: '历史记录',
                                    title: __('历史记录'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-pencil',
                                    url: Config.moduleurl + '/itemmanage/item/presell_history',
                                    //extend: 'data-area = \'["50%","50%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                            return true;
                                    }
                                },
                            ]
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add_presell: function () {
            Controller.api.bindevent();
            Form.api.bindevent($("form[role=form]"));
            $('#c-sku').autocomplete({
                source: function (request, response) {
                    var origin_sku = $('#c-sku').val();
                    $.ajax({
                        type: "POST",
                        url: "itemmanage/item/ajaxGetLikeOriginSku",
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
            //检查填写的平台sku数据库有没有
            $(document).on('change', '#c-sku', function () {
                var platform_sku = $(this).val();
                Backend.api.ajax({
                    url: 'itemmanage/item/check_sku_exists',
                    data: { origin_sku: platform_sku }
                }, function (data, ret) {
                    console.log(ret.data);
                    $('.btn-success').removeClass('btn-disabled disabled');
                    return false;
                }, function (data, ret) {
                    //失败的回调
                    $('.btn-success').addClass('btn-disabled disabled');
                    Layer.alert(ret.msg);
                    return false;
                });
            });
            //选中的开始时间和现在的时间比较
            $(document).on('dp.change', '#c-presell_start_time', function () {
                var time_value = $(this).val();
                //console.log(time_value);
                function getNow(s) {
                    return s < 10 ? '0' + s : s;
                }

                var myDate = new Date();

                var year = myDate.getFullYear();        //获取当前年
                var month = myDate.getMonth() + 1;   //获取当前月
                var date = myDate.getDate();            //获取当前日
                var h = myDate.getHours();              //获取当前小时数(0-23)
                var m = myDate.getMinutes() - 10;          //获取当前分钟数(0-59)
                var s = myDate.getSeconds();
                var now = year + '-' + getNow(month) + "-" + getNow(date) + " " + getNow(h) + ':' + getNow(m) + ":" + getNow(s);
                if (time_value > now) {
                    //console.log(1111);
                } else {
                    Layer.alert('预售开始时间小于当前时间,如果添加则默认开始预售');
                    //console.log(2222);
                }
                //console.log(now);
            });
            ////选中的结束时间和现在的时间比较
            $(document).on('dp.change', '#c-presell_end_time', function () {
                var time_start_value = $("#c-presell_start_time").val();
                var time_end_value = $(this).val();
                console.log(time_end_value);
                function getNow(s) {
                    return s < 10 ? '0' + s : s;
                }
                var myDate = new Date();
                var year = myDate.getFullYear();        //获取当前年
                var month = myDate.getMonth() + 1;   //获取当前月
                var date = myDate.getDate();            //获取当前日
                var h = myDate.getHours();              //获取当前小时数(0-23)
                var m = myDate.getMinutes() - 10;          //获取当前分钟数(0-59)
                var s = myDate.getSeconds();
                var now = year + '-' + getNow(month) + "-" + getNow(date) + " " + getNow(h) + ':' + getNow(m) + ":" + getNow(s);
                if (time_end_value < time_start_value) {
                    $('.btn-success').addClass('btn-disabled disabled');
                    Layer.alert('预售开始时间不能大于预售结束时间,请重新选择');
                    return false;
                }
                if (time_end_value > now) {
                    $('.btn-success').removeClass('btn-disabled disabled');
                } else {
                    $('.btn-success').addClass('btn-disabled disabled');
                    Layer.alert('当前时间大于预售结束时间无法添加,请重新选择');
                    return false;
                }
            });
        },
    edit_presell: function(){
        Controller.api.bindevent();
        Form.api.bindevent($("form[role=form]"));
    }  
    };
    return Controller;
});
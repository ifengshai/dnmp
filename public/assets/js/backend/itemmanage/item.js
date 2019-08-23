define(['jquery', 'bootstrap', 'backend', 'table', 'form','jqui'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                searchFormVisible: true,
                extend: {
                    index_url: 'itemmanage/item/index' + location.search,
                    add_url: 'itemmanage/item/add',
                    edit_url: 'itemmanage/item/edit',
                    del_url: 'itemmanage/item/del',
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
                        {checkbox: true},
                        {field: 'id', title: __('Id'),operate:false},
                        {field: 'name', title: __('Name')},
                        {field:'origin_sku',title:__('Origin_sku')},
                        {field:'sku',title:__('Sku')},
                        {
                            field:'brand_id',
                            title:__('Brand_id'),
                            searchList:$.getJSON('itemmanage/item/ajaxGetItemBrandList'),
                            formatter:Table.api.formatter.status
                        },
                        {field: 'category_id', title: __('Category_id'),
                            searchList:$.getJSON('itemmanage/item/ajaxGetItemCategoryList'),
                            formatter: Table.api.formatter.status
                            //formatter: Controller.api.formatter.devicess
                        },
                        {field: 'item_status', title: __('Item_status'),
                            searchList: { 1: '保存', 2 :'提交审核', 3: '审核通过', 4: '审核拒绝', 5: '取消' },
                            custom: {  1: 'yellow', 2: 'blue',3: 'success',4: 'red',5: 'danger' },
                            formatter: Table.api.formatter.status
                        },
                        {field: 'stock', title: __('Stock')},
                        {field:'is_open',title:__('Is_open'),
                            searchList:{1:'启用',2:'禁用',3:'回收站'},
                            custom:{1:'blue',2:'yellow',3:'red'},
                            formatter:Table.api.formatter.status
                        },
                        {
                            field:'is_new',
                            title:__('Is_new'),
                            searchList:{1:'是',2:'不是'},
                            custom:{1:'blue',2:'red'},
                            formatter:Table.api.formatter.status
                        },
                        {
                            field:'is_presell',
                            title:__('Is_presell'),
                            searchList:{1:'不是',2:'是'},
                            custom:{1:'blue',2:'red'},
                            formatter:Table.api.formatter.status
                        },
                        {field: 'create_person', title: __('Create_person')},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', width: "120px", title: __('操作'), table: table,formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'detail',
                                    text: '详情',
                                    title: __('查看详情'),
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-list',
                                    url: '/admin/itemmanage/item/detail',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
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
                                    url: '/admin/itemmanage/item/edit',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        //console.log(row.item_status);
                                        if(row.item_status ==1){
                                            return true;
                                        }else{
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
                                    url: '/admin/itemmanage/item/audit',
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
                                        if(row.item_status == 1){
                                            return true;
                                        }else{
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
                                    url: '/admin/itemmanage/item/passAudit',
                                    confirm:'确认审核通过吗',
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
                                        if(row.item_status == 2){
                                            return true;
                                        }else{
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
                                    url: '/admin/itemmanage/item/auditRefused',
                                    confirm:'确认审核拒绝吗',
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
                                        if(row.item_status == 2){
                                            return true;
                                        }else{
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
                                    url: '/admin/itemmanage/item/cancel',
                                    confirm:'确认取消吗',
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
                            url: "/admin/itemmanage/item/morePassAudit",
                            data: {ids:ids}
                        },function(data,ret){
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
                            url: "/admin/itemmanage/item/moreAuditRefused",
                            data: {ids:ids}
                        },function(data,ret){
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
                            url: "/admin/itemmanage/item/startItem",
                            data: {ids:ids}
                        },function(data,ret){
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
                           url:"/admin/itemmanage/item/forbiddenItem",
                           data:{ids:ids}
                        },function (data,ret) {
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
                            url:"/admin/itemmanage/item/moveRecycle",
                            data:{ids:ids}
                        },function (data,ret) {
                            table.bootstrapTable('refresh');
                            Layer.close(index);
                        });
                    }
                );
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
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
                //通过审核
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
                $(document).on('change','#choose_category_id',function(){
                    var categoryId = $('#choose_category_id').val();
                    Backend.api.ajax({
                        url:'itemmanage/item/ajaxCategoryInfo',
                        data:{categoryId:categoryId}
                    }, function(data, ret){
                        var resultData = ret.data;
                        $('.ajax-add').remove();
                        //console.log(resultData);
                        $('#item-stock').after(resultData);
                        Form.api.bindevent($("form[role=form]"));
                        $(".selectpicker").selectpicker('refresh');

                        return false;
                    }, function(data, ret){
                        //失败的回调
                        alert(ret.msg);
                        return false;
                    });
                });
                //采购类型和采购产地二级联动
                $(document).on('change','#c-procurement_type',function(){
                    var arrIds = $(this).val();
                    console.log(arrIds);
                    if(arrIds == 0){
                        Layer.alert('请选择采购类型');
                        return false;
                    }
                    //线上采购
                    if(arrIds == 1){
                        $('#c-procurement_origin').html('');
                        var str = '<option value="O">线上采购</option>';
                        $('#c-procurement_origin').append(str);
                        $("#c-procurement_origin").selectpicker('refresh');
                    }else{
                        Backend.api.ajax({
                            url:'itemmanage/item/ajaxGetProOrigin',
                        },function(data,ret){
                            var rs = ret.data;
                            var r;
                            $('#c-procurement_origin').html('');
                            var str = '';
                            for(r in rs){
                                str +='<option value="'+r+'">' + rs[r]+'</option>';
                            }
                            $('#c-procurement_origin').append(str);
                            $("#c-procurement_origin").selectpicker('refresh');
                        },function(data,ret){

                        });
                    }
                });
                //模糊匹配原始sku
                $('#c-origin_skus').autocomplete({
                    source:function(request,response){
                        var origin_sku = $('#origin_sku').val();
                        $.ajax({
                            type:"POST",
                            url:"itemmanage/item/ajaxGetLikeOriginSku",
                            dataType : "json",
                            cache : false,
                            async : false,
                            data : {
                                origin_sku:origin_sku
                            },
                            success : function(json) {
                                var data = json.data;
                                response($.map(data,function(item){
                                    return {
                                        label:item,//下拉框显示值
                                        value:item,//选中后，填充到input框的值
                                        //id:item.bankCodeInfo//选中后，填充到id里面的值
                                    };
                                }));
                            }
                        });
                    },
                    delay: 10,//延迟100ms便于输入
                    select : function(event, ui) {
                        $("#bankUnionNo").val(ui.item.id);//取出在return里面放入到item中的属性
                    },
                    scroll:true,
                    pagingMore:true,
                    max:5000
                });
                //根据选择的sku找出关于sku的商品
                $(document).on('change','#c-origin_skus',function(){
                    var categoryId = $('#choose_category_id').val();
                    var sku        = $('#c-origin_skus').val();
                    if(categoryId == 0){
                        Layer.alert('请先选择商品分类');
                        return false;
                    }
                    Backend.api.ajax({
                        url:'itemmanage/item/ajaxItemInfo',
                        data:{categoryId:categoryId,sku:sku}
                    }, function(data, ret){
                        //Form.api.bindevent($("form[role=form]"));
                        var resultData = ret.data;
                        // console.log(resultData.procurement_type);
                         //console.log(resultData);
                         $('.newAddition').remove();
                        //$('#c-procurement_type').eq(2).attr("selected",true);
                        $("#c-procurement_type").find("option[value="+resultData.procurement_type+"]").prop("selected",true);
                        $("#c-procurement_origin").find("option[value="+resultData.procurement_origin+"]").prop("selected",true);
                        $("#c-frame_texture").find("option[value="+resultData.frame_texture+"]").prop("selected",true);
                        $("#c-shape").find("option[value="+resultData.shape+"]").prop("selected",true);
                        $("#c-frame_type").find("option[value="+resultData.frame_type+"]").prop("selected",true);
                        $("#c-frame_shape").find("option[value="+resultData.frame_shape+"]").prop("selected",true);
                        $("#c-frame_gender").find("option[value="+resultData.frame_gender+"]").prop("selected",true);
                        $("#c-frame_size").find("option[value="+resultData.frame_size+"]").prop("selected",true);
                        $("#c-glasses_type").find("option[value="+resultData.glasses_type+"]").prop("selected",true);
                        $("#c-frame_is_recipe").find("option[value="+resultData.frame_is_recipe+"]").prop("selected",true);
                        $("#c-frame_piece").find("option[value="+resultData.frame_piece+"]").prop("selected",true); $("#c-frame_temple_is_spring").find("option[value="+resultData.frame_temple_is_spring+"]").prop("selected",true);
                        $("#c-frame_is_adjust_nose_pad").find("option[value="+resultData.frame_is_adjust_nose_pad+"]").prop("selected",true);
                        $("#c-frame_is_advance").find("option[value="+resultData.frame_is_advance+"]").prop("selected",true);
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
                        $(".redact").after(function(){
                            var Str = '';
                            Str+=  '<div class="caigou ajax-add newAddition">'+
                                '<p style="font-size: 16px;"><b>产品信息</b></p>'+
                                '<div>'+
                                '<div id="toolbar" class="toolbar">'+
                                '<a href="javascript:;" class="btn btn-success btn-add" title="增加"><i class="fa fa-plus"></i> 增加</a>'+
                                '</div>'+
                                '<table id="caigou-table">'+
                                '<tr>'+
                                '<th>商品名称</th>'+
                                '<th>商品颜色</th>'+
                                '<th>操作</th>'+
                                '</tr>';
                            for(var j = 0,len = resultData.itemArr.length; j <len; j++) {
                                var newItem = resultData.itemArr[j];
                                Str +='<tr>';
                                Str +='<td><input id="c-name" class="form-control" name="row[name][]" type="text" value="'+newItem.name+'" disabled="disabled"></td>';
                                Str +='<td><div class="col-xs-12 col-sm-12">';
                                Str +='<select  id="c-color" data-rule="required" class="form-control " name="row[color][]" disabled="disabled">';
                                Str +='<option value="'+newItem.frame_color+'">'+newItem.frame_color_value+'</option>';
                                Str +='</select></td>';
                                Str +='<td><a href="javascript:;" class="btn btn-danger btn-del" title="删除"><i class="fa fa-trash"></i>删除</a></td>';
                                Str += '</tr>';
                            }
                            Str+='</table>'+
                                '</div>'+
                                '</div>';
                            return Str;
                        });
                        $(".selectpicker").selectpicker('refresh');
                        return false;
                    }, function(data, ret){
                        //失败的回调
                        alert(ret.msg);
                        return false;
                    });
                });
                //根据填写的商品名称找出商品是否重复
                $(document).on('blur','.c-name',function(){
                   var name = $(this).val();
                   if(name.length>0){
                       Backend.api.ajax({
                           url:'itemmanage/item/ajaxGetInfoName',
                           data:{name:name}
                       }, function(data, ret){
                           console.log(ret.data);
                           $('.btn-success').removeClass('btn-disabled disabled');
                           return false;
                       }, function(data, ret){
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
        frame:function () {
            Controller.api.bindevent();
            Form.api.bindevent($("form[role=form]"));
        },
        ajaxCategoryInfo:function () {
            Controller.api.bindevent();
            Form.api.bindevent($("form[role=form]"));
            Form.events.datetimepicker($("form"));
        },
        detail:function () {
            Form.api.bindevent($("form[role=form]"));
        },
        images:function () {
            Form.api.bindevent($("form[role=form]"));
            $(document).on('click', '.btn-status', function () {
                $('#status').val(2);
            });
        },
        recycle: function () {
            // 初始化表格参数配置
            Table.api.init({
                searchFormVisible: true,
                extend: {
                    index_url: 'itemmanage/item/recycle' + location.search,
                    add_url: 'itemmanage/item/add',
                    //edit_url: 'itemmanage/item/edit',
                    del_url: 'itemmanage/item/del',
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
                        {checkbox: true},
                        {field: 'id', title: __('Id'),operate:false},
                        {field: 'name', title: __('Name')},
                        {field:'origin_sku',title:__('Origin_sku'),operate:false},
                        {field:'sku',title:__('Sku')},
                        {
                            field:'brand_id',
                            title:__('Brand_id'),
                            searchList:$.getJSON('itemmanage/item/ajaxGetItemBrandList'),
                            formatter:Table.api.formatter.status,
                            operate:false
                        },
                        {field: 'category_id', title: __('Category_id'),
                            searchList:$.getJSON('itemmanage/item/ajaxGetItemCategoryList'),
                            formatter: Table.api.formatter.status,
                            operate:false
                            //formatter: Controller.api.formatter.devicess
                        },
                        {field: 'item_status', title: __('Item_status'),
                            searchList: { 1: '保存', 2 :'提交审核', 3: '审核通过', 4: '审核拒绝', 5: '取消' },
                            custom: {  1: 'yellow', 2: 'blue',3: 'success',4: 'red',5: 'danger' },
                            formatter: Table.api.formatter.status,
                            operate:false
                        },
                        {field: 'stock', title: __('Stock'),operate:false},
                        {field:'is_open',title:__('Is_open'),
                            searchList:{1:'启用',2:'禁用',3:'回收站'},
                            custom:{1:'blue',2:'yellow',3:'red'},
                            formatter:Table.api.formatter.status,
                            operate:false
                        },
                        {
                            field:'is_new',
                            title:__('Is_new'),
                            searchList:{1:'是',2:'不是'},
                            custom:{1:'blue',2:'red'},
                            formatter:Table.api.formatter.status,
                            operate:false
                        },
                        {
                            field:'is_presell',
                            title:__('Is_presell'),
                            searchList:{1:'不是',2:'是'},
                            custom:{1:'blue',2:'red'},
                            formatter:Table.api.formatter.status,
                            operate:false
                        },
                        {field: 'create_person', title: __('Create_person'),operate:false},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', width: "120px", title: __('操作'), table: table,formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'detail',
                                    text: '详情',
                                    title: __('查看详情'),
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-list',
                                    url: '/admin/itemmanage/item/detail',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
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
                                    url: '/admin/itemmanage/item/oneRestore',
                                    confirm:'确认还原吗',
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
                            url: "/admin/itemmanage/item/morePassAudit",
                            data: {ids:ids}
                        },function(data,ret){
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
                            url: "/admin/itemmanage/item/moreRestore",
                            data: {ids:ids}
                        },function(data,ret){
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
                            url:"/admin/itemmanage/item/moveRecycle",
                            data:{ids:ids}
                        },function (data,ret) {
                            table.bootstrapTable('refresh');
                            Layer.close(index);
                        });
                    }
                );
            });
        }
    };
    return Controller;
});
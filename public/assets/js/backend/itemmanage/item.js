define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
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
                        {field: 'id', title: __('Id')},
                        {field: 'name', title: __('Name')},
                        {field: 'category_id', title: __('Category_id')},
                        {field: 'attribute_id', title: __('Attribute_id')},
                        {field: 'stock', title: __('Stock')},
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
                                    url: 'saleaftermanage/sale_after_task/detail',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                },
                            ]
                        },
                        //{field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
            $(document).on('click', '.btn-status', function () {
                $('#status').val(2);
            });
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
                $(document).on('click', '.btn-add', function () {
                    $(".selectpicker").selectpicker('refresh');
                    var content = $('#table-content table tbody').html();
                    console.log(content);
                    $('.caigou table tbody').append(content);


                    // Form.api.bindevent($("form[role=form]"));


                });
                $(document).on('click', '.btn-del', function () {
                    $(this).parent().parent().remove();
                });
                //根据分类请求不同属性页面
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


                    // var arrStr = arrIds.join("&");
                    // //根据承接部门查找出承接人
                    // Backend.api.ajax({
                    //     url:'infosynergytaskmanage/info_synergy_task/ajaxFindRecipient',
                    //     data:{arrIds:arrStr}
                    // }, function(data, ret){
                    //     // console.log(ret.data);
                    //     var rs = ret.data;
                    //     var x;
                    //     $("#choose_rep_id").html('');
                    //     var str = '';
                    //     for( x in rs ){
                    //         str +='<option value="'+x+'">' + rs[x]+'</option>';
                    //     }
                    //     $("#choose_rep_id").append(str);
                    //     $("#choose_rep_id").selectpicker('refresh');
                    //     return false;
                    // }, function(data, ret){
                    //     //失败的回调
                    //     alert(ret.msg);
                    //     console.log(ret);
                    //     return false;
                    // });
                });
            }
        },
        ajaxCategoryInfo:function () {
            Controller.api.bindevent();
            Form.api.bindevent($("form[role=form]"));
            Form.events.datetimepicker($("form"));
        }
    };
    return Controller;
});
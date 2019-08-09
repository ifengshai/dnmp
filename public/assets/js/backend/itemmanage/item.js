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
                        {field: 'weight', title: __('Weight'), operate:'BETWEEN'},
                        {field: 'create_person', title: __('Create_person')},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
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
                $(document).on('change','#choose_category_id',function(){
                    var categoryId = $('#choose_category_id').val();
                    Backend.api.ajax({
                        url:'itemmanage/item/ajaxCategoryInfo',
                        data:{categoryId:categoryId}
                    }, function(data, ret){
                        var resultData = ret.data;
                        console.log(resultData);
                        $('#item-stock').after(function () {
                            return resultData;
                        });
                        // $('#item-stock').after(function(){
                        //     var resultData = ret.data;
                        //     var Str = '';
                        //     for(var j = 0,len = resultData.length; j < len; j++){
                        //         Str+='<div class="form-group">';
                        //         Str+='<label class="control-label col-xs-12 col-sm-2">'+resultData[j].name_cn+'</label>';
                        //         Str+='<div class="col-xs-12 col-sm-3">';
                        //         if(resultData[j].input_mode == 1){ //单选
                        //             if(resultData[j].is_required ==1){ //是否必须
                        //                 Str+='{:Form::select("row[dept_id][]",'+resultData[j].propertyValues+', null, ["class"=>"form-control selectpicker", "data-rule"=>"required","data-live-search" => true])}';
                        //             }else{
                        //                 Str+='{:Form::select("row[dept_id][]", '+resultData[j].propertyValues+', null, ["class"=>"form-control selectpicker","data-live-search" => true])}';
                        //             }
                        //         }else if(resultData[j].input_mode == 2){ //多选
                        //             if(resultData[j].is_required == 1){
                        //                 Str+='{:Form::selects("row[dept_id][]",'+resultData[j].propertyValues+', null,["class"=>"form-control selectpicker", "data-rule"=>"required","data-live-search" => true])}';
                        //             }else{
                        //                 Str+='{:Form::selects("row[dept_id][]",'+resultData[j].propertyValues+', null, ["class"=>"form-control selectpicker","data-live-search" => true])}';
                        //             }
                        //
                        //         }else if(resultData[j].input_mode == 3){ //输入
                        //             if(resultData[j].is_required ==1){ //必须
                        //                 Str+='<input id="c-'+resultData[j].name_en+'" data-rule="required" class="form-control" name="row['+resultData[j].name_en+']" type="text" value="">';
                        //             }else{ //不是必须
                        //                 Str+='<input id="c-'+resultData[j].name_en+'"  class="form-control" name="row['+resultData[j].name_en+']" type="text" value="">';
                        //             }
                        //         }
                        //         Str+='</div>';
                        //
                        //         // if()
                        //         // console.log(resultData[j]);
                        //         // console.log(resultData[j].id);
                        //         // console.log(resultData[j].is_required);
                        //         // console.log(resultData[j].name_cn);
                        //         // console.log(resultData[j].name_en);
                        //         // console.log(resultData[j].input_mode);
                        //         // console.log(resultData[j].propertyValue);
                        //         // var propertyValue = resultData[j].propertyValue;
                        //         // for(var i = 0, lens = propertyValue.length; i<lens;i++){
                        //         //     console.log(propertyValue[i].id);
                        //         //     console.log(propertyValue[i].name_value_cn);
                        //         //     console.log(propertyValue[i].name_value_en);
                        //         //     console.log(propertyValue[i].code_rule);
                        //         // }
                        //     }
                        //     Str+='</div>';
                        //     return Str;
                        //     //console.log(resultData);
                        // });
                        return false;
                    }, function(data, ret){
                        //失败的回调
                        alert(ret.msg);
                        console.log(ret);
                        return false;
                    });
                });
            }
        },
        attribute:function () {
            Controller.api.bindevent();
        }
    };
    return Controller;
});
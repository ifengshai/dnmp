define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {

            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                extend: {
                    index_url: 'lens/index/index' + location.search,
                    add_url: 'lens/index/add',
                    edit_url: 'lens/index/edit',
                    del_url: 'lens/index/del',
                    multi_url: 'lens/index/multi',
                    table: 'lens',
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
                        { field: 'refractive_index', title: __('Refractive_index') },
                        { field: 'lens_type', title: __('Lens_type') },
                        { field: 'sph', title: __('Sph') },
                        { field: 'cyl', title: __('Cyl') },
                        { field: 'stock_num', title: __('Stock_num') },
                        { field: 'price', title: __('Price'), operate: 'BETWEEN' },
                        { field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange' },
                        { field: 'create_person', title: __('Create_person') },
                        { field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
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
        lens: function () {
            Controller.api.bindevent();

            //正值
            $(document).on('click', '.zheng', function () {
                $('.zheng_table').removeClass('hide');
                $('.fu_table').addClass('hide');
                $('.fu').parent().removeClass('active');
                $('.zheng').parent().addClass('active');

            })

            //正值
            $(document).on('click', '.fu', function () {
                $('.fu_table').removeClass('hide');
                $('.zheng_table').addClass('hide');
                $('.zheng').parent().removeClass('active');
                $('.fu').parent().addClass('active');

            })

            //双击事件
            $('.ceshi').dblclick(function () {
                var type = $('.type').val();
                var data = $(this).find('span').attr('data');
                var value = $(this).find('span').text();
                var sph = $(this).find('span').attr('data-sph');
                var cyl = $(this).find('span').attr('data-cyl');
                var refractive_index = $('.refractive_index').val();
                var lens_type = $('.lens_type').val();

                if (!data) {
                    data = '';
                }
                if (type == 2) {
                    var str = '<div>SPH：<input disabled style="line-height:30px; width:300px;" name="sph" value="' + sph + '"  type="text" ><br/><br/>CYL：<input disabled name="cyl" style="line-height:30px; width:300px;" value="' + cyl + '"  type="text" ><br/><br/>库存：<input id="new_stock_num" style="line-height:30px; width:300px;" value="' + data + '"  type="text" name="stock_num"><br/>价格：<input id="new_price" style="line-height:30px; width:300px;margin-top:20px;" value="' + value + '" type="text" name="price"></div>';
                } else {
                    var str = '<div>SPH：<input disabled style="line-height:30px; width:300px;" value="' + sph + '"  type="text" name="sph"><br/><br/>CYL：<input disabled style="line-height:30px; width:300px;" name="cyl" value="' + cyl + '"  type="text" ><br/><br/>库存：<input id="new_stock_num" style="line-height:30px; width:300px;" value="' + value + '"  type="text" name="stock_num"><br/>价格：<input id="new_price" style="line-height:30px; width:300px;margin-top:20px;" value="' + data + '" type="text" name="price"></div>';
                }
                var id = $(this).find('span').attr('data-id');


                Layer.open({
                    type: 1 //Page层类型
                    , area: ['450px', '350px']
                    , title: '镜片属性变更'
                    , skin: 'layui-layer-prompt'
                    , maxmin: true //允许全屏最小化
                    , btn: ["确定", "取消"]
                    , content: str
                    , yes: function () {
                        layer.load();
                        var stock_num = $('#new_stock_num').val();
                        var price = $('#new_price').val();
                        $.post("lens/index/lens_edit", { id: id, stock_num: stock_num, price: price, sph: sph, cyl: cyl, refractive_index: refractive_index, lens_type: lens_type }, function (e) {
                            if (e.code == 1) {
                                location.reload();
                            } else {
                                layer.msg(e.msg);
                            }

                        })
                    }
                });
            })

            //添加
            $(document).on('click', '.btn-add', function () {
                var url = 'lens/index/add';
                Fast.api.open(url, __('Add'), {});
            })

        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'bootstrap-table-jump-to', 'upload'], function ($, undefined, Backend, Table, Form, undefined, Upload) {

    var Controller = {
        index: function () {

            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'lens/index/index' + location.search,
                    add_url: 'lens/index/add',
                    edit_url: 'lens/index/edit',
                    // del_url: 'lens/index/del',
                    import_url: 'lens/index/import',
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
                        { field: 'refractive_index', title: __('Refractive_index'), searchList: { '1.57': '1.57', '1.61': '1.61', '1.67': '1.67', '1.71': '1.71', '1.74': '1.74' } },
                        {
                            field: 'lens_type', title: __('Lens_type'),
                            searchList: { 'Mid-Index': 'Mid-Index', 'High-Index Beyond UV Blue Blockers': 'High-Index Beyond UV Blue Blockers', 'Photochromic - Gray': 'Photochromic - Gray', 'Photochromic - Amber': 'Photochromic - Amber' },
                            operate: 'like'
                        },
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

            // 导入按钮事件
            Upload.api.plupload($('.btn-import'), function (data, ret) {
                Fast.api.ajax({
                    url: 'lens/index/import',
                    data: { file: data.url },
                }, function (data, ret) {
                    layer.msg('导入成功！！', { time: 3000, icon: 6 }, function () {
                        location.reload();
                    });

                });
            });

        },
        lens_out_order: function () {

            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'lens/index/lens_out_order' + location.search,
                    import_url: 'lens/index/import_xls_order',
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
                        { field: 'id', title: __('Id'), operate: false },
                        { field: 'order_number', title: __('订单号') },
                        { field: 'sku', title: __('SKU') },

                        {
                            field: 'lens_type', title: __('Lens_type'),
                            // searchList: { 'Mid-Index': 'Mid-Index', 'High-Index Beyond UV Blue Blockers': 'High-Index Beyond UV Blue Blockers', 'Photochromic - Gray': 'Photochromic - Gray', 'Photochromic - Amber': 'Photochromic - Amber' },
                            operate: 'like'
                        },
                        { field: 'sph', title: __('SPH') },
                        { field: 'cyl', title: __('CYL') },
                        { field: 'num', title: __('出库数量'), operate: false },
                        { field: 'price', title: __('出库总金额'), operate: false },
                        { field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange' },
                        { field: 'create_person', title: __('Create_person'), operate: false }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
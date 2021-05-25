define(['jquery', 'bootstrap', 'backend', 'echartsobj'], function ($, undefined, Backend, EchartObj) {
    var Controller = {
        index: function () {
            getStockOverView()
            getStockGrading()
            Controller.api.formatter.buildStockHealthStatusEChart();
            $('#platform').on('change', () => {
                $('#stock_overview_tbody').empty()
                $('#stock_grading_tbody').empty()
                $('#stock_health_status').empty()

                getStockOverView()
                getStockGrading()
                Controller.api.formatter.buildStockHealthStatusEChart();
            })
        },
        api: {
            formatter: {
                buildStockHealthStatusEChart: function () {
                    //库存分布
                    var chartOptions = {
                        targetId: 'stock_health_status',
                        downLoadTitle: '图表',
                        type: 'pie',
                        pie: {
                            tooltip: { //提示框组件。
                                trigger: 'item',
                                formatter: function (param) {
                                    console.log(param)
                                    return param.data.name + '占比：' + param.percent.toFixed(2) + '%';
                                }
                            },
                            title: {
                                subtext: '总数',
                                left: "center",
                                top: "40%",
                                subtextStyle: {
                                    textAlign: "center",
                                    fill: "#333",
                                    fontSize: 16,
                                    fontWeight: 700
                                },
                                textStyle: {
                                    color: "#27D9C8",
                                    fontSize: 32,
                                    align: "center"
                                }
                            },
                            series: [{
                                radius: ['50%', '70%'],
                            }]
                        }
                    };

                    var options = {
                        url: 'operatedatacenter/goodsdata/stock_data/stockHealthStatus',
                        data: {
                            'platform': $("#platform").val(),
                        }

                    };
                    EchartObj.api.ajax(options, chartOptions)
                },
            }
        }
    };
    return Controller;
});

function getStockOverView() {
    let platform = $('#platform').val();
    Backend.api.ajax({
        url: 'operatedatacenter/goodsdata/stock_data/getStockOverView',
        data: {platform}
    }, function (data) {
        let html = '';
        data.forEach(({
                          category,
                          sku_num,
                          stock,
                          turnover_months,
                          sluggish_stock,
                          sluggish_stock_ratio,
                          sales_last_30_days
                      }) => {

            html += `<tr>
<td>${category}</td>
<td>${sku_num}</td>
<td>${stock}</td>
<td>${sales_last_30_days}</td>
<td>${turnover_months}</td>
<td>${sluggish_stock}</td>
<td>${sluggish_stock_ratio}%</td>
</tr>`
        })

        $('#stock_overview_tbody').append(html)

        return false;
    }, function (data, ret) {
        Layer.alert(ret.msg);
        return false;
    });
}

function getStockGrading() {
    let platform = $('#platform').val();
    Backend.api.ajax({
        url: 'operatedatacenter/goodsdata/stock_data/getStockGrading',
        data: {platform}
    }, function (data) {
        let html = '';
        data.forEach(({
                          grade,
                          sku_num,
                          sku_ratio,
                          stock,
                          stock_ratio,
                          dull_stock,
                          dull_stock_sku_num,
                          dull_stock_ratio,
                          high_risk_dull_stock,
                          high_risk_dull_stock_sku,
                          medium_risk_dull_stock,
                          medium_risk_dull_stock_sku,
                          low_risk_dull_stock,
                          low_risk_dull_stock_sku,
                      }) => {

            html += `<tr>
<td>${grade === 'Z' ? '合计' : grade}</td>
<td>${sku_num}</td>
<td>${sku_ratio}%</td>
<td>${stock}</td>
<td>${stock_ratio}%</td>
<td>120</td>
<td>${dull_stock}</td>
<td>${dull_stock_sku_num}</td>
<td>${dull_stock_ratio}%</td>
<td>${high_risk_dull_stock}（${high_risk_dull_stock_sku}）</td>
<td>${medium_risk_dull_stock}（${medium_risk_dull_stock_sku}）</td>
<td>${low_risk_dull_stock}（${low_risk_dull_stock_sku}）</td>
</tr>`
        })

        $('#stock_grading_tbody').append(html)

        return false;
    }, function (data, ret) {
        Layer.alert(ret.msg);
        return false;
    });
}

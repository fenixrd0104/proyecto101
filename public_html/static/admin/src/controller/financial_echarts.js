/**

 @Name：layuiAdmin Echarts集成
 @Author：star1029
 @Site：http://www.layui.com/admin/
 @License：GPL-2
    
 */
layui.define(function(exports){
//区块轮播切换
layui.use(['admin', 'carousel'], function(){
    var $ = layui.$
    ,admin = layui.admin
    ,carousel = layui.carousel
    ,element = layui.element
    ,device = layui.device();

    //轮播切换
    $('.layadmin-carousel').each(function(){
    var othis = $(this);
    carousel.render({
        elem: this
        ,width: '100%'
        ,arrow: 'none'
        ,interval: othis.data('interval')
        ,autoplay: othis.data('autoplay') === true
        ,trigger: (device.ios || device.android) ? 'click' : 'hover'
        ,anim: othis.data('anim')
    });
    });

});
    //折线图
    layui.use(['admin','form','echarts','element','table'], function(){
    var $ = layui.$
    ,admin = layui.admin
    ,form = layui.form
    ,element = layui.element
    ,table = layui.table
    ,echarts = layui.echarts;

    
    // 提现人数统计
    var txrs_category;
    var txrs_value;
    txrs_ajax(); 
    //监听搜索 
    form.on('submit(txrs_search)', function(data){
        // console.log(data.field);
        txrs_ajax(data.field);
    });
    // 日-周-月-季-年
    $('#txrs_data').find('.layui-btn').click(function(){
        var lay_type= $(this).attr('lay-type');
        // console.log(lay_type);
        txrs_ajax( {type:lay_type} );
    })
    function txrs_ajax(data_s){
    admin.req({
        url: layui.setter.baseUrl + 'admin/StatisticsFinancial/renshu_k' 
        ,data: data_s
        ,type: 'get'
        ,success: function (res) {
            if (res.code == 1) {
                txrs_category=res.data.category;
                txrs_value=res.data.value;
                // 赋值人数
                var strong=0;
                for( var i in txrs_value){
                    strong= strong+txrs_value[i];
                }
                $('#txrs_strong').html(strong);   
                txrs();            
            } else {
                layer.msg(res.msg);
            }
        }
    });
    }
    
    function txrs(){
    var echheapline = [], heapline = [{
        tooltip : {
            trigger: 'axis',
            axisPointer : {            // 坐标轴指示器，坐标轴触发有效
                type : 'line'        // 默认为直线，可选为：'line' | 'shadow'
            }
        }, 
        color: ["#ff6c87"],
        toolbox: {
            show: true,
            orient: 'vertical',
            top: 'center',
            feature: {
                mark: {
                    show: true
                },
                dataView: {
                    show: true,
                    readOnly: true
                },
                magicType: {
                    show: true,
                    type: ['line', 'bar']
                },
                restore: {
                    show: true
                },
                saveAsImage: {
                    show: true
                }
            }
        },
        legend: {
            data:['提现人数'],
            x:"left"
        },
        xAxis:[{
            type: 'category',
            data: txrs_category,
            splitLine: {
                show: false // x轴的网格隐藏
            },
            axisPointer: {
                type: 'shadow'
            }
        }],
        yAxis: [
        {
            type: 'value',
        }],
        series: [
        {
            name: '提现人数',
            type: 'line',
            data: txrs_value
        }]
    }]
    ,elemheapline = $('#LAY-index-withd_people').children('div')
    ,renderheapline = function(index){
        echheapline[index] = echarts.init(elemheapline[index], layui.echartsTheme);
        echheapline[index].setOption(heapline[index]);
        window.onresize = echheapline[index].resize;
    };
    if(!elemheapline[0]) return;
    renderheapline(0);
    }
   
    // 提现笔数统计
    var txbs_category;
    var txbs_value;
    txbs_ajax();
    //监听搜索 
    form.on('submit(txbs_search)', function(data){
        // console.log(data.field);
        txbs_ajax(data.field);
    });
    // 日-周-月-季-年
    $('#txbs_data').find('.layui-btn').click(function(){
        var lay_type= $(this).attr('lay-type');
        // console.log(lay_type);
        txbs_ajax( {type:lay_type} );
    })
    function txbs_ajax(data_s){
    admin.req({
        url: layui.setter.baseUrl + 'admin/StatisticsFinancial/bishu_k' 
        ,data: data_s
        ,type: 'get'
        ,success: function (res) {
            if (res.code == 1) {
                txbs_category=res.data.category;
                txbs_value=res.data.value;
                // 赋值人数
                var strong=0;
                for( var i in txbs_value){
                    strong= strong+txbs_value[i];
                }
                $('#txbs_strong').html(strong);
                txbs();       
            } else {
                layer.msg(res.msg);
            }
        }
    });
    }
    
    function txbs(){
    var echheapline = [], heapline = [{
        tooltip : {
            trigger: 'axis',
            axisPointer : {            // 坐标轴指示器，坐标轴触发有效
                type : 'line'        // 默认为直线，可选为：'line' | 'shadow'
            }
        }, 
        color: ["#3aa1ff"],
        toolbox: {
            show: true,
            orient: 'vertical',
            top: 'center',
            feature: {
                mark: {
                    show: true
                },
                dataView: {
                    show: true,
                    readOnly: true
                },
                magicType: {
                    show: true,
                    type: ['line', 'bar']
                },
                restore: {
                    show: true
                },
                saveAsImage: {
                    show: true
                }
            }
        },
        legend: {
            data:['提现笔数'],
            x:"left"
        },
        xAxis:[{
            type: 'category',
            data: txbs_category,
            splitLine: {
                show: false // x轴的网格隐藏
            },
            axisPointer: {
                type: 'shadow'
            }
        }],
        yAxis: [
        {
            type: 'value',
        }],
        series: [
        {
            name: '提现笔数',
            type: 'line',
            data: txbs_value
        }]
    }]
    ,elemheapline = $('#LAY-index-withd_number').children('div')
    ,renderheapline = function(index){
        echheapline[index] = echarts.init(elemheapline[index], layui.echartsTheme);
        echheapline[index].setOption(heapline[index]);
        window.onresize = echheapline[index].resize;
    };
    if(!elemheapline[0]) return;
    renderheapline(0);
    }

    // 提现金额统计
    var txje_category;
    var txje_value;
    txje_ajax();
    //监听搜索 
    form.on('submit(txje_search)', function(data){
        // console.log(data.field);
        txje_ajax(data.field);
    });
    // 日-周-月-季-年
    $('#txje_data').find('.layui-btn').click(function(){
        var lay_type= $(this).attr('lay-type');
        // console.log(lay_type);
        txje_ajax( {type:lay_type} );
    })
    function txje_ajax(data_s){
    admin.req({
        url: layui.setter.baseUrl + 'admin/StatisticsFinancial/jine_k' 
        ,data: data_s
        ,type: 'get'
        ,success: function (res) {
            if (res.code == 1) {
                txje_category=res.data.category;
                txje_value=res.data.value;
                // 赋值人数
                // var strong=0;
                // for( var i in txje_value){
                //     strong= parseFloat(strong+txje_value[i]);  
                // }
                // $('#txje_strong').html( strong.toFixed(2) );
                txje();       
            } else {
                layer.msg(res.msg);
            }
        }
    });
    }
    function txje(){ 
    var echheapline = [], heapline = [{
        tooltip : {
            trigger: 'axis',
            axisPointer : {            // 坐标轴指示器，坐标轴触发有效
                type : 'line'        // 默认为直线，可选为：'line' | 'shadow'
            }
        }, 
        color: ["#ff9e00"],
        toolbox: {
            show: true,
            orient: 'vertical',
            top: 'center',
            feature: {
                mark: {
                    show: true
                },
                dataView: {
                    show: true,
                    readOnly: true
                },
                magicType: {
                    show: true,
                    type: ['line', 'bar']
                },
                restore: {
                    show: true
                },
                saveAsImage: {
                    show: true
                }
            }
        },
        legend: {
            data:['提现金额'],
            x:"left"
        },
        xAxis:[{
            type: 'category',
            data: txje_category,
            splitLine: {
                show: false // x轴的网格隐藏
            },
        }],
        yAxis: [
        {
            type: 'value',
        }],
        series: [
        {
            name: '提现金额',
            type: 'line',
            data: txje_value
        }]
    }]
    ,elemheapline = $('#LAY-index-withd_money').children('div')
    ,renderheapline = function(index){
        echheapline[index] = echarts.init(elemheapline[index], layui.echartsTheme);
        echheapline[index].setOption(heapline[index]);
        window.onresize = echheapline[index].resize;
    };
    if(!elemheapline[0]) return;
    renderheapline(0);
    }

    // 提现人均金额统计
    var txrj_category;
    var txrj_value;
    txrj_ajax();
    //监听搜索 
    form.on('submit(txrj_search)', function(data){
        // console.log(data.field);
        txrj_ajax(data.field);
    });
    // 日-周-月-季-年
    $('#txrj_data').find('.layui-btn').click(function(){
        var lay_type= $(this).attr('lay-type');
        // console.log(lay_type);
        txrj_ajax( {type:lay_type} );
    })
    function txrj_ajax(data_s){
    admin.req({
        url: layui.setter.baseUrl + 'admin/StatisticsFinancial/renjun_k' 
        ,data: data_s
        ,type: 'get'
        ,success: function (res) {
            if (res.code == 1) {
                txrj_category=res.data.category;
                txrj_value=res.data.value;
                // 赋值人数
                // var strong=0;
                // for( var i in txrj_value){
                //     strong= parseFloat(strong+txrj_value[i]);                    
                // }
                // $('#txrj_strong').html( strong.toFixed(2) );
                txrj();       
            } else {
                layer.msg(res.msg);
            }
        }
    });
    }
    function txrj(){
    var echheapline = [], heapline = [{
        tooltip : {
            trigger: 'axis',
            axisPointer : {            // 坐标轴指示器，坐标轴触发有效
                type : 'line'        // 默认为直线，可选为：'line' | 'shadow'
            }
        }, 
        color: ["#8378ea"],
        toolbox: {
            show: true,
            orient: 'vertical',
            top: 'center',
            feature: {
                mark: {
                    show: true
                },
                dataView: {
                    show: true,
                    readOnly: true
                },
                magicType: {
                    show: true,
                    type: ['line', 'bar']
                },
                restore: {
                    show: true
                },
                saveAsImage: {
                    show: true
                }
            }
        },
        legend: {
            data:['提现人均金额'],
            x:"left"
        },
        xAxis:[{
            type: 'category',
            data: txrj_category,
            splitLine: {
                show: false // x轴的网格隐藏
            },
            axisPointer: {
                type: 'shadow'
            }
        }],
        yAxis: [
        {
            type: 'value',
        }],
        series: [
        {
            name: '提现人均金额',
            type: 'line',
            data: txrj_value
        }]
    }]
    ,elemheapline = $('#LAY-index-withd_capita').children('div')
    ,renderheapline = function(index){
        echheapline[index] = echarts.init(elemheapline[index], layui.echartsTheme);
        echheapline[index].setOption(heapline[index]);
        window.onresize = echheapline[index].resize;
    };
    if(!elemheapline[0]) return;
    renderheapline(0);
    }
    // 提现方式饼状图
    var echheapline = [], heapline = [{       
        color: ["#7cb5ec", "#f05189","#f7a35c"],
        tooltip: {
            trigger: 'item',
            formatter: " {b} <br/> 比例：{c}%",
        },
        series: [{
            type: 'pie',
            name: "比例",
            data: [{
                name:'支付宝',
                value: 100,
            },
            {
                name:'网银支付',
                value: 0,
            },
            {
                name:'微信支付',
                value: 0,
            }]
        }]
    }]
    ,elemheapline = $('#LAY-index-withd_type').children('div')
    ,renderheapline = function(index){
        echheapline[index] = echarts.init(elemheapline[index], layui.echartsTheme);
        echheapline[index].setOption(heapline[index]);
        window.onresize = echheapline[index].resize;
    };
    if(!elemheapline[0]) return;
    renderheapline(0);
 
    // 测试111

    // var myChart = echarts.init(document.getElementById('main'), layui.echartsTheme);
    // // 指定图表的配置项和数据
    // var option={
    //     tooltip : {
    //         trigger: 'axis',
    //         axisPointer : {            // 坐标轴指示器，坐标轴触发有效
    //             type : 'line'        // 默认为直线，可选为：'line' | 'shadow'
    //         }
    //     }, 
    //     color: ["#3aa1ff"],
    //     toolbox: {
    //         show: true,
    //         orient: 'vertical',
    //         top: 'center',
    //         feature: {
    //             mark: {
    //                 show: true
    //             },
    //             dataView: {
    //                 show: true,
    //                 readOnly: true
    //             },
    //             magicType: {
    //                 show: true,
    //                 type: ['line', 'bar']
    //             },
    //             restore: {
    //                 show: true
    //             },
    //             saveAsImage: {
    //                 show: true
    //             }
    //         }
    //     },
    //     legend: {
    //         data:['充值笔数'],
    //         x:"left"
    //     },
    //     xAxis:[{
    //         type: 'category',
    //         data: ["2019-06-28","2019-06-29","2019-06-30","2019-07-01","2019-07-02"],
    //         splitLine: {
    //             show: false // x轴的网格隐藏
    //         },
    //         axisPointer: {
    //             type: 'shadow'
    //         }
    //     }],
    //     yAxis: [
    //     {
    //         type: 'value',
    //     }],
    //     series: [
    //     {
    //         name: '充值笔数',
    //         type: 'line',
    //         data: [ ]
    //     }]
    // };
    // // 使用刚指定的配置项和数据显示图表。
    // myChart.setOption(option);
    // window.onresize = myChart.resize;
    // // tab切换时 重载echarts
    // $(document).on('click','#lay_tab_hd li', function (e) {
    //     myChart.resize();
    // });
    // window.addEventListener('resize',function(){
    //     myChart.resize();
    // })
   
   
element.on('tab(component-tabs-brief)', function(data){    
    table.resize(); 
    
    // myChart.resize(); 

    // 充值笔数统计
    var echheapline = [], heapline = [{
        tooltip : {
            trigger: 'axis',
            axisPointer : {            // 坐标轴指示器，坐标轴触发有效
                type : 'line'        // 默认为直线，可选为：'line' | 'shadow'
            }
        }, 
        color: ["#3aa1ff"],
        toolbox: {
            show: true,
            orient: 'vertical',
            top: 'center',
            feature: {
                mark: {
                    show: true
                },
                dataView: {
                    show: true,
                    readOnly: true
                },
                magicType: {
                    show: true,
                    type: ['line', 'bar']
                },
                restore: {
                    show: true
                },
                saveAsImage: {
                    show: true
                }
            }
        },
        legend: {
            data:['充值笔数'],
            x:"left"
        },
        xAxis:[{
            type: 'category',
            data: ["2019-06-28","2019-06-29","2019-06-30","2019-07-01","2019-07-02","2019-07-03","2019-07-04","2019-07-05","2019-07-06","2019-07-07","2019-07-08","2019-07-09","2019-07-10","2019-07-11","2019-07-12","2019-07-13","2019-07-14","2019-07-15","2019-07-16","2019-07-17","2019-07-18","2019-07-19","2019-07-20","2019-07-21","2019-07-22","2019-07-23","2019-07-24","2019-07-25","2019-07-26","2019-07-27"],
            splitLine: {
                show: false // x轴的网格隐藏
            },
            axisPointer: {
                type: 'shadow'
            }
        }],
        yAxis: [
        {
            type: 'value',
        }],
        series: [
        {
            name: '充值笔数',
            type: 'line',
            data: [ 0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0]
        }]
    }]
    ,elemheapline = $('#LAY-index-topup_number').children('div')
    ,renderheapline = function(index){
        echheapline[index] = echarts.init(elemheapline[index], layui.echartsTheme);
        echheapline[index].setOption(heapline[index]);
        window.onresize = echheapline[index].resize;
    };
    if(!elemheapline[0]) return;
    renderheapline(0);
    // 提现金额统计
    var echheapline = [], heapline = [{
        tooltip : {
            trigger: 'axis',
            axisPointer : {            // 坐标轴指示器，坐标轴触发有效
                type : 'line'        // 默认为直线，可选为：'line' | 'shadow'
            }
        }, 
        color: ["#ff9e00"],
        toolbox: {
            show: true,
            orient: 'vertical',
            top: 'center',
            feature: {
                mark: {
                    show: true
                },
                dataView: {
                    show: true,
                    readOnly: true
                },
                magicType: {
                    show: true,
                    type: ['line', 'bar']
                },
                restore: {
                    show: true
                },
                saveAsImage: {
                    show: true
                }
            }
        },
        legend: {
            data:['充值金额'],
            x:"left"
        },
        xAxis:[{
            type: 'category',
            data: ["2019-06-28","2019-06-29","2019-06-30","2019-07-01","2019-07-02","2019-07-03","2019-07-04","2019-07-05","2019-07-06","2019-07-07","2019-07-08","2019-07-09","2019-07-10","2019-07-11","2019-07-12","2019-07-13","2019-07-14","2019-07-15","2019-07-16","2019-07-17","2019-07-18","2019-07-19","2019-07-20","2019-07-21","2019-07-22","2019-07-23","2019-07-24","2019-07-25","2019-07-26","2019-07-27"],
            splitLine: {
                show: false // x轴的网格隐藏
            },
        }],
        yAxis: [
        {
            type: 'value',
        }],
        series: [
        {
            name: '充值金额',
            type: 'line',
            data: [ 0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0]
        }]
    }]
    ,elemheapline = $('#LAY-index-topup_money').children('div')
    ,renderheapline = function(index){
        echheapline[index] = echarts.init(elemheapline[index], layui.echartsTheme);
        echheapline[index].setOption(heapline[index]);
        window.onresize = echheapline[index].resize;
    };
    if(!elemheapline[0]) return;
    renderheapline(0);







});  
});
    
exports('financial_echarts', {})
});
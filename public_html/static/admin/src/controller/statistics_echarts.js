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

    // all_data();
// function all_data(){


    // 实时概况
    var xs_fk_category;
    var xs_fk_jt_data;
    var xs_fk_zt_data;
    admin.req({
        url: layui.setter.baseUrl + 'admin/StatisticStransaction/jiaoyi' 
        ,data: { }
        ,type: 'get'
        ,success: function (res) {
            if (res.code == 1) {
                var data=res.data;
                $('#xs_dds_all').html( data.xianshang_dingdan.zong );
                $('#xs_dds_zuo').html( data.xianshang_dingdan.zuo );
                $('#xs_fkrs_all').html( data.xianshang_fukuan.zong );
                $('#xs_fkrs_zuo').html( data.xianshang_fukuan.zuo );
                $('#xs_zdds_all').html( data.xianshang_zongdingdan.zong );
                $('#xs_zdds_zuo').html( data.xianshang_zongdingdan.zuo );
                $('#xs_zrs_all').html( data.xianshang_zongrenshu.zong );
                $('#xs_zrs_zuo').html( data.xianshang_zongrenshu.zuo );

                $('#xx_dds_all').html( data.xianxia_dingdan.zong );
                $('#xx_dds_zuo').html( data.xianxia_dingdan.zuo );
                $('#xx_fkrs_all').html( data.xianxia_fukuan.zong );
                $('#xx_fkrs_zuo').html( data.xianxia_fukuan.zuo );
                $('#xx_zdds_all').html( data.xianxia_zongdingdan.zong );
                $('#xx_zdds_zuo').html( data.xianxia_zongdingdan.zuo );
                $('#xx_zrs_all').html( data.xianxia_zongrenshu.zong );
                $('#xx_zrs_zuo').html( data.xianxia_zongrenshu.zuo );

                xs_fk_category= data.xianshang_fukuanjine.category;
                xs_fk_jt_data= data.xianshang_fukuanjine.jintian;
                xs_fk_zt_data= data.xianshang_fukuanjine.zuotian;

                xx_fk_category= data.xianxia_fukuanjine.category;
                xx_fk_jt_data= data.xianxia_fukuanjine.jintian;
                xx_fk_zt_data= data.xianxia_fukuanjine.zuotian;
                // 赋值
                // 线上
                // var xsjt_strong=0;
                // for( var i in xs_fk_jt_data){
                //     xsjt_strong= parseFloat(xsjt_strong+xs_fk_jt_data[i]);
                // }
                // $('#xs_fkjt_strong').html(xsjt_strong.toFixed(2)); 
                // var xszt_strong=0;
                // for( var j in xs_fk_zt_data){
                //     xszt_strong= parseFloat(xszt_strong+xs_fk_zt_data[j]);
                // }
                // $('#xs_fkzt_strong').html(xszt_strong.toFixed(2)); 
                xsfk();
                // 线下
                // var xxjt_strong=0;
                // for( var i in xx_fk_jt_data){
                //     xxjt_strong= parseFloat(xxjt_strong+xx_fk_jt_data[i]);
                // }
                // $('#xx_fkjt_strong').html(xxjt_strong.toFixed(2)); 
                // var xxzt_strong=0;
                // for( var j in xx_fk_zt_data){
                //     xxzt_strong= parseFloat(xxzt_strong+xx_fk_zt_data[j]);
                // }
                // $('#xx_fkzt_strong').html(xxzt_strong.toFixed(2)); 
                xxfk();
            } else {
                layer.msg(res.msg);
            }
        }
    });
    // 付款概况---线上和线下
    function xsfk(){
    var echheapline = [], heapline = [{
        tooltip: {
            trigger: 'axis'
        },
        grid: {
            containLabel: true
        },
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
            data: ['线上今天付款金额', '线上昨日付款金额'],
            x: 'left'
        },   
        xAxis: {
            type: 'category',
            name: "小时",
            data: xs_fk_category,
            splitLine: {
                show: false // x轴的网格隐藏
            },
        },
        yAxis: {
            type: 'value',
        },
        series: [
        {
            name: '线上今天付款金额',
            type: 'line',
            data: xs_fk_jt_data
        },
        {
            name: '线上昨日付款金额',
            type: 'line',
            data: xs_fk_zt_data
        }
        ]
    }]
    ,elemheapline = $('#LAY-index-payment_online').children('div')
    ,renderheapline = function(index){
        echheapline[index] = echarts.init(elemheapline[index], layui.echartsTheme);
        echheapline[index].setOption(heapline[index]);
        window.onresize = echheapline[index].resize;
    };
    if(!elemheapline[0]) return;
    renderheapline(0);
    }
    function xxfk(){
    var echheapline = [], heapline = [{
        tooltip: {
            trigger: 'axis'
        },
        grid: {
            containLabel: true
        },
        color: ["#ff6c87","#ff9e00"],
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
            data: ['线下今天付款金额', '线下昨日付款金额'],
            x: 'left'
        },   
        xAxis: {
            type: 'category',
            name: "小时",
            data: xx_fk_category,
            splitLine: {
                show: false // x轴的网格隐藏
            },
        },
        yAxis: {
            type: 'value',
        },
        series: [
        {
            name: '线下今天付款金额',
            type: 'line',
            data: xx_fk_jt_data
        },
        {
            name: '线下昨日付款金额',
            type: 'line',
            data: xx_fk_zt_data
        }
        ]
    }]
    ,elemheapline = $('#LAY-index-payment_offline').children('div')
    ,renderheapline = function(index){
        echheapline[index] = echarts.init(elemheapline[index], layui.echartsTheme);
        echheapline[index].setOption(heapline[index]);
        window.onresize = echheapline[index].resize;
    };
    if(!elemheapline[0]) return;
    renderheapline(0);
    }
// 核心指标
    var xszb_category;
    var xszb_bs_value;
    var xszb_je_value;
    var xxzb_category;
    var xxzb_bs_value;
    var xxzb_je_value;
    hxzb_ajax(); 
    //监听搜索 
    form.on('submit(hxzb_search)', function(data){
        // console.log(data.field);
        hxzb_ajax(data.field);
    });
    // 日-周-月-季-年
    $('#hxzb_data').find('.layui-btn').click(function(){
        var lay_type= $(this).attr('lay-type');
        // console.log(lay_type);
        hxzb_ajax( {type:lay_type} );
    })
    function hxzb_ajax(data_s){
    admin.req({
        url: layui.setter.baseUrl + 'admin/StatisticStransaction/jiaoyifukuan_k' 
        ,data: data_s
        ,type: 'get'
        ,success: function (res) {
            if (res.code == 1) {
                // 线上
                xszb_category=res.data.xianshang.category;
                xszb_bs_value=res.data.xianshang.bishu;
                xszb_je_value=res.data.xianshang.jine;
                xszb();        
                // 线下
                xxzb_category=res.data.xianxia.category;
                xxzb_bs_value=res.data.xianxia.bishu;
                xxzb_je_value=res.data.xianxia.jine;
                xxzb();  
            } else {
                layer.msg(res.msg);
            }
        }
    });
    }
    // 核心指标---线上和线下
    function xszb(){
    var echheapline = [], heapline = [{
        tooltip : {
            trigger: 'axis',
            axisPointer : {            // 坐标轴指示器，坐标轴触发有效
                type : 'shadow'        // 默认为直线，可选为：'line' | 'shadow'
            }
        }, 
        legend: {
            data:['线上付款订单金额','线上付款订单数']
        },
        xAxis:[{
            type: 'category',
            data: xszb_category,
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
            name: '线上付款订单金额',
            position: 'left',
            axisLabel: {
                formatter: '{value} 元'
            }
        },
        {
            type: 'value',
            name: '线上付款订单数',
            position: 'right',
            axisLabel: {
                formatter: '{value} 笔'
            }
        }],
        series: [
        {
            name: '线上付款订单金额',
            type: 'bar',
            data: xszb_je_value
        },  
        {
            name: '线上付款订单数',
            type: 'bar',
            yAxisIndex: 1,
            data: xszb_bs_value
        }]
    }]
    ,elemheapline = $('#LAY-index-core_online').children('div')
    ,renderheapline = function(index){
        echheapline[index] = echarts.init(elemheapline[index], layui.echartsTheme);
        echheapline[index].setOption(heapline[index]);
        window.onresize = echheapline[index].resize;
    };
    if(!elemheapline[0]) return;
    renderheapline(0);
    }
    function xxzb(){
    var echheapline = [], heapline = [{
        tooltip : {
            trigger: 'axis',
            axisPointer : {            // 坐标轴指示器，坐标轴触发有效
                type : 'shadow'        // 默认为直线，可选为：'line' | 'shadow'
            }
        }, 
        legend: {
            data:['线下付款订单金额','线下付款订单数']
        },
        color: ["#ff6c87","#ff9e00"],
        xAxis:[{
            type: 'category',
            data: xxzb_category,
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
            name: '线下付款订单金额',
            position: 'left',
            axisLabel: {
                formatter: '{value} 元'
            }
        },
        {
            type: 'value',
            name: '线下付款订单数',
            position: 'right',
            axisLabel: {
                formatter: '{value} 笔'
            }
        }],
        series: [
        {
            name: '线下付款订单金额',
            type: 'bar',
            data: xxzb_je_value
        },  
        {
            name: '线下付款订单数',
            type: 'bar',
            yAxisIndex: 1,
            data: xxzb_bs_value
        }]
    }]
    ,elemheapline = $('#LAY-index-core_offline').children('div')
    ,renderheapline = function(index){
        echheapline[index] = echarts.init(elemheapline[index], layui.echartsTheme);
        echheapline[index].setOption(heapline[index]);
        window.onresize = echheapline[index].resize;
    };
    if(!elemheapline[0]) return;
    renderheapline(0);    
    }
    // 成交金额
    var x_cjjn_value;
    var j_cjjn_value;
    var x_cjrs_value;
    var j_cjrs_value;
    admin.req({
        url: layui.setter.baseUrl + 'admin/StatisticStransaction/jiaoyichengjiao' 
        ,data: {}
        ,type: 'get'
        ,success: function (res) {
            if (res.code == 1) {
                // 数据
                $('#bz_fgl').html( res.data.benzhou.bili+"%");
                $('#by_fgl').html( res.data.benyue.bili+"%");
                if( res.data.benzhou.type =="icon-down" ){
                    var html='<span class="p_green">'+res.data.benzhou.duibi+'% <i class="iconfont icon-down"></i></span>';
                } else{
                    var html='<span class="p_red">'+res.data.benzhou.duibi+'% <i class="iconfont icon-up"></i></span>';
                }
                $('#bz_type').html(html);
                if( res.data.benyue.type =="icon-down" ){
                    var html1='<span class="p_green">'+res.data.benyue.duibi+'% <i class="iconfont icon-down"></i></span>';
                } else{
                    var html1='<span class="p_red">'+res.data.benyue.duibi+'% <i class="iconfont icon-up"></i></span>';
                }
                $('#by_type').html(html1);

                x_cjjn_value=res.data.jine.new;
                j_cjjn_value=res.data.jine.old;
                x_cjrs_value=res.data.renshu.new;
                j_cjrs_value=res.data.renshu.old;
                cjjn();
                cjrs();
            } else {
                layer.msg(res.msg);
            }
        }
    });
    function cjjn(){
    var echheapline = [], heapline = [{
        legend: {
            data: ['新成交用户付款金额', '老成交用户付款金额']
        },
        color: ["#3c8dff", "#ff38b1"],
        tooltip: {
            trigger: 'item',
            formatter: " {b} <br/> 金额：{c} <br/> 占比：{d}%",
        },
        series: [{
            type: 'pie',
            radius: ['30%', '50%'],
            center: ['50%', '60%'], //位置
            data: [{
                value: x_cjjn_value,
                name: '新成交用户付款金额'
            }, {
                value: j_cjjn_value,
                name: '老成交用户付款金额'
            }]
        }]
    }]
    ,elemheapline = $('#LAY-index-clinch-money').children('div')
    ,renderheapline = function(index){
        echheapline[index] = echarts.init(elemheapline[index], layui.echartsTheme);
        echheapline[index].setOption(heapline[index]);
        window.onresize = echheapline[index].resize;
    };
    if(!elemheapline[0]) return;
    renderheapline(0);
    }
    // 成交人数
    function cjrs(){
    var echheapline = [], heapline = [{
        legend: {
            data: ['新成交客户数', '老成交客户数']
        },
        color: ["#00a7ff", "#ffb638"],
        tooltip: {
            trigger: 'item',
            formatter: " {b} <br/> 人数：{c} <br/> 占比：{d}%",
        },
        series: [{
            type: 'pie',
            radius: ['30%', '50%'],
            center: ['50%', '60%'], //位置
            data: [{
                value: x_cjrs_value,
                name: '新成交客户数'
            }, {
                value: j_cjrs_value,
                name: '老成交客户数'
            }]
        }]
    }]
    ,elemheapline = $('#LAY-index-clinch-people').children('div')
    ,renderheapline = function(index){
        echheapline[index] = echarts.init(elemheapline[index], layui.echartsTheme);
        echheapline[index].setOption(heapline[index]);
        window.onresize = echheapline[index].resize;
    };
    if(!elemheapline[0]) return;
    renderheapline(0);
    }
    
// }

element.on('tab(component-tabs-brief)', function(data){    
    table.resize();  
    

    // all_data();
    // 商品动销概况
    var spdx_category;
    var spdx_dx_value;
    var spdx_zj_value;
    spdx_ajax(); 
    //监听搜索 
    form.on('submit(spdx_search)', function(data){
        // console.log(data.field);
        spdx_ajax(data.field);
    });
    // 日-周-月-季-年
    $('#spdx_data').find('.layui-btn').click(function(){
        var lay_type= $(this).attr('lay-type');
        // console.log(lay_type);
        spdx_ajax( {type:lay_type} );
    })
    function spdx_ajax(data_s){
    admin.req({
        url: layui.setter.baseUrl + 'admin/StatisticStransaction/shagnpdognxiao_k' 
        ,data: data_s
        ,type: 'get'
        ,success: function (res) {
            if (res.code == 1) {
                spdx_category=res.data.category;
                spdx_dx_value=res.data.dongxiao;
                spdx_zj_value=res.data.zaijia;
                spdx(); 
            } else {
                layer.msg(res.msg);
            }
        }
    });
    }
    function spdx(){
    var echheapline = [], heapline = [{
        tooltip: {
            trigger: 'axis'
        },
        grid: {
            containLabel: true
        },
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
            data: ['动销商品数', '在架商品数'],
            x: 'left'
        },   
        xAxis: {
            type: 'category',
            data: spdx_category,
            splitLine: {
                show: false // x轴的网格隐藏
            },
        },
        yAxis: {
            type: 'value',
        },
        series: [
        {
            name: '动销商品数',
            type: 'line',
            data: spdx_dx_value
        },
        {
            name: '在架商品数',
            type: 'line',
            data: spdx_zj_value
        }
        ]
    }]
    ,elemheapline = $('#LAY-index-pin').children('div')
    ,renderheapline = function(index){
        echheapline[index] = echarts.init(elemheapline[index], layui.echartsTheme);
        echheapline[index].setOption(heapline[index]);
        window.onresize = echheapline[index].resize;
    };
    if(!elemheapline[0]) return;
    renderheapline(0);
    }
    
    // 订单笔数
    var ddbs_category;
    var ddbs_xs_value;
    var ddbs_xx_value;
    ddbs_ajax(); 
    //监听搜索 
    form.on('submit(ddbs_search)', function(data){
        // console.log(data.field);
        ddbs_ajax(data.field);
    });
    // 日-周-月-季-年
    $('#ddbs_data').find('.layui-btn').click(function(){
        var lay_type= $(this).attr('lay-type');
        // console.log(lay_type);
        ddbs_ajax( {type:lay_type} );
    })
    function ddbs_ajax(data_s){
    admin.req({
        url: layui.setter.baseUrl + 'admin/StatisticStransaction/dingdanbishu_k' 
        ,data: data_s
        ,type: 'get'
        ,success: function (res) {
            if (res.code == 1) {
                ddbs_category=res.data.category;
                // 线上
                ddbs_xs_value=res.data.xianshang;
                // 赋值人数
                // var strong_xs=0;
                // for( var i in ddbs_xs_value){
                //     strong_xs= strong_xs+ddbs_xs_value[i];
                // }
                // $('#ddbs_xs_strong').html(strong_xs); 
                // 线下  
                ddbs_xx_value=res.data.xianxia;
                // var strong_xx=0;
                // for( var i in ddbs_xx_value){
                //     strong_xx= strong_xx+ddbs_xx_value[i];
                // }
                // $('#ddbs_xx_strong').html(strong_xx);
                
                ddbs();            
            } else {
                layer.msg(res.msg);
            }
        }
    });
    }
    function ddbs(){
    var echheapline = [], heapline = [{
        tooltip: {
            trigger: 'axis'
        },
        grid: {
            containLabel: true
        },
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
            data: ['线上订单笔数', '线下订单笔数'],
            x: 'left'
        },   
        xAxis: {
            type: 'category',
            data: ddbs_category,
            splitLine: {
                show: false // x轴的网格隐藏
            },
        },
        yAxis: {
            type: 'value',
        },
        series: [
        {
            name: '线上订单笔数',
            type: 'line',
            data: ddbs_xs_value
        },
        {
            name: '线下订单笔数',
            type: 'line',
            data: ddbs_xx_value
        }
        ]
    }]
    ,elemheapline = $('#LAY-index-order').children('div')
    ,renderheapline = function(index){
        echheapline[index] = echarts.init(elemheapline[index], layui.echartsTheme);
        echheapline[index].setOption(heapline[index]);
        window.onresize = echheapline[index].resize;
    };
    if(!elemheapline[0]) return;
    renderheapline(0);
    }
    // 订单金额
    var ddje_category;
    var ddje_xs_value;
    var ddje_xx_value;
    ddje_ajax(); 
    //监听搜索 
    form.on('submit(ddje_search)', function(data){
        // console.log(data.field);
        ddje_ajax(data.field);
    });
    // 日-周-月-季-年
    $('#ddje_data').find('.layui-btn').click(function(){
        var lay_type= $(this).attr('lay-type');
        // console.log(lay_type);
        ddje_ajax( {type:lay_type} );
    })
    function ddje_ajax(data_s){
    admin.req({
        url: layui.setter.baseUrl + 'admin/StatisticStransaction/dingdanjine_k' 
        ,data: data_s
        ,type: 'get'
        ,success: function (res) {
            if (res.code == 1) {
                ddje_category=res.data.category;
                // 线上
                ddje_xs_value=res.data.xianshang;
                // 赋值人数
                // var strong_xs1=0;
                // for( var i in ddje_xs_value){
                //     strong_xs1= parseFloat(strong_xs1+ddje_xs_value[i]);
                // }
                
                // $('#ddje_xs_strong').html(strong_xs1.toFixed(2) ); 
                // 线下  
                ddje_xx_value=res.data.xianxia;
                // var strong_xx1=0;
                // for( var i in ddje_xx_value){
                //     strong_xx1= parseFloat(strong_xx1+ddje_xx_value[i]);
                // }
                // $('#ddje_xx_strong').html(strong_xx1.toFixed(2) );
                
                ddje();            
            } else {
                layer.msg(res.msg);
            }
        }
    });
    }
    function ddje(){
    var echheapline = [], heapline = [{
        tooltip: {
            trigger: 'axis'
        },
        grid: {
            containLabel: true
        },
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
            data: ['线上订单金额数', '线下订单金额数'],
            x: 'left'
        },   
        xAxis: {
            type: 'category',
            data: ddje_category,
            splitLine: {
                show: false // x轴的网格隐藏
            },
        },
        yAxis: {
            type: 'value',
        },
        series: [
        {
            name: '线上订单金额数',
            type: 'line',
            data: ddje_xs_value
        },
        {
            name: '线下订单金额数',
            type: 'line',
            data: ddje_xx_value
        }
        ]
    }]
    ,elemheapline = $('#LAY-index-omoney').children('div')
    ,renderheapline = function(index){
        echheapline[index] = echarts.init(elemheapline[index], layui.echartsTheme);
        echheapline[index].setOption(heapline[index]);
        window.onresize = echheapline[index].resize;
    };
    if(!elemheapline[0]) return;
    renderheapline(0);
    }

    // 退换货统计
    var xs_thtj_category;
    var xs_thtj_jy_data;
    var xs_thtj_th_data;
    var xx_thtj_category;
    var xx_thtj_jy_data;
    var xx_thtj_th_data;
    thtj_ajax(); 
    //监听搜索 
    form.on('submit(thtj_search)', function(data){
        // console.log(data.field);
        thtj_ajax(data.field);
    });
    // 日-周-月-季-年
    $('#thtj_data').find('.layui-btn').click(function(){
        var lay_type= $(this).attr('lay-type');
        // console.log(lay_type);
        thtj_ajax( {type:lay_type} );
    })
    function thtj_ajax(data_s){
    admin.req({
        url: layui.setter.baseUrl + 'admin/StatisticStransaction/tuihuo_k' 
        ,data: data_s
        ,type: 'get'
        ,success: function (res) {
            if (res.code == 1) {
                // 线上
                xs_thtj_category=res.data.xianshang.category;
                xs_thtj_jy_data=res.data.xianshang.jiaoyi;
                xs_thtj_th_data=res.data.xianshang.tuihuo;

                // 线下 
                xx_thtj_category=res.data.xianxia.category;
                xx_thtj_jy_data=res.data.xianxia.jiaoyi;
                xx_thtj_th_data=res.data.xianxia.tuihuo; 
                
                
                xs_thtj();            
                xx_thtj();            
            } else {
                layer.msg(res.msg);
            }
        }
    });
    }
    function xs_thtj(){
    var echheapline = [], heapline = [{
        tooltip : {
            trigger: 'axis',
            axisPointer : {            // 坐标轴指示器，坐标轴触发有效
                type : 'shadow'        // 默认为直线，可选为：'line' | 'shadow'
            }
        }, 
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
            data:['线上交易笔数','线上退换货笔数'],
            x:"left"
        },
        xAxis:[{
            type: 'category',
            data: xs_thtj_category,
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
            name: '线上交易笔数',
            type: 'bar',
            data: xs_thtj_jy_data
        },  
        {
            name: '线上退换货笔数',
            type: 'bar',
            data: xs_thtj_th_data
        }]
    }]
    ,elemheapline = $('#LAY-index-refund_online').children('div')
    ,renderheapline = function(index){
        echheapline[index] = echarts.init(elemheapline[index], layui.echartsTheme);
        echheapline[index].setOption(heapline[index]);
        window.onresize = echheapline[index].resize;
    };
    if(!elemheapline[0]) return;
    renderheapline(0);
    }
    function xx_thtj(){
    var echheapline = [], heapline = [{
        tooltip : {
            trigger: 'axis',
            axisPointer : {            // 坐标轴指示器，坐标轴触发有效
                type : 'shadow'        // 默认为直线，可选为：'line' | 'shadow'
            }
        },         
        color: ["#ff6c87","#ff9e00"],
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
            data:['线下交易笔数','线下退换货笔数'],
            x:"left"
        },
        xAxis:[{
            type: 'category',
            data: xx_thtj_category,
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
            name: '线下交易笔数',
            type: 'bar',
            data: xx_thtj_jy_data
        },  
        {
            name: '线下退换货笔数',
            type: 'bar',
            data: xx_thtj_th_data
        }]
    }]
    ,elemheapline = $('#LAY-index-refund_offline').children('div')
    ,renderheapline = function(index){
        echheapline[index] = echarts.init(elemheapline[index], layui.echartsTheme);
        echheapline[index].setOption(heapline[index]);
        window.onresize = echheapline[index].resize;
    };
    if(!elemheapline[0]) return;
    renderheapline(0);
    }

     // 商品线上销售数量统计
    var spzl_category;
    var spzl_value;
    spzl_ajax(); 
    //监听搜索 
    form.on('submit(spzl_search)', function(data){
        // console.log(data.field);
        spzl_ajax(data.field);
    });
    // 日-周-月-季-年
    $('#spzl_data').find('.layui-btn').click(function(){
        var lay_type= $(this).attr('lay-type');
        // console.log(lay_type);
        spzl_ajax( {type:lay_type} );
    })
    function spzl_ajax(data_s){
    admin.req({
        url: layui.setter.baseUrl + 'admin/StatisticStransaction/zhonglei_k' 
        ,data: data_s
        ,type: 'get'
        ,success: function (res) {
            if (res.code == 1) {
                spzl_category=res.data.category;
                spzl_value=res.data.value;
                spzl(); 
            } else {
                layer.msg(res.msg);
            }
        }
    });
    }
    function spzl(){
    var echheapline = [], heapline = [{
        tooltip : {
            trigger: 'axis',
            axisPointer : {            // 坐标轴指示器，坐标轴触发有效
                type : 'shadow'        // 默认为直线，可选为：'line' | 'shadow'
            }
        }, 
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
            data:['商品种类'],
            x:"left"
        },
        xAxis:[{
            type: 'category',
            data: spzl_category,
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
            name: '商品种类',
            type: 'bar',
            data: spzl_value
        }]
    }]
    ,elemheapline = $('#LAY-index-species').children('div')
    ,renderheapline = function(index){
        echheapline[index] = echarts.init(elemheapline[index], layui.echartsTheme);
        echheapline[index].setOption(heapline[index]);
        window.onresize = echheapline[index].resize;
    };
    if(!elemheapline[0]) return;
    renderheapline(0);
    }
    
    // 商品销售数量统计
    var spxs_xs_category;
    var spxs_xs_value;
    spxs_xs_ajax(); 
    //监听搜索 
    form.on('submit(spxs_xs_search)', function(data){
        // console.log(data.field);
        spxs_xs_ajax(data.field);
    });
    // 日-周-月-季-年
    $('#spxs_xs_data').find('.layui-btn').click(function(){
        var lay_type= $(this).attr('lay-type');
        // console.log(lay_type);
        spxs_xs_ajax( {type:lay_type} );
    })
    function spxs_xs_ajax(data_s){
    admin.req({
        url: layui.setter.baseUrl + 'admin/StatisticStransaction/xianshang_k' 
        ,data: data_s
        ,type: 'get'
        ,success: function (res) {
            if (res.code == 1) {
                spxs_xs_category=res.data.category;
                spxs_xs_value=res.data.value;
                spxs_xs(); 
            } else {
                layer.msg(res.msg);
            }
        }
    });
    }
    function spxs_xs(){
    var echheapline = [], heapline = [{
        tooltip : {
            trigger: 'axis',
            axisPointer : {            // 坐标轴指示器，坐标轴触发有效
                type : 'shadow'        // 默认为直线，可选为：'line' | 'shadow'
            }
        }, 
        color: ["#ffa544"],
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
            data:['商品数量'],
            x:"left"
        },
        xAxis:[{
            type: 'category',
            data: spxs_xs_category,
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
            name: '商品数量',
            type: 'bar',
            data: spxs_xs_value
        }]
    }]
    ,elemheapline = $('#LAY-index-spe_num').children('div')
    ,renderheapline = function(index){
        echheapline[index] = echarts.init(elemheapline[index], layui.echartsTheme);
        echheapline[index].setOption(heapline[index]);
        window.onresize = echheapline[index].resize;
    };
    if(!elemheapline[0]) return;
    renderheapline(0);
    }
   
    // 商品线下销售数量统计
    var spxx_xs_category;
    var spxx_xs_value;
    spxx_xs_ajax(); 
    //监听搜索 
    form.on('submit(spxx_xs_search)', function(data){
        // console.log(data.field);
        spxx_xs_ajax(data.field);
    });
    // 日-周-月-季-年
    $('#spxx_xs_data').find('.layui-btn').click(function(){
        var lay_type= $(this).attr('lay-type');
        // console.log(lay_type);
        spxx_xs_ajax( {type:lay_type} );
    })
    function spxx_xs_ajax(data_s){
    admin.req({
        url: layui.setter.baseUrl + 'admin/StatisticStransaction/xianxia_k' 
        ,data: data_s
        ,type: 'get'
        ,success: function (res) {
            if (res.code == 1) {
                spxx_xs_category=res.data.category;
                spxx_xs_value=res.data.value;
                spxx_xs(); 
            } else {
                layer.msg(res.msg);
            }
        }
    });
    }
    function spxx_xs(){
    var echheapline = [], heapline = [{
        tooltip : {
            trigger: 'axis',
            axisPointer : {            // 坐标轴指示器，坐标轴触发有效
                type : 'shadow'        // 默认为直线，可选为：'line' | 'shadow'
            }
        }, 
        color: ["#f2637b"],
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
            data:['商品金额'],
            x:"left"
        },
        xAxis:[{
            type: 'category',
            data: spxx_xs_category,
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
            name: '商品金额',
            type: 'bar',
            data: spxx_xs_value
        }]
    }]
    ,elemheapline = $('#LAY-index-spe_money').children('div')
    ,renderheapline = function(index){
        echheapline[index] = echarts.init(elemheapline[index], layui.echartsTheme);
        echheapline[index].setOption(heapline[index]);
        window.onresize = echheapline[index].resize;
    };
    if(!elemheapline[0]) return;
    renderheapline(0);
    }
    


});

});
  
exports('statistics_echarts', {})
});
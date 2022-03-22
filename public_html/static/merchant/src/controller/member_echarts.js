/**

 @Name：layuiAdmin Echarts集成
 @Author：star1029
 @Site：http://www.layui.com/merchant/
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
layui.use(['echarts','element','table'], function(){
    var $ = layui.$
    ,element = layui.element
    ,table = layui.table
    ,echarts = layui.echarts;
    
    // 客户概括及趋势
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
            data:['累积粉丝数','成交客户数'],
            x:"left"
        },
        xAxis:[{
            type: 'category',
            data: ['2019-06-27','2019-06-28','2019-06-29','2019-06-30','2019-07-01','2019-07-02','2019-07-03','2019-07-04','2019-07-05','2019-07-06','2019-07-07','2019-07-08','2019-07-09','2019-07-10','2019-07-11','2019-07-12','2019-07-13','2019-07-14','2019-07-15','2019-07-16','2019-07-17','2019-07-18','2019-07-19','2019-07-20','2019-07-21','2019-07-22','2019-07-23','2019-07-24','2019-07-25','2019-07-26'],
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
            name: '累积粉丝数',
            type: 'line',
            data: [330,0,0,330,329,329,329,331,331,331,331,0,331,331,334,336,337,337,337,338,338,338,338,337,337,337,337,336,337,337]
        },  
        {
            name: '成交客户数',
            type: 'line',
            data:[0,0,0,0,0,0,1,0,1,0,0,0,1,0,2,0,0,0,0,0,2,1,1,1,1,0,0,0,3,1]
        }]
    }]
    ,elemheapline = $('#LAY-index-cust_profile').children('div')
    ,renderheapline = function(index){
        echheapline[index] = echarts.init(elemheapline[index], layui.echartsTheme);
        echheapline[index].setOption(heapline[index]);
        window.onresize = echheapline[index].resize;
    };
    if(!elemheapline[0]) return;
    renderheapline(0);
    // 粉丝活跃
    var echheapline = [], heapline = [{
        legend: {
            orient: 'vertical',
            x: 'left',
            data: ['访问粉丝数', '无访问粉丝数']
        },
        color: ["#4580FD", "#4ECEEB"],
        tooltip: {
            trigger: 'item',
            formatter: " {b} <br/> 人数：{c} <br/> 占比：{d}%",
        },
        series: [{
            type: 'pie',
            radius: ['30%', '60%'], // 第一个参数是内圆半径，第二个参数是外圆半径，相对饼图的宿主div大小
            data: [{
                value: 0.89,
                name: '访问粉丝数'
            }, {
                value: 99.11,
                name: '无访问粉丝数'
            }]
        }]
    }]
    ,elemheapline = $('#LAY-index-fans1').children('div')
    ,renderheapline = function(index){
        echheapline[index] = echarts.init(elemheapline[index], layui.echartsTheme);
        echheapline[index].setOption(heapline[index]);
        window.onresize = echheapline[index].resize;
    };
    if(!elemheapline[0]) return;
    renderheapline(0);

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
        xAxis:[{
            type: 'category',
            data: ['领券粉丝数','加购粉丝数','成交粉丝数'],
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
        series: [{
            name: "人数",
            type: 'bar',
            barWidth: '80',
            data: [1, 2, 3]
        }]
    }]
    ,elemheapline = $('#LAY-index-fans2').children('div')
    ,renderheapline = function(index){
        echheapline[index] = echarts.init(elemheapline[index], layui.echartsTheme);
        echheapline[index].setOption(heapline[index]);
        window.onresize = echheapline[index].resize;
    };
    if(!elemheapline[0]) return;
    renderheapline(0);
    // 会员活跃
    var echheapline = [], heapline = [{
        legend: {
            orient: 'vertical',
            x: 'left',
            data: ['访问会员数', '无访问会员数']
        },
        color: ["#4580FD", "#4ECEEB"],
        tooltip: {
            trigger: 'item',
            formatter: " {b} <br/> 人数：{c} <br/> 占比：{d}%",
        },
        series: [{
            type: 'pie',
            radius: ['30%', '60%'], // 第一个参数是内圆半径，第二个参数是外圆半径，相对饼图的宿主div大小
            data: [{
                value: 0.89,
                name: '访问会员数'
            }, {
                value: 99.11,
                name: '无访问会员数'
            }]
        }]
    }]
    ,elemheapline = $('#LAY-index-member_go1').children('div')
    ,renderheapline = function(index){
        echheapline[index] = echarts.init(elemheapline[index], layui.echartsTheme);
        echheapline[index].setOption(heapline[index]);
        window.onresize = echheapline[index].resize;
    };
    if(!elemheapline[0]) return;
    renderheapline(0);

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
        xAxis:[{
            type: 'category',
            data: ['领券会员数','加购会员数','成交会员数'],
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
        series: [{
            name: "人数",
            type: 'bar',
            barWidth: '80',
            data: [1, 0, 0]
        }]
    }]
    ,elemheapline = $('#LAY-index-member_go2').children('div')
    ,renderheapline = function(index){
        echheapline[index] = echarts.init(elemheapline[index], layui.echartsTheme);
        echheapline[index].setOption(heapline[index]);
        window.onresize = echheapline[index].resize;
    };
    if(!elemheapline[0]) return;
    renderheapline(0);
    // 成交客户分析
    var echheapline = [], heapline = [{
        tooltip : {
            trigger: 'axis',
            axisPointer : {            // 坐标轴指示器，坐标轴触发有效
                type : 'line'        // 默认为直线，可选为：'line' | 'shadow'
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
            data:['新成交客户付款金额','老成交客户付款金额'],
            x:"left"
        },
        xAxis:[{
            type: 'category',
            data: ["2019-06-27","2019-06-28","2019-06-29","2019-06-30","2019-07-01","2019-07-02","2019-07-03","2019-07-04","2019-07-05","2019-07-06","2019-07-07","2019-07-08","2019-07-09","2019-07-10","2019-07-11","2019-07-12","2019-07-13","2019-07-14","2019-07-15","2019-07-16","2019-07-17","2019-07-18","2019-07-19","2019-07-20","2019-07-21","2019-07-22","2019-07-23","2019-07-24","2019-07-25","2019-07-26"],
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
            name: '新成交客户付款金额',
            type: 'line',
            barWidth: 40, 
            data: [0,0,0,0,0,0,0,0,399,0,0,0,399,0,1594.5,0,0,0,0,0,100,236,248,0,100,0,0,0,196,98],
        },
        {
            name: '老成交客户付款金额',
            type: 'line',
            barWidth: 40, 
            data: [0,0,0,0,0,0,2400,0,0,0,0,0,0,0,0,0,0,0,0,0,2925,0,0,99,0,0,0,0,2755,0],
        }]
    }]
    ,elemheapline = $('#LAY-index-cust_deal').children('div')
    ,renderheapline = function(index){
        echheapline[index] = echarts.init(elemheapline[index], layui.echartsTheme);
        echheapline[index].setOption(heapline[index]);
        window.onresize = echheapline[index].resize;
    };
    if(!elemheapline[0]) return;
    renderheapline(0);
    var echheapline = [], heapline = [{
        tooltip : {
            trigger: 'axis',
            axisPointer : {            // 坐标轴指示器，坐标轴触发有效
                type : 'shadow'        // 默认为直线，可选为：'line' | 'shadow'
            }
        }, 
        legend: {
            data: ['新成交客户付款金额', '老成交客户付款金额'],
            x:"left"
        },
        color:['#269fff','#ff8313'],
        xAxis:[{
            type: 'category',
            data: ['普通客户', '微信粉丝', '店铺会员'],
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
        series: [{
            name: "新成交客户付款金额",
            type: 'bar',
            data: [98.00, 0.00, 98.00]
        },{
            name: "老成交客户付款金额",
            type: 'bar',
            data: [0.00, 0.00, 0.00]
        }]
    }]
    ,elemheapline = $('#LAY-index-cust_deal1').children('div')
    ,renderheapline = function(index){
        echheapline[index] = echarts.init(elemheapline[index], layui.echartsTheme);
        echheapline[index].setOption(heapline[index]);
        window.onresize = echheapline[index].resize;
    };
    if(!elemheapline[0]) return;
    renderheapline(0);
    var echheapline = [], heapline = [{
        tooltip : {
            trigger: 'axis',
            axisPointer : {            // 坐标轴指示器，坐标轴触发有效
                type : 'line'        // 默认为直线，可选为：'line' | 'shadow'
            }
        },
        legend: {
            data:['客户周复购率'],
            x:"left"
        },
        color: ['#4f5fff'],
        xAxis:[{
            type: 'category',
            data: ["06-01~06-07","06-08~06-14","06-15~06-21","06-22~06-28"],
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
        series: [{
            name: '客户周复购率',
            type: 'line',
            barWidth: 40, 
            data: [0.5,1,0.4,0.29],
        }]
    }]
    ,elemheapline = $('#LAY-index-cust_deal2').children('div')
    ,renderheapline = function(index){
        echheapline[index] = echarts.init(elemheapline[index], layui.echartsTheme);
        echheapline[index].setOption(heapline[index]);
        window.onresize = echheapline[index].resize;
    };
    if(!elemheapline[0]) return;
    renderheapline(0);
    // 微信粉丝
    var echheapline = [], heapline = [{
        tooltip: {
            trigger: 'axis'
        },
        legend: {
            data: ['累积粉丝数', '净增粉丝数', '访问粉丝数'],
            x:"left"
        },   
        xAxis: [{
            type: 'category',
            name: "",
            data: ['06-25','06-27','06-29','07-01','07-03','07-05','07-07','07-09','07-11','07-13','07-15','07-17','07-19','07-21','07-23','07-25'],
            splitLine: {
                show: false // x轴的网格隐藏
            },
        }],
        yAxis:[{
            type: 'value',
        }] ,
        series: [
        {
            name: '累积粉丝数',
            type: 'line',
            data: [332,330,0,329,329,331,331,331,334,337,337,338,338,337,337,337]
        },
        {
            name: '净增粉丝数',
            type: 'line',
            data: [0,0,0,0,0,0,0,0,4,2,0,0,0,0,0,1]
        },
        {
            name: '访问粉丝数',
            type: 'line',
            data: [0,0,1,0,2,3,0,3,5,3,2,1,1,1,1,2]
        }
        ]
    }]
    ,elemheapline = $('#LAY-index-wechat').children('div')
    ,renderheapline = function(index){
        echheapline[index] = echarts.init(elemheapline[index], layui.echartsTheme);
        echheapline[index].setOption(heapline[index]);
        window.onresize = echheapline[index].resize;
    };
    if(!elemheapline[0]) return;
    renderheapline(0);
    // 店铺会员
    var echheapline = [], heapline = [{
        tooltip: {
            trigger: 'axis'
        },
        legend: {
            data: ['累积会员数', '新增会员数', '成交会员数'],
            x:"left"
        },   
        xAxis: [{
            type: 'category',
            name: "",
            data: ['06-25','06-27','06-29','07-01','07-03','07-05','07-07','07-09','07-11','07-13','07-15','07-17','07-19','07-21','07-23','07-25'],
            splitLine: {
                show: false // x轴的网格隐藏
            },
        }],
        yAxis:[{
            type: 'value',
        }] ,
        series: [
        {
            name: '累积会员数',
            type: 'line',
            data: [1296,1300,1302,1303,1306,1309,1309,1310,1321,1325,1325,1325,1327,1329,1334,1336]
        },
        {
            name: '新增会员数',
            type: 'line',
            data: [0,2,0,1,0,2,0,1,6,2,0,0,1,2,3,3]
        },
        {
            name: '成交会员数',
            type: 'line',
            data: [0,0,0,0,1,1,0,1,2,0,0,2,1,1,0,3]
        }
        ]
    }]
    ,elemheapline = $('#LAY-index-goods').children('div')
    ,renderheapline = function(index){
        echheapline[index] = echarts.init(elemheapline[index], layui.echartsTheme);
        echheapline[index].setOption(heapline[index]);
        window.onresize = echheapline[index].resize;
    };
    if(!elemheapline[0]) return;
    renderheapline(0);

    
    
    
    
element.on('tab(component-tabs-brief)', function(data){    
    table.resize();   

     // 新增会员/粉丝/分销商统计
     var echheapline = [], heapline = [{
        tooltip : {
            trigger: 'axis',
            axisPointer : {            // 坐标轴指示器，坐标轴触发有效
                type : 'line'        // 默认为直线，可选为：'line' | 'shadow'
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
        color:['#4580fd','#f0761f','#ff6c87'],
        legend: {
            data: ['新增会员数量', '新增粉丝数量','新增分销商数量'],
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
        series: [{
            name: '新增会员数量',
            type: 'line',
            data: [ 1,1,0,1,3,0,2,2,0,0,0,1,5,6,2,2,0,0,0,0,1,1,0,2,2,2,0,3,1,2],
        }, {
            name: '新增粉丝数量',
            type: 'line',
            data: [0,0,0,0,0,0,2,0,0,0,0,0,0,4,2,2,0,0,1,0,0,0,0,0,0,0,0,1,0,0],
        },
        {
            name: '新增分销商数量',
            type: 'line',
            data: [ 0,1,0,0,0,0,0,1,0,0,0,1,1,6,0,0,0,0,0,2,0,2,0,0,0,0,1,0,1,0],
        }]
    }]
    ,elemheapline = $('#LAY-index-new_number').children('div')
    ,renderheapline = function(index){
        echheapline[index] = echarts.init(elemheapline[index], layui.echartsTheme);
        echheapline[index].setOption(heapline[index]);
        window.onresize = echheapline[index].resize;
    };
    if(!elemheapline[0]) return;
    renderheapline(0);

    // 饼图统计
    var echheapline = [], heapline = [{       
        color: ["#f15c80", "#4381e6"],
        tooltip: {
            trigger: 'item',
            formatter: " {b} <br/> 比例：{c}%",
        },
        series: [{
            type: 'pie',
            name: "比例",
            data: [{
                name: '粉丝会员数量',
                value: 25.2
            },
            {
                name: '非粉丝会员数量',
                value: 74.8
            }]
        }]
    }]
    ,elemheapline = $('#LAY-index-pie_chart1').children('div')
    ,renderheapline = function(index){
        echheapline[index] = echarts.init(elemheapline[index], layui.echartsTheme);
        echheapline[index].setOption(heapline[index]);
        window.onresize = echheapline[index].resize;
    };
    if(!elemheapline[0]) return;
    renderheapline(0);

    var echheapline = [], heapline = [{       
        color: ["#e24ac4", "#4381e6"],
        tooltip: {
            trigger: 'item',
            formatter: " {b} <br/> 比例：{c}%",
        },
        series: [{
            type: 'pie',
            name: "比例",
            data: [{
                name: '分销商数量',
                value: 60.2
            },
            {
                name: '非分销商数量',
                value: 39.8
            }]
        }]
    }]
    ,elemheapline = $('#LAY-index-pie_chart2').children('div')
    ,renderheapline = function(index){
        echheapline[index] = echarts.init(elemheapline[index], layui.echartsTheme);
        echheapline[index].setOption(heapline[index]);
        window.onresize = echheapline[index].resize;
    };
    if(!elemheapline[0]) return;
    renderheapline(0);

    var echheapline = [], heapline = [{       
        color: ["#ffba00", "#4381e6"],
        tooltip: {
            trigger: 'item',
            formatter: " {b} <br/> 比例：{c}%",
        },
        series: [{
            type: 'pie',
            name: "比例",
            data: [{
                name: '多次购物会员数量',
                value: 4.0
            },
            {
                name: '非多次购物会员数量',
                value: 96.1
            }]
        }]
    }]
    ,elemheapline = $('#LAY-index-pie_chart3').children('div')
    ,renderheapline = function(index){
        echheapline[index] = echarts.init(elemheapline[index], layui.echartsTheme);
        echheapline[index].setOption(heapline[index]);
        window.onresize = echheapline[index].resize;
    };
    if(!elemheapline[0]) return;
    renderheapline(0);

    var echheapline = [], heapline = [{       
        color: ["#ff9232", "#4381e6"],
        tooltip: {
            trigger: 'item',
            formatter: " {b} <br/> 比例：{c}%",
        },
        series: [{
            type: 'pie',
            name: "比例",
            data: [{
                name: '节点商数量',
                value: 5.4
            },
            {
                name: '非节点商数量',
                value: 94.6
            }]
        }]
    }]
    ,elemheapline = $('#LAY-index-pie_chart4').children('div')
    ,renderheapline = function(index){
        echheapline[index] = echarts.init(elemheapline[index], layui.echartsTheme);
        echheapline[index].setOption(heapline[index]);
        window.onresize = echheapline[index].resize;
    };
    if(!elemheapline[0]) return;
    renderheapline(0);



    // 购物会员统计
    var echheapline = [], heapline = [{
        tooltip : {
            trigger: 'axis',
            axisPointer : {            // 坐标轴指示器，坐标轴触发有效
                type : 'line'        // 默认为直线，可选为：'line' | 'shadow'
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
            data:['购物会员数量'],
            x:"left"
        },
        color: ['#4580fd'],
        xAxis:[{
            type: 'category',
            data: ["2019-06-30","2019-07-01","2019-07-02","2019-07-03","2019-07-04","2019-07-05","2019-07-06","2019-07-07","2019-07-08","2019-07-09","2019-07-10","2019-07-11","2019-07-12","2019-07-13","2019-07-14","2019-07-15","2019-07-16","2019-07-17","2019-07-18","2019-07-19","2019-07-20","2019-07-21","2019-07-22","2019-07-23","2019-07-24","2019-07-25","2019-07-26","2019-07-27","2019-07-28","2019-07-29"],
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
        series: [{
            name: '购物会员数量',
            type: 'line',
            data: [ 0,0,0,1,0,1,0,0,0,1,0,2,0,0,0,0,0,2,1,1,1,1,0,0,0,3,1,1,0,0],
        }]
    }]
    ,elemheapline = $('#LAY-index-shopping').children('div')
    ,renderheapline = function(index){
        echheapline[index] = echarts.init(elemheapline[index], layui.echartsTheme);
        echheapline[index].setOption(heapline[index]);
        window.onresize = echheapline[index].resize;
    };
    if(!elemheapline[0]) return;
    renderheapline(0);

    // 分销商业务人数和提现人数统计
    var echheapline = [], heapline = [{
        tooltip : {
            trigger: 'axis',
            axisPointer : {            // 坐标轴指示器，坐标轴触发有效
                type : 'line'        // 默认为直线，可选为：'line' | 'shadow'
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
            data: ['分销商业务数量', '分销商提现数量'],
            x:"left"
        },
        color: ['#4580FD','#FF6C87'],
        xAxis:[{
            type: 'category',
            data: ["2019-06-30","2019-07-01","2019-07-02","2019-07-03","2019-07-04","2019-07-05","2019-07-06","2019-07-07","2019-07-08","2019-07-09","2019-07-10","2019-07-11","2019-07-12","2019-07-13","2019-07-14","2019-07-15","2019-07-16","2019-07-17","2019-07-18","2019-07-19","2019-07-20","2019-07-21","2019-07-22","2019-07-23","2019-07-24","2019-07-25","2019-07-26","2019-07-27","2019-07-28","2019-07-29"],
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
        series: [{
            name: '分销商业务数量',
            type: 'line',
            data: [ 0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],
        },{
            name: '分销商提现数量',
            type: 'line',
            data: [ 0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],
        }]
    }]
    ,elemheapline = $('#LAY-index-distrib').children('div')
    ,renderheapline = function(index){
        echheapline[index] = echarts.init(elemheapline[index], layui.echartsTheme);
        echheapline[index].setOption(heapline[index]);
        window.onresize = echheapline[index].resize;
    };
    if(!elemheapline[0]) return;
    renderheapline(0);

    // 分销商业务佣金和提现金额统计
    var echheapline = [], heapline = [{
        tooltip : {
            trigger: 'axis',
            axisPointer : {            // 坐标轴指示器，坐标轴触发有效
                type : 'line'        // 默认为直线，可选为：'line' | 'shadow'
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
            data: ['分销商业务佣金', '分销商提现佣金'],
            x:"left"
        },
        color: ['#48D63E','#ff9e00'],
        xAxis:[{
            type: 'category',
            data: ["2019-06-30","2019-07-01","2019-07-02","2019-07-03","2019-07-04","2019-07-05","2019-07-06","2019-07-07","2019-07-08","2019-07-09","2019-07-10","2019-07-11","2019-07-12","2019-07-13","2019-07-14","2019-07-15","2019-07-16","2019-07-17","2019-07-18","2019-07-19","2019-07-20","2019-07-21","2019-07-22","2019-07-23","2019-07-24","2019-07-25","2019-07-26","2019-07-27","2019-07-28","2019-07-29"],
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
        series: [{
            name: '分销商业务佣金',
            type: 'line',
            data: [  0,0,0,110,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],
        },{
            name: '分销商提现佣金',
            type: 'line',
            data: [ 0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],
        }]
    }]
    ,elemheapline = $('#LAY-index-distrib_money').children('div')
    ,renderheapline = function(index){
        echheapline[index] = echarts.init(elemheapline[index], layui.echartsTheme);
        echheapline[index].setOption(heapline[index]);
        window.onresize = echheapline[index].resize;
    };
    if(!elemheapline[0]) return;
    renderheapline(0);

    // 饼图统计
    var echheapline = [], heapline = [{       
        color: ["#7cb5ec", "#434348"],
        tooltip: {
            trigger: 'item',
            formatter: " {b} <br/> 比例：{c}%",
        },
        series: [{
            type: 'pie',
            name: "比例",
            data: [{
                name: '购物会员数量',
                value: 18.9
            },{
                name: '非购物会员数量',
                value: 81.2
            }]
        }]
    }]
    ,elemheapline = $('#LAY-index-pie_member1').children('div')
    ,renderheapline = function(index){
        echheapline[index] = echarts.init(elemheapline[index], layui.echartsTheme);
        echheapline[index].setOption(heapline[index]);
        window.onresize = echheapline[index].resize;
    };
    if(!elemheapline[0]) return;
    renderheapline(0);

    var echheapline = [], heapline = [{       
        color: ["#7cb5ec", "#434348"],
        tooltip: {
            trigger: 'item',
            formatter: " {b} <br/> 比例：{c}%",
        },
        series: [{
            type: 'pie',
            name: "比例",
            data: [{
                name: '重复购物会员数量',
                value: 9.6
            },{
                name: '非重复购物会员数量',
                value: 90.4
            }]
        }]
    }]
    ,elemheapline = $('#LAY-index-pie_member2').children('div')
    ,renderheapline = function(index){
        echheapline[index] = echarts.init(elemheapline[index], layui.echartsTheme);
        echheapline[index].setOption(heapline[index]);
        window.onresize = echheapline[index].resize;
    };
    if(!elemheapline[0]) return;
    renderheapline(0);

    // 地图
    var echplat = [], plat = [{      
        tooltip : {
            trigger: 'item',
            formatter: '{b}：{c}'
        },
        toolbox: {
            show: true,
            orient: 'vertical',
            top: 'center',
            feature: {            
                dataView: {
                    show: true,
                    readOnly: true
                },           
                restore: {
                    show: true
                },
                saveAsImage: {
                    show: true
                }
            }
        },
        dataRange: {
            orient: 'horizontal',
            min: 0,
            max: 200,
            text:['High','Low'],   
            realtime: false,
            calculable: true,  
        },
        series : [
        {
            name: '流量地域分布',
            type: 'map',
            mapType: 'china',
            mapLocation: {
                x: 'center'
            },
            selectedMode : 'multiple',
            itemStyle:{
                normal:{label:{show:true}},
                emphasis:{label:{show:true}}
            },
            data:[
                {name:'西藏', value:0},
                {name:'青海', value:0},
                {name:'宁夏', value:2},
                {name:'海南', value:13},
                {name:'甘肃', value:2},
                {name:'贵州', value:6},
                {name:'新疆', value:4},
                {name:'云南', value:19},
                {name:'重庆', value:19},
                {name:'吉林', value:8},
                {name:'山西', value:22},
                {name:'天津', value:4},
                {name:'江西', value:22},
                {name:'广西', value:10},
                {name:'陕西', value:20},
                {name:'黑龙江', value:14},
                {name:'内蒙古', value:8},
                {name:'安徽', value:31},
                {name:'北京', value:37},
                {name:'福建', value:46},
                {name:'上海', value:34},
                {name:'湖北', value:79},
                {name:'湖南', value:28},
                {name:'四川', value:54},
                {name:'辽宁', value:20},
                {name:'河北', value:36},
                {name:'河南', value:35},
                {name:'浙江', value:237},
                {name:'山东', value:22},
                {name:'江苏', value:60},
                {name:'广东', value:120}
            ]
        }]
    }]
    ,elemplat = $('#LAY-index-map').children('div')
    ,renderplat = function(index){
        echplat[index] = echarts.init(elemplat[index], layui.echartsTheme);
        echplat[index].setOption(plat[index]);
        window.onresize = echplat[index].resize;
    };   
    if(!elemplat[0]) return;
    renderplat(0);

    // 性别分布比例
    var echheapline = [], heapline = [{       
        color: ["#7cb5ec", "#434348", "#90ed7d"],
        tooltip: {
            trigger: 'item',
            formatter: " {b} <br/> 比例：{c}%",
        },
        series: [{
            type: 'pie',
            name: "比例",
            data: [{
                name: '未知',
                value: 12.4
            },{
                name: '男',
                value: 55.2
            },{            
                name: '女',
                value: 32.4
            }]
        }]
    }]
    ,elemheapline = $('#LAY-index-sex').children('div')
    ,renderheapline = function(index){
        echheapline[index] = echarts.init(elemheapline[index], layui.echartsTheme);
        echheapline[index].setOption(heapline[index]);
        window.onresize = echheapline[index].resize;
    };
    if(!elemheapline[0]) return;
    renderheapline(0);
  
    
    

});
   
    
});
    
exports('member_echarts', {})
});
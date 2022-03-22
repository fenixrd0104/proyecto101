/**

 @Name：layuiAdmin Echarts集成
 @Author：star1029
 @Site：http://www.layui.com/admin/
 @License：GPL-2
    
 */
layui.define(function(exports){
    //区块轮播切换
    layui.use(['admin', 'laydate'], function(){
        var $ = layui.$
        ,admin = layui.admin
        ,laydate = layui.laydate
        ,element = layui.element
        ,router = layui.router();  

    //日期时间选择器

    /* ----------交易数据--------- */
    laydate.render({
        elem: '#test-laydate-type-datetime-start'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-end'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-month-start'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-month-end'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-screen'
        ,value: '2019-07-25'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-begin'
        ,value: '2019-07-25'
    });

    laydate.render({
        elem: '#test-laydate-type-datetime-month-start-goods'
        ,value: '2019-07-25'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-month-end-goods'
        ,value: '2019-07-25'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-start-pin'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-end-pin'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-start-list'
        ,value: '2019-07-19'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-end-list'
        ,value: '2019-07-25'
    });

    laydate.render({
        elem: '#test-laydate-type-datetime-month-start-order'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-month-end-order'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-month-start-omoney'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-month-end-omoney'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-month-start-refund'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-month-end-refund'
    });

    laydate.render({
        elem: '#test-laydate-type-datetime-month-start-species'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-month-end-species'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-month-start-spe_num'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-month-end-spe_num'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-month-start-spe_num1'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-month-end-spe_num1'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-month-start-spe_money'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-month-end-spe_money'
    });
    


    /* ----------财务数据---------- */
    laydate.render({
        elem: '#test-laydate-type-datetime-month-start-withd_people'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-month-end-withd_people'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-month-start-withd_number'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-month-end-withd_number'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-month-start-withd_money'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-month-end-withd_money'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-month-start-withd_capita'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-month-end-withd_capita'
    });

    laydate.render({
        elem: '#test-laydate-type-datetime-month-start-topup_number'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-month-end-topup_number'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-month-start-topup_money'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-month-end-topup_money'
    });



    /* ---------会员数据---------- */
    laydate.render({
        elem: '#test-laydate-type-datetime-month-start-cust_profile'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-month-end-cust_profile'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-month-start-wx_fans'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-month-end-wx_fans'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-month-start-fans'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-month-end-fans'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-month-start-member_go'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-month-end-member_go'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-month-start-cust_deal'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-month-end-cust_deal'
    });

    /* 会员分析 */
    // 会员增长
    laydate.render({
        elem: '#test-laydate-type-datetime-month-start-new_number'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-month-end-new_number'
    });
    // 会员业务
    laydate.render({
        elem: '#test-laydate-type-datetime-month-start-shopping'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-month-end-shopping'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-month-start-distrib'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-month-end-distrib'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-month-start-distrib_money'
    });
    laydate.render({
        elem: '#test-laydate-type-datetime-month-end-distrib_money'
    });



    
    });  
    exports('statistics_laydate', {})
    });
layui.define(['jquery'],function(exports) {
    var $ = layui.jquery;
    function change_data(myobj){
        var top='',tr='',li='';
        for(var i in myobj){
            li+='<li style="border:none;width:33.33%;padding:10px 0;list-style:none;float:left;">'+i+'：'+myobj[i]+'</li>'
        }

        return top="<div><ul style='overflow: hidden;'>"+li+"</ul></div>";
    }
    var obj={
        myprint:function(myobj,tablelayid)
        {
            var top=change_data(myobj);
            //tablelayid 表格id myobj 顶部内容
            var v = document.createElement("div");
            $(v).append($(".layui-table-box").find(".layui-table-header").html());      
            $(v).find("tr").after($("[lay-id=\"" + tablelayid+ "\"] .layui-table-body.layui-table-main table").html());     
            $(v).find('table').before(top);
            $(v).find("th.layui-table-patch").remove();
            $(v).find(".layui-table-col-special").remove();
            for(var i of $(v).find('input[type="text"]')){
                $(i).parent().append($(i).val());
                $(i).remove();
            }

            var $iframe = $("<iframe  />");
            $iframe.css({
                position: "absolute",
                width: "0px",
                height: "0px",
                left: "-600px",
                top: "-600px"
            });
            $iframe.appendTo("body");
            var doc = $iframe[0].contentWindow.document;
            
            var head = "<html><head><title></title></head><body><OBJECT classid='CLSID:8856F961-340A-11D0-A96B-00C04FD705A2' height='0' id='WebBrowser3' width='0' VIEWASTEXT></OBJECT><style>@page {size: auto;margin: 0mm;}body{font-size: 12px; color: #666;}table{width: 96%; margin: 0 auto; border-collapse: collapse; border-spacing: 0;}th,td{line-height: 20px; padding: 9px 15px; border: 1px solid #ccc; text-align: left; font-size: 12px; color: #666;}a{color: #666; text-decoration:none;}*.layui-hide{display: none}input[type='hidden']{display:none;}</style>";//先生成头部
            var foot = "</body></html>";//生成尾部
            // var newWin=window.open("_url","_blank","");
            bdhtml=window.document.body.innerHTML;
            var titleHTML=head+$(v).prop("outerHTML")+foot;   
            doc.write(titleHTML);  
            $iframe[0].contentWindow.print();  
        }
    }
    exports("myprint", obj);
});
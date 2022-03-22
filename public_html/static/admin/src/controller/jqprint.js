layui.define("jquery",function(exports){
var jQuery=layui.jquery;

function myprint(){ 
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
    
    var head = "<html><head><title></title></head><body><OBJECT classid='CLSID:8856F961-340A-11D0-A96B-00C04FD705A2' height='0' id='WebBrowser3' width='0' VIEWASTEXT></OBJECT><style>@page {size: auto;margin: 0mm;}</style>";//先生成头部
    var foot = "</body></html>";//生成尾部
    // var newWin=window.open("_url","_blank","");
    bdhtml=window.document.body.innerHTML;
    sprnstr="<!--startprint-->"; //打印区域开始的标记
    eprnstr="<!--endprint-->";   //打印区域结束的标记  
    prnhtml=bdhtml.substr(bdhtml.indexOf(sprnstr)+17);      
    prnhtml=prnhtml.substring(0,prnhtml.indexOf(eprnstr)); 
    var titleHTML=head+prnhtml+foot;   
    doc.write(titleHTML);  
    $iframe[0].contentWindow.print();  
}


    
exports('jqprint',{});
});
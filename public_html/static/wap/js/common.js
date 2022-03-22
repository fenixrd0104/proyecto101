var baseUrl = "";
var baseurl = "http://weshop.ruan.work/";

function $get(url, data, func,func2) {
	var surl = url.replace(/^(\/)|(\/)$/g, '');
	$.ajax({
		type: "get",
		data: data,
		url: baseUrl + url,
		success: function (data) {

			func(data);
			if(typeof(data)=='string'){
				data=JSON.parse(data);
			}
			if(data.code==-1&&data.msg=='用户未登录'){
				setTimeout(() => {
					window.location.href="/wap/index/login.html";
				}, 1500);
			}
		},
		error:function(XMLHttpRequest, textStatus, errorThrown){
			if(func2){func2();};
			mui.toast('网络错误，请稍后再试');
		}
	});
};

function $post(url, data, func,func2) {
	var surl = url.replace(/^(\/)|(\/)$/g, '');
	$.ajax({
		type: "post",
		data: data,
		url: baseUrl + url,
		success: function (data) {
			func(data);
			if(typeof(data)=='string'){
				data=JSON.parse(data);
			}
			if(data.code==-1&&data.msg=='用户未登录'){
				setTimeout(() => {
					window.location.href="/wap/index/login.html";
				}, 1500);
			}
		},
		error:function(XMLHttpRequest, textStatus, errorThrown){
			if(func2){func2();};
			mui.toast('网络错误，请稍后再试');
		}
	});
};
function btn_disabled(obj,text){
	obj.addClass('mui-disabled');
	obj.html(text);
};
function btn_abled(obj,text){
	obj.removeClass('mui-disabled');
	obj.html(text);
};
// 返回上一页
$('header').on('click', '.back-page', function () {
	mui.back();
	localStorage.setItem('load_list', 'true');
});
// 返回上一页
$('header').on('click', '.mui-action-back', function () {
	localStorage.setItem('load_list', 'true');

});
window.onpageshow = function (event) {
	if (localStorage.getItem('load_list') == 'true') {
		if ($('#mescroll').length == 0) {
			window.location.reload();
		}
		localStorage.setItem('load_list', 'false');

	} else {

	}

};
$('input').on('focus input',function(){
	this.scrollIntoViewIfNeeded();
	$('.footer').hide();
});
$('input').on('blur',function(){
	$('.footer').show();
});

// 底部跳转
$('#footer').on('tap', 'a', function () {
	var this_status = $(this).attr('data-login');
	var this_href = $(this).attr('data-href');
	if (this_href == 'no_href') {
		layer.msg('敬请期待');
	} else {
		location.href = this_href;
	}

});

// 头部导航栏  
$(function () {
	$('body').on('click', '#menu', function (e) {
		$('.login_pop').toggle();
		e.stopPropagation();
	})
	$('body').click(function () {
		$('.login_pop').hide();
	})
});


// 验证码
var countdown = 60;
function settime(obj) { //发送验证码倒计时
	if (countdown == 0) {
		obj.attr('disabled', false);
		// obj.removeattr("disabled"); 
		obj.html("获取验证码");
		countdown = 60;
		return;
	} else {
		obj.attr('disabled', true);
		obj.html("重新发送(" + countdown + ")");
		countdown--;
	}
	setTimeout(function () {
		settime(obj);
	}, 1000);
};
// 解决点击确定，软键盘弹出
$('body').on('tap','.mui-popup-button',function(){
	if($(this).parents('.mui-popup').find('input').val()){
		$(this).parents('.mui-popup').find('input').blur();
	}
});

// 使用方法
// function get_code(){
// 	var obj = $('.get_code');
// 	settime(obj);
// }

function $toast(str) {
	layer.open({
		content: str,
		time: 1.5,
		skin: 'msg',
		style: 'padding:0px 20px!important;background-color:rgba(30,30,30,0.8); border-radius:50px;'
	});
}
function $toast_s(str) {
	layer.open({
		type: 1,
		skin: 'store_demo', //样式类名
		closeBtn: 0, //不显示关闭按钮
		anim: 2,
		time: 4000,
		title: false, //不显示标题
		shadeClose: true, //开启遮罩关闭
		content: str
	});
}

$('#footer .mui-active img').attr('src', $('#footer .mui-active img').attr('title'));

// 手机号加省略号
function mobile(mobile) {
	var tel = mobile;
	tel = "" + tel;
	var reg = /(\d{3})\d{4}(\d{4})/;
	var tel1 = tel.replace(reg, "$1****$2")
	// console.log(tel1);
	return tel1;
}

// mobile(15638187275);  用法

function timetrans(date) {
	var date = new Date(date * 1000);//如果date为13位不需要乘1000
	var Y = date.getFullYear() + '-';
	var M = (date.getMonth() + 1 < 10 ? '0' + (date.getMonth() + 1) : date.getMonth() + 1) + '-';
	var D = (date.getDate() < 10 ? '0' + (date.getDate()) : date.getDate()) + ' ';
	var h = (date.getHours() < 10 ? '0' + date.getHours() : date.getHours()) + ':';
	var m = (date.getMinutes() < 10 ? '0' + date.getMinutes() : date.getMinutes());
	var s = ':' + (date.getSeconds() < 10 ? '0' + date.getSeconds() : date.getSeconds());
	return M + D + h + m;
}
function timetrans1(date) {
	var date = new Date(date * 1000);//如果date为13位不需要乘1000
	var Y = date.getFullYear() + '-';
	var M = (date.getMonth() + 1 < 10 ? '0' + (date.getMonth() + 1) : date.getMonth() + 1) + '-';
	var D = (date.getDate() < 10 ? '0' + (date.getDate()) : date.getDate()) + ' ';
	var h = (date.getHours() < 10 ? '0' + date.getHours() : date.getHours()) + ':';
	var m = (date.getMinutes() < 10 ? '0' + date.getMinutes() : date.getMinutes());
	var s = ':' + (date.getSeconds() < 10 ? '0' + date.getSeconds() : date.getSeconds());
	return Y + M + D + h + m;
}


// 自定义时间戳

// new Date(time*1000).Format("yyyy-MM-dd hh:mm:ss");    调用格式
Date.prototype.Format = function (fmt) { //author: meizz
	var o = {
		"M+": this.getMonth() + 1, //月份
		"d+": this.getDate(), //日
		"h+": this.getHours(), //小时
		"m+": this.getMinutes(), //分
		"s+": this.getSeconds(), //秒
		"q+": Math.floor((this.getMonth() + 3) / 3), //季度
		"S": this.getMilliseconds() //毫秒
	};
	if (/(y+)/.test(fmt)) fmt = fmt.replace(RegExp.$1, (this.getFullYear() + "").substr(4 - RegExp.$1.length));
	for (var k in o)
		if (new RegExp("(" + k + ")").test(fmt)) fmt = fmt.replace(RegExp.$1, (RegExp.$1.length == 1) ? (o[k]) : (("00" + o[k]).substr(("" + o[k]).length)));
	return fmt;
};
Date.Diff = function (start, end, fmt) {
	var time = end.getTime() - start.getTime();
	var day = time / (1000 * 3600 * 24);
	var hour = (time % (1000 * 3600 * 24)) / (3600 * 1000);
	var min = (time % (1000 * 3600)) / (60 * 1000);
	var sec = (time % (1000 * 60)) / 1000;
	var o = {
		"d+": parseInt(day), //日
		"h+": parseInt(hour), //小时
		"m+": parseInt(min), //分
		"s+": parseInt(sec), //秒
	};

	for (var k in o)
		if (new RegExp("(" + k + ")").test(fmt)) fmt = fmt.replace(RegExp.$1, (RegExp.$1.length == 1) ? (o[k]) : (("00" + o[k]).substr(("" + o[k]).length)));
	return fmt;
};

//字数限制

function limit(note, info, num) {
	var lim = new limit();
	lim.txtNote = document.getElementById(note);
	lim.txtLimit = document.getElementById(info);
	lim.limitCount = num;
	lim.init();
	function limit() {
		var txtNote;//文本框
		var txtLimit;//提示字数的input
		var limitCount;//限制的字数
		this.init = function () {
			txtNote = this.txtNote;
			txtLimit = this.txtLimit;
			limitCount = this.limitCount;
			txtNote.maxLength = limitCount;
			txtNote.onkeydown = function () { txtLimit.innerText = txtNote.value.length; };
			txtNote.onkeyup = function () { txtLimit.innerText = txtNote.value.length; };
		}
	}
}

// 下拉刷新 

function upLoad(obj, dropDown, callback) {
	var $doc = $(document);
	dropDown.html('加载中...');
	//执行上拉加载  		      
	obj.scroll(function () {
		var tag = true;
		//加载完成
		var suc = function () {
			dropDown.html('加载完成');
		};
		//没有数据了
		var err = function () {
			tag = false;
			dropDown.html('已经到底了~~');
			return;
		};
		//加载中 
		var loading = function () {
			dropDown.html('正在加载...');
		};
		if ($doc.scrollTop() + $(window).height() >= $doc.height() && tag) {
			//正在加载
			loading();
			//回调函数
			callback(suc, err);
		}
	});
}
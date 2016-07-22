<?php
    
    date_default_timezone_set("Asia/Hong_Kong");
    $datetime = date("Y/m/d") . ' ' . date('H:i:s');
    
    $css_appendix = "?v=201601301";
    
    
    function visitCount() {
        $cookie_name = "visit";
        
        if (isset($_COOKIE[$cookie_name])) {
            $cookie_value = $_COOKIE[$cookie_name];
            $cookie_count = (int) $cookie_value;
            
            if ($cookie_count < 0) {
                $cookie_count = 0;
            } else {
                $cookie_count = $cookie_count + 1;
            }
        } else {
            $cookie_count = 0;
        }
        
        setcookie($cookie_name, $cookie_count, time() + (86400 * 30));
        
        return $cookie_count;
    }
    
    function setTheme($value) {
        setcookie("theme", $value, time() + (86400 * 30));
        return true;
    }
    
    function getTheme() {
        $cookie_value = $_COOKIE["theme"];
        
        if ($cookie_value === "dark") {
            return "dark";
        }
        if ($cookie_value === "bright") {
            return "bright";
        }
        
        return "bright";
    }
    
    $visitcount = visitCount();
    
    $theme = getTheme();
    $client_ip = $_SERVER['REMOTE_ADDR'];
    
?>


<!doctype html>

<html>

<head>

<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, user-scalable=yes">
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<meta name="robots" content="noindex,nofollow" >

<title>Donate | Jiumo E-Book Search 鸠摩搜书 - 电子书搜索引擎</title>
<meta name="description" content="鸠摩电子书搜索引擎">
<meta name="keywords" content="图书, ebook, book, 找书, 搜电子书">

<link rel="shortcut icon" href="images/favicon.png" type="image/png">


<link id="theme" href="Style/Main_<?php echo $theme ?>.css<?php echo $css_appendix ?>" rel="stylesheet" type="text/css">
<link id="theme_main" href="Style/Main.css<?php echo $css_appendix ?>" rel="stylesheet" type="text/css">


</head>

<body>



<div style="width:95%; max-width:800px;  margin:40px auto 20px; height:auto;">




<div id="donate-text-div" style="display: inline-block; font-size: 15px; vertical-align: top;  width: 40%; ">




<div style="margin-top: 20px;">
<span style="font-weight: bolder; font-size: larger;">在此接受捐赠:</span>

<!--
<div style="margin-top:20px;">

<p>这个网址是想做到两件事</p>
<p>
一, 搜索可以下载的电子书 <br>直接用百度, Google 去搜一本书的名字时, 很多时候都是首先找到豆瓣,亚马逊这类网站的介绍; 有时还会看到一些奇怪的网站, 有下载链接, 可是在里面转来转去就是下载不到.
</p>
<p>
二, 检索不同论坛的资源 <br>能够用一个工具直接查看有没有自己想要找的书, 这个论坛收录的数量我在尽可能增加.
</p>

<p>如果能收到一些捐助, 我会非常非常开心的把这个网址一直维护下去.
</p>

<p>
<br>
谢谢 ~
<br>
Jack Jiumo
</p>



</div>
-->

<div style="margin-top:20px;">


<p>
如果这个工具能够帮上您一些忙, 欢迎分享给其他需要找书的人. 如果方便, 也可以捐助支持一下我.
</p>
<p>
谢谢 ~
</p>

<!--
<p>
维护这个网站主要不在界面, 而是需要想办法抓取搜索引擎和论坛的内容.
</p>

<p>
同时,为了可以在墙<span style="display: none;">|</span>内使用, 还需要解决穿过G<span style="display: none;">|</span>FW的问题. 我在后面换过好几个方法, 现在能做到的就是随着G<span style="display: none;">|</span>FW的变化, 换不同的方法应对.
</p>

<p>
如果能收到一些支持, 我会非常非常开心的把它一直维护下去.
</p>

<p>
<br>
谢谢 ~
</p>


<p>

如果您觉得本站能够帮上一些忙, 欢迎分享给其他需要找书的人.   如果方便, 也可以赞助一下请我喝杯咖啡.
</p>

<p>
<br>
谢谢 ~
<br>
(老王_Jack)
</p>
-->
</div>


<!--
做本站的目的. (搜索引擎, 论坛和网盘资源)


引擎的结果最全,  但是每次手动过滤掉那些

介绍和购买类网站 (豆瓣, 亚马逊)
避免(死链很多年的, 循环链接就是找不到下载地址的)

论坛资源和网盘,
不同论坛多次搜索

-->
</div>




</div>

<div id="donate-barcode-div">



<div style="text-align: center; margin-top: 40px; display:inline-block;">
<img src="images/donate_barcode_alipay5.gif" style="border: 6px solid #aa9988; border-radius: 20px 20px; width: 185px;">
<div style="margin-top: 10px; text-align: center;">支付宝扫码(小小)</div>
</div>



<div style="text-align: center; margin-top: 40px; display:inline-block;">
<img src="images/donate_barcode_wechat4.gif" style="border: 6px solid #778811; border-radius: 20px 20px; width: 185px;">
<div style="margin-top: 10px; text-align: center;">微信扫码(Jack)</div>
</div>


<div style="text-align: center; margin-top: 10px; ">
<div style="margin-top: 10px;margin-left: 7%;text-align: left;">支付宝账号: jackjiumo@126.com (小小)<br>微信帐号: jackwechat11 (Jack)
</div>
</div>

<span style="display:none;">

<form action="https://shenghuo.alipay.com/send/payment/fill.htm" id="donate" method="post" name="juanzeng" target="_blank"  style="display:inline" accept-charset="gbk" onsubmit="document.charset='gbk';">
<input name="optEmail" type="hidden" value="jackjiumo@126.com"> <input name="memo" type="hidden" value=""> <input id="payAmount" name="payAmount" type="hidden" value=""> <input id="title" name="title" type="hidden" value="捐赠 '鸠摩搜书'">
</form>

</span>

<script>

function link_alipay() {
    link_form = document.getElementById("donate");
    link_form.submit();
}

</script>



<div style="text-align:right;margin-top: 20px; display:none;">
<a href="donate_list.php" style="background: #7b5e40; display:inline-block;  padding: 6px 8px; color: #eee; font-size: 14px; border-radius: 10px;">
<div class="icons" style="width:20px; height:20px; vertical-align:middle; display:inline-block; background-position:-224px -96px;);"></div>
<span style="vertical-align:middle; display: inline-block;">捐赠者列表 &gt;</span>
</a>
</div>




</div>

</div>


</body>

</html>
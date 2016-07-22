<?php
    
    date_default_timezone_set("Asia/Hong_Kong");
    $datetime = date("Y/m/d") . ' ' . date('H:i:s');
    
    $css_appendix = "?v=201511052";
    $img_appendix = "?v=20151025";
    
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
    
    
    
    include("hitcounter/counter_config.php");
    
    function connect_dblink() {
        
        // ################################################
        // ######### connect + select  database ###########
        // ################################################
        
        global $localhost, $dbuser, $dbpass, $dbname;
        
        $link = mysql_connect($localhost, $dbuser, $dbpass);
        
        if (!$link) {
            die('Could not connect: ' . mysql_error());  // remove ?
        }
        
        $dbselect = mysql_select_db($dbname, $link);
        if (!$dbselect) {
            die("Can't use database $dbname! : " . mysql_error()); // remove ?
        }
        
        return $link;
        
    }
    
    function close_dblink($link) {
        mysql_close($link);
    }
    
    
    function get_advice_array($link) {
        $advice_array = array();
        
        
        
        $result = mysql_query("SELECT * FROM advices ORDER BY id DESC", $link);
        
        while ($row = mysql_fetch_array($result)) {
            
            $id = $row['id'];
            $ip_address = $row['ip_address'];
            $advice = $row['advice'];
            $reply = $row['reply'];
            $datetime = $row['datetime'];
            
            
            $advice_item = array(
                                 'id' => $row['id'],
                                 'ip_address' => $row['ip_address'],
                                 'advice' => $row['advice'],
                                 'reply' => $row['reply'],
                                 'datetime' => $row['datetime'],
                                 );
            
            $advice_array[] = $advice_item;
            
        }
        
        
        return $advice_array;
        
    }
    
    
    
    $link = connect_dblink();
    $advice_array = get_advice_array($link);
    close_dblink($link);
    

    
?>


<!doctype html>

<html>

<head>

<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, user-scalable=yes">
<meta http-equiv="content-type" content="text/html; charset=UTF-8">

<title>Info | Jiumo E-Book Search 鸠摩搜索 - 电子书搜索引擎</title>
<meta name="description" content="鸠摩电子书搜索引擎">
<meta name="keywords" content="搜书, 图书, ebook, book, 找书, 搜电子书">

<link rel="shortcut icon" href="images/favicon.png" type="image/png">


<link id="theme" href="Style5/Main_<?php echo $theme ?>.css<?php echo $css_appendix ?>" rel="stylesheet" type="text/css">
<link id="theme_main" href="Style5/Main.css<?php echo $css_appendix ?>" rel="stylesheet" type="text/css">


</head>

<body>



<div style="width:100%; margin-top:50px; height:auto;">


<div style="display:inline-block; width: 15%;"></div>

<div style="padding-left: 3%; display: inline-block; font-size: 15px; vertical-align: top;  width: 30%; min-width: 320px;">




<div style="margin-top: 20px;">
<span style="font-weight: bolder;">Search Engine:</span>
<ul>
<li>http://www.google.com</li>
<li>http://www.bing.com</li>

<!--
<li style="display: none;">http://www.xiexingwen.com (镜像)</li>
<li>https://www.duliziyou.com (镜像)</li>
<li>https://guge.io (镜像)</li>
<li>http://g.90r.org (镜像)</li>
-->
</ul>

<span style="font-weight:bold">Search from the following sites:</span>

<ul>
<li>yun.baidu.com (百度云)</li>
<li>pan.baidu.com (还是百度云?)</li>
<li>vdisk.weibo.com (微盘)</li>
<li>www.kindle114.com (就叫kindle114)</li>
<li>bbs.mydoo.cn (麦兜)</li>
<li>www.kindle10000.com (万读)</li>
<li>www.cnepub.com (掌上书院)</li>
<li>muchong.com (小木虫)</li>
<li>dl.dbank.com (华为网盘)</li>
<li>book.zi5.me (子乌书简)</li>
<li>wodemo.com (我的磨)</li>
<li>bbs.feng.com (FENG.COM)</li>
<li>www.xiaoshuotxt.net (TXT小说天堂)</li>

</ul>
<span style="font-weight:bold">Extra data from the following BBS <span style="display: none;">(updated on 2015.10.13)</span>:</span>

<ul>
<li>www.kindle10000.com (电子书, 杂志, 漫画)</li>
<li>bbs.mydoo.cn</li>
<li>www.cenpub.com (非epub格式)</li>
<li>www.readfar.com <span style="display: none;">(updated on 2015.7.10)</span></li>
<li>bbs.feng.com </li>
<li>www.kindle114.com </li>

<!--
<li>www.binnao.com <span style="display: none;">(updated on 2015.5.13)</span></li>
-->
</ul>

<span style="font-size: 14px;"><span style="font-weight:bold;font-size: 13px;">Email: </span>jackjiumo@126.com</span>

<!--
做本站的目的. (搜索引擎, 论坛和网盘资源)


引擎的结果最全,  但是每次手动过滤掉那些

介绍和购买类网站 (豆瓣, 亚马逊)
避免(死链很多年的, 循环链接就是找不到下载地址的)

论坛资源和网盘,
不同论坛多次搜索

-->
</div>

<!--  previous qq code
<div style="font-weight: bold;"><span style="font-weight: bold;">QQ Group: </span><a target="_blank" href="http://wpa.qq.com/msgrd?v=3&amp;uin=463852251&amp;site=qq&amp;menu=yes">463852251</a></div>
-->


<!--  temp
<div style="font-weight: bold;"><span style="font-weight: bold;">E-Book Share QQ Group: </span><a target="_blank" href="http://shang.qq.com/wpa/qunwpa?idkey=5b61c5b17f51670044104ca2d84680cf2e8008be30b4fc4f758071ee0bb1d074">463852251</a></div>
-->

</div>


<div style="padding-left:3%; display:inline-block; vertical-align: top;  width:85%; max-width:420px; ">


<img style="box-shadow: 1px 1px 10px 2px #cce; width:70%; height: auto; margin-top: 30px; margin-left:30%;" src="image/info/info.jpg"></img>
<div style="border-radius: 5px 5px; margin-top:10px; margin-bottom: 10px; width:100%; border: solid 1px grey; height: 150px; overflow:scroll;">
<ul style="margin-top: 5px; padding-left: 5px; list-style: none;">


<?php
    
    for ($i=0; $i<count($advice_array); $i++) {
        
        $advice = $advice_array[$i]['advice'];
        $reply = $advice_array[$i]['reply'];
        
        $reg_timespan = '/ \- [0-9][0-9][0-9][0-9]\/[0-9][0-9]\/[0-9][0-9] [0-9][0-9]\:[0-9][0-9]\:[0-9][0-9]/';
        $is_timespan = preg_match($reg_timespan, $advice, $matches);
        
        
        if ($reply) {
            
            if ($is_timespan) {
                $timespan = $matches[0];
                $datespan = substr($timespan, 2, 12);
                $advice = preg_replace ($reg_timespan, '', $advice) . ' - ' . $datespan;
            }
            
            echo '<li style="margin-bottom: 10px;"><div style="color: brown;  ">"';
            echo $advice;
            echo '"</div><div>';
            echo $reply;
            echo '</div></li>';
            
        }
        
        
    }
    
?>


</ul>
</div><form id="advice-form" onsubmit="return submit_advice()" method="get">

<textarea id="advice-textarea" maxlength="500" style="border-radius: 4px 4px; border: solid 1px grey; display: inline-block; width: 100%;  margin: 0px;"></textarea>
<button id="back-button"  onclick="return window.location.replace('/');" style="border-radius: 1px 1px; float: left; border: none; width: 110px; height: 25px;background-color: #7b5e40; color: white; margin-top: 5px;">Back To Search</button>
<button id="advice-button" type="submit" style="border-radius: 1px 1px; float: right; border: none; width: 120px; height: 25px;background-color: #7b5e40; color: white; margin-top: 5px;">Submit My Advice</button>


<script>

function submit_advice() {
    var advice_textarea = document.getElementById("advice-textarea");
    var advice_button = document.getElementById("advice-button");
    var advice_str = advice_textarea.value;
    
    if (advice_str.length === 0) {
        return false;
    }
    if (advice_textarea.getAttribute("readonly")) {
        return false;
    }
    
    
    //    alert(advice_str.length + " : " + advice_str);
    
    
    
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            var received_text = xmlhttp.responseText;
        }
    }
    xmlhttp.open("POST", "advice.php", true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.send("q=" + advice_str);
    
    
    
    advice_textarea.value = "Advice is submited :)";
    advice_textarea.setAttribute("readonly", true);
    advice_button.innerHTML = "Go Back";
    advice_button.setAttribute("onclick", "return window.location.replace('/')");
    
    return false;
}
</script>
</form>




</div>

</div>





</body>

</html>
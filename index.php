<?php
    
    date_default_timezone_set("Asia/Hong_Kong");
    $datetime = date("Y/m/d") . ' ' . date('H:i:s');
    
    $img_appendix = "?v=1";
    $css_appendix = "?v=20160513";
    

    // $image = "/images/front/visu.jpg" . $img_appendix;
    $image = "/images/front/hill7.jpg";
    $image_link = "/blog/?p=1053";
    $image_info = "コクリコ坂から";
    
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
<!DOCTYPE html>

<html>

<head>

<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, user-scalable=yes">
<meta http-equiv="content-type" content="text/html; charset=UTF-8">

<title>Jiumo E-Book Search 鸠摩搜书 - 电子书搜索引擎</title>
<meta name="description" content="鸠摩电子书搜索引擎">
<meta name="keywords" content="kindle, ebook, jiumosoushu, 找书, 搜电子书">

<link rel="shortcut icon" href="images/favicon.png" type="image/png">


<link id="theme" href="Style/Main_<?php echo $theme ?>.css<?php echo $css_appendix ?>" rel="stylesheet" type="text/css">
<link id="theme_main" href="Style/Main.css<?php echo $css_appendix ?>" rel="stylesheet" type="text/css">


<script>

// whether enable log info
var log_enabled = false;
function c_log(msg) { if(log_enabled) { console.log(msg); } return;}


// polyfill for ie

if (!window.console)
console = {log: function () {
}};


// TEST, set test variable
function setTestVariables() {
    var query = window.location.search.substring(1);
    var vars = query.split("&");
    for (var i=0;i<vars.length;i++) {
        var pair = vars[i].split("=");
        var value = true;
        if (pair[1] === "0" || pair[1] === "false") { value = false; }
        
        if (pair[0] === "cached") {
            g_cache_enabled = value;
        }
        if (pair[0] === "cn") {
            g_ischina = value;
        }
        if (pair[0] == "log") {
            log_enabled = value;
        }
        
    }
}


// URL direct search
// ...


// URL insert parameter
// ...


function isLetter(word) {
    
    var reg_charactor = new RegExp('[\u3400-\u9FBF]');
    var reg_letter = new RegExp('[a-zA-Z][a-zZ-Z]');
    
    if (word.length>=4 && reg_letter.test(word) && (!reg_charactor.test(word)) ) {
        return true;
    }
    return false;
}


function allTrimText(str) {
    str = str.replace(/\s+/g, ' ');
    str = str.replace(/^\s+|\s+$/g, '');
    return str;
}



</script>

<script>

var current_theme = "<?php echo $theme; ?>";
var visit_count = <?php echo $visitcount; ?>;
var g_ischina = true;
var g_cache_enabled = true;
var g_device = 'normal';


// disable alert
window.alert = function () {
};


// status hub related
////////////////////
var status_hub = [];
var hub_word = '';


function add_status_hub(name, word) {
    
    if (word !== hub_word) {
        c_log( 'hub add alert - hub_word not match, name: ' + name +  '; word: ' + word + '; hub_word: ' + hub_word);
        return false;
    }
    
    var item = {
    source: name,
    status: 'running',
    received_type: '',
    received_data: '',
    }
    status_hub.push(item);
    return true;
}


function find_in_status_hub(name) {
    for (var i=0; i<status_hub.length; i++) {
        if (status_hub[i].source === name) {
            return status_hub[i];
        }
    }
    return false;
}


function update_status_hub(name, word, received_type, received_data) {
    
    c_log('hub received ' + name + ', ' + received_type + ', ' + received_data.length);
    
    // word not match
    if (word !== hub_word) {
        c_log( 'hub received alert - hub_word not match, name: ' + name +  '; word: ' + word + '; hub_word: ' + hub_word);
        return;
    }
    
    // name not found in hub
    if (!find_in_status_hub(name)) {
        c_log( 'hub received alert - source name not match: ' + name);
        return;
    }
    
    
    // stop received after any data parsed
    if (status_hub.length === 0) {
        c_log( 'hub received alert - stop received, hub cleared, name: ' + name +  '; word: ' + word + '; hub_word: ' + hub_word);
        return;
    }
    

    
    for (var i=0; i<status_hub.length; i++) {
        if (status_hub[i].source === name) {
            status_hub[i].status = 'received';
            status_hub[i].received_type = received_type;
            status_hub[i].received_data = received_data;
            break;
        }
    }
    
    
    var count_running = 0;
    var count_data = 0;
    var data_combined = [];
    
    for (var i=0; i<status_hub.length; i++) {
        if (status_hub[i].status === 'running') {
            count_running++;
        }
        if (status_hub[i].received_type === 'data') {
            count_data++;
            data_combined = data_combined.concat(status_hub[i].received_data);
        }
    }
    
    
    
    if (received_type === 'data') {
        hide_loading();
        c_log('hub to parsing - new data received ' + name);
        parse_fetched(data_combined, word, true);
    }
    
    
    
    // all result is done
    if (count_running === 0) {
        
        hide_loading();
        hide_loadingmore();
        
        if (count_data > 0) {

            c_log('hub to closing - all received, data parsed when arrived');

        }
        else {
            
            // not a single data received
            
            if (find_in_status_hub('normal').received_type === 'empty' ) {
                
                notfound_alert();
                c_log('hub to parsing - all received, no data, normal empty, show empty');
            }
            else if (find_in_status_hub('bing').received_type === 'empty') {
                // bing is used & bing result is empty
                notfound_alert();
                c_log('hub to parsing - all received, no data, bing empty, show empty');
            }
            else {
                notfound_alert();
                c_log('hub to parsing - all received, no data, normal unknwon, bing unknwon, show backup bing (now empty)');
            }
        }
        
        status_hub = [];
        hub_word = '';
        c_log('hub cleared');
    }
    
    
    return;
    
}




var previous_search = '';
var search_count = 0;
var countdown = 0;
var search_count_handle = setInterval(reset_search_count, 1000 * 10);
function reset_search_count() {
    search_count = 0;
}

function counting_down() {
    
    var search_word = document.getElementById("SearchWord");
    var search_button = document.getElementById("SearchButton");
    if (count_down > 0) {
        search_word.setAttribute('disabled', 'disabled');
        search_button.setAttribute('disabled', 'disabled');
        search_button.setAttribute('style', 'opacity: 0.5;');
        search_button.innerHTML = count_down + " seconds";
        count_down = count_down - 1;
        setTimeout(counting_down, 1000);
    }
    else
    {
        search_word.removeAttribute('disabled');
        search_button.removeAttribute('disabled');
        search_button.setAttribute('style', 'opacity: 1;');
        search_button.innerHTML = search_button.value;
    }
    
}


function suspicious_spam() {
    if (search_count > 5) {
        count_down = 10;
        counting_down();
        return true;
    }
    else {
        return false;
    }
}



// supporting

function wechat_overlay_show() {
    var wechat_overlay = document.getElementById("wechat-overlay");
    var img = '<img style="max-width:100%; border: solid 2px grey; border-radius: 5px 5px;" src="images/wechat_share_barcode.gif"></img>';
    wechat_overlay.innerHTML = img;
    wechat_overlay.style.display = 'block';
}

function wechat_overlay_hide() {
    var wechat_overlay = document.getElementById("wechat-overlay");
    wechat_overlay.style.display = 'none';
}

function loadingmore_eclipse_animate() {
    var loadingmore_eclipse = document.getElementById("loadingmore-eclipse");
    if (loadingmore_eclipse) {
        if (loadingmore_eclipse.innerHTML === '.') {loadingmore_eclipse.innerHTML = '. .'; return;}
        if (loadingmore_eclipse.innerHTML === '. .') {loadingmore_eclipse.innerHTML = '. . .'; return;}
        if (loadingmore_eclipse.innerHTML === '. . .') {loadingmore_eclipse.innerHTML = ''; return;}
        if (loadingmore_eclipse.innerHTML === '') {loadingmore_eclipse.innerHTML = '.'; return;}
    }
}



// update share times
function share_record(new_share, str) {
    
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        if (xmlhttp.readyState === 4 && xmlhttp.status === 200) {
        }
    };
    
    var request_url = "fetch_record.php";
    if (new_share) {
        request_url = request_url + "?q=plus";
        link_clicked(str);
    }
    xmlhttp.open("POST", request_url, true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.setRequestHeader("X-Requested-With", "XMLHttpRequest");
    xmlhttp.send();
    
    return false;
}


function comm_lastday() {
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        if (xmlhttp.readyState === 4 && xmlhttp.status === 200) {
        }
    };
    xmlhttp.open("POST", "comm_lastday.php", true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.setRequestHeader("X-Requested-With", "XMLHttpRequest");
    xmlhttp.send();
    return false;
}


function comm_info() {
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        if (xmlhttp.readyState === 4 && xmlhttp.status === 200) {
            
            var share_count = 0;
            var info = JSON.parse(xmlhttp.responseText);
            if (info) {
                if (info['country'] !== 'CN' && info['country'].length >= 2) {
                    g_ischina = false;
                }
                
                share_count = info['share_count'];
                g_device = info['device'];
            }
            
            
            
            // load share icons

            if (g_ischina) {
                show_share_cn();
            }
            else {
                show_share();
            }
           
            
            // load google analytics
            google_analytics();
            
            
            
            // clean lastday count
            comm_lastday();
            
            
            // set TEST variables
            setTestVariables();
            
            
            // check url direct search
            // check_url_direct();
            
            
        }
    };
    xmlhttp.open("POST", "comm_info.php", true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.setRequestHeader("X-Requested-With", "XMLHttpRequest");
    xmlhttp.send();
    return false;
}



function _PageLoad() {
    
    // if not on mobile device
    if (window.innerWidth > 500) {
        if (document.getElementById("SearchWord") !== null) {
            document.getElementById("SearchWord").focus();
        }
    }
    
    // communicate info with server
    comm_info();
    
    // prepare loading icon
    create_loading_icon(false);
    
    // loadingmore animate
    setInterval(loadingmore_eclipse_animate, 300);
    
}


function google_analytics() {
    
    (function (i, s, o, g, r, a, m) {
     i['GoogleAnalyticsObject'] = r;
     i[r] = i[r] || function () {
     (i[r].q = i[r].q || []).push(arguments)
     }, i[r].l = 1 * new Date();
     a = s.createElement(o),
     m = s.getElementsByTagName(o)[0];
     a.async = 1;
     a.src = g;
     m.parentNode.insertBefore(a, m)
     })(window, document, 'script', '//www.google-analytics.com/analytics.js', 'ga');
    ga('create', 'UA-63432350-1', 'auto');
    ga('send', 'pageview');
}



function show_share_cn() {
    var share_buttons_cn = document.getElementById("share-buttons-cn");
    share_buttons_cn.style.display = "";
}


function show_share() {
    var share_buttons = document.getElementById("share-buttons");
    share_buttons.style.display = "";
}


function create_loading_icon(force_recreate) {
    var loading_icon = document.getElementById("loading-icon");
    if (loading_icon.getElementsByTagName("img").length !== 0 && force_recreate === false) {
        return;
    }
    
    loading_icon.innerHTML = "";
    img = new Image();
    if (current_theme === "dark") {
        img.src = "images/loading_dark.gif";
    }
    else {
        img.src = "images/loading_bright.gif";
    }
    loading_icon.innerHTML = '<img src="' + img.src + '">';
    return;
}



function setCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
    var expires = "expires=" + d.toUTCString();
    document.cookie = cname + "=" + cvalue + "; " + expires;
}

function getCookie(cname) {
    var name = cname + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) === ' ')
            c = c.substring(1);
        if (c.indexOf(name) === 0)
            return c.substring(name.length, c.length);
    }
    return "";
}

function checkCookie() {
    var user = getCookie("username");
    if (user !== "") {
        return true;
    } else {
        return false;
    }
}





function themeChanged() {
    
    var theme_link = document.getElementById("theme");
    if (current_theme === "dark") {
        current_theme = "bright";
        setCookie("theme", "bright", 30);
        theme_link.setAttribute("href", "Style/Main_bright.css<?php echo $css_appendix; ?>");
    }
    else {
        current_theme = "dark";
        setCookie("theme", "dark", 30);
        theme_link.setAttribute("href", "Style/Main_dark.css<?php echo $css_appendix; ?>");
    }
    
    
    // force redraw
    if (document.body !== null)
    {
        // forse recreate loading icon
        create_loading_icon(true);
        
        document.body.style.display = "none";
        var force_redraw = document.body.offsetHeight;
        document.body.style.display = "";
    }
    
    // notify server client theme changed
    link_clicked('theme changed to ' + current_theme);
    return false;
}


function load_mainpage() {
    
    var main_form = document.getElementById("main-form");
    var main_form_class = main_form.getAttribute("class");
    if (main_form_class === "mainform_search") {
        window.location = window.location.pathname;
    } else if (main_form_class === "mainform") {
        var image_link = "<?php echo $image_link ?>";
        if (image_link.length > 0) {
            window.open(image_link, "_blank");
        }
    }
    
    return false;
}

</script>



</head>





<body onload="_PageLoad();" >


<div style="width:100%;height:auto;">

<div id="logo" class="top-wrapper" >
<div class="logo">

<div class="icons icon-title"></div>


<div id="setting-ul"  style="margin-top:10px;">
<div class="icons-group">
<a id="donate-div"  href="donate.php" target="_blank" onclick="link_clicked('donate clicked : <?php /* echo $client_ip . " " . $datetime; */ ?>')" title="donate"><div class="icons icon-item-big icon-donate-big"></div></a>
    <a id="theme-div" href="#" onclick="return themeChanged();" title="change theme"><div class="icons icon-item icon-theme"></div></a>
    <a href="info.php" target="_blank" onclick="link_clicked('info clicked : <?php /* echo $client_ip; */ ?>')"  title="site info"><div id="info-div" class="icons icon-item icon-info"></div></a>
</div>

</div>



<div id="bdshare-div" style="margin-top:5px;">


<div id="share-buttons" style="display:none;" class="icons-group">
    <a href="https://www.facebook.com/sharer/sharer.php?u=http%3A%2F%2Fwww.jiumodiary.com&t=" title="Share on Facebook" target="_blank" onclick="window.open('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(document.URL) + '&t=' + encodeURIComponent(document.URL)); share_record(true, 'shared to facebook'); return false;"><div class="icons icon-item icon-facebook"></div></a>
    <a href="https://twitter.com/intent/tweet?source=http%3A%2F%2Fwww.jiumodiary.com&text=:%20http%3A%2F%2Fwww.jiumodiary.com" target="_blank" title="Tweet" onclick="window.open('https://twitter.com/intent/tweet?text=' + encodeURIComponent(document.title) + ':%20'  + encodeURIComponent(document.URL)); share_record(true, 'shared to twitter'); return false;"><div class="icons icon-item icon-twitter"></div></a>
    <a href="https://plus.google.com/share?url=http%3A%2F%2Fwww.jiumodiary.com" target="_blank" title="Share on Google+" onclick="window.open('https://plus.google.com/share?url=' + encodeURIComponent(document.URL)); share_record(true, 'shared to googleplus'); return false;"><div class="icons icon-item icon-googleplus"></div></a>
    <a href="http://www.linkedin.com/shareArticle?mini=true&url=http%3A%2F%2Fwww.jiumodiary.com&title=&summary=&source=http%3A%2F%2Fwww.jiumodiary.com" target="_blank" title="Share on LinkedIn" onclick="window.open('http://www.linkedin.com/shareArticle?mini=true&url=' + encodeURIComponent(document.URL) + '&title=' +  encodeURIComponent(document.title)); share_record(true, 'shared to linkedin'); return false;"><div class="icons icon-item icon-linkedin"></div></a>
    <a href="http://www.evernote.com/clip.action?url=http%3A%2F%2Fwww.jiumodiary.com&t=&s=" target="_blank" title="Clip to Evernote" onclick="window.open('http://www.evernote.com/clip.action?url=' + encodeURIComponent(document.URL) + '&t=' +  encodeURIComponent(document.title)); share_record(true, 'shared to evernote'); return false;"><div class="icons icon-item icon-evernote"></div></a>
</div>


<div id="share-buttons-cn" style="display:none;" class="icons-group">
<a href="http://service.weibo.com/share/share.php?url=http%3A%2F%2Fwww.jiumodiary.com&searchPic=true" title="分享到新浪微博" target="_blank" onclick="window.open('http://service.weibo.com/share/share.php?url=http://' + encodeURIComponent(window.location.hostname) + '&title=' + encodeURIComponent(document.title)); share_record(true, 'shared to Weibo'); return false;"><div class="icons icon-item icon-weibo"></div></a>
<a id="wechat-share" title="分享到微信" onclick="wechat_overlay_show(); share_record(true, 'shared to wechat'); return false;"><div class="icons icon-item icon-wechat"></div></a>
<a href="http://sns.qzone.qq.com/cgi-bin/qzshare/cgi_qzshare_onekey?url=http%3A%2F%2Fwww.jiumodiary.com" target="_blank" title="分享到QQ空间" onclick="window.open('http://sns.qzone.qq.com/cgi-bin/qzshare/cgi_qzshare_onekey?url=http://' + encodeURIComponent(window.location.hostname) + '&title=' + encodeURIComponent(document.title));  share_record(true, 'shared to qqzone'); return false;"><div class="icons icon-item icon-qqzone"></div></a>
<a href="http://www.douban.com/recommend/?url=http%3A%2F%2Fwww.jiumodiary.com" target="_blank" title="分享到豆瓣" onclick="window.open('http://www.douban.com/recommend/?url=http://' + encodeURIComponent(window.location.hostname) + '&title=' + encodeURIComponent(document.title) + ' (' + encodeURIComponent(document.URL) + ')');  share_record(true, 'shared to douban'); return false;"><div class="icons icon-item icon-douban"></div></a>
<a href="https://app.yinxiang.com/clip.action?url=http%3A%2F%2Fwww.jiumodiary.com" target="_blank" title="分享到印象笔记" onclick="window.open('https://app.yinxiang.com/clip.action?url=' + encodeURIComponent(document.URL) + '&title=' + encodeURIComponent(document.title) + ' (' + encodeURIComponent(document.URL) + ')');   share_record(true, 'shared to evernote_cn'); return false;"><div class="icons icon-item icon-evernote"></div></a>
</div>


</div>

</div>
</div>



<div id="main-form"    class="mainform">
<form id="SearchForm" onsubmit="return startsearch();">

<div id="row-0-space-1" > </div>

<div id="row-1" class="row1" >

<a id="row-1-link" href="#" onclick="return load_mainpage();">
<div id="front-info" style="">
    <div style='display: inline-block; color: #eee; font-size: 11px; vertical-align: middle;'><?php echo $image_info ?></div>
    <div style='display:inline-block; width: 20px; height: 20px; background-image: url("/images/icons.png?v=5"); background-position: -192px -128px; vertical-align: middle;'></div>
</div>
<img id="front-image"  alt="" src="<?php echo $image ?>" title="">
</a>

</div>

<div id="row-1-space-2"> </div>



<div id="row-2" class="row2">
<input id="SearchWord" type="text" maxlength="2048"  autocomplete="off" class="invert">
</div>


<div id="row-2-space-3"> </div>

<div id="row-3" class="row3">
<button id="SearchButton" type="submit" value="Search" class="button" >Search</button>
</div>

</form>


<div id="icon-wrapper" class="row4"></div>


<div id="filter-div" style="text-align: center; margin-top: 50px;">
<span class="filter" > <input type="checkbox" class="resource_filter invert" value="yun.baidu.com" checked="" disabled>yun.baidu</span>
<span class="filter" > <input type="checkbox" class="resource_filter invert" value="pan.baidu.com" checked="" disabled>pan.baidu</span>
<span class="filter" > <input type="checkbox" class="resource_filter invert" value="vdisk.weibo.com" checked="" disabled>vdisk</span>
<span class="filter" > <input type="checkbox" class="resource_filter invert" value="emuch.net" checked="" disabled>emuch</span>
<span class="filter" > <input type="checkbox" class="resource_filter invert" value="wodemo.com" checked="" disabled>wodemo</span>
<span class="filter" > <input type="checkbox" class="resource_filter invert" value="www.xiaoshuotxt.com" checked="" disabled>xiaoshuotxt</span>

<span class="filter" style="display:none;" > <input type="checkbox" class="resource_filter_en invert" value="www.4shared.com" checked="" disabled>4shared</span>


<span class="filter" > <input type="checkbox" class="invert" value="dl.dbank.com" checked="" disabled>dbank</span>
<span class="filter" > <input type="checkbox" class="invert" value="bbs.feng.com" checked="" disabled>feng</span>
<span class="filter" > <input type="checkbox" class="invert" value="www.kindle114.com" checked="" disabled>kindle114</span>
<span class="filter" > <input type="checkbox" class="invert" value="www.cnepub.com" checked="" disabled> cnepub</span>
<span class="filter" > <input type="checkbox" class="invert" value="bbs.mydoo.cn" checked="" disabled>mydoo</span>
<span class="filter" > <input type="checkbox" class="invert" value="www.kindle10000.com" checked="" disabled>kindle10000</span>


</div>

</div>

<div id="main-panel" style="">


<div id="result-panel" style="display: none;">

<div id="result-panel-left">  </div>



<div id="google-panel" class="google-panel-width" style="vertical-align:top; display: inline-block;">
<table style="width:100%">

<tr>
<td style="height: 20px;">
<span id = "format-filter" style="width: 100%; display: inline-block; text-align: right; "></span>
</td>
</tr>

<tr>
<td style="width: 100%">

<div id="google-div" >
<div id="loading-icon"  style="display:none;"></div>


<div id="google-result-div" style="position: relative;" >
<iframe src="" id="google-iframe" class="iframe-default" scrolling="auto" style="display:none;"></iframe>

<ul id="result-ul" style="padding-left: 10px; width: 95%; max-width: 600px"></ul>
</div>

</div>

</td>
</tr>
</table>

</div>






<div id="result-panel-middle" class="result-panel-middle-hide"> </div>





<div id="forum-panel" style="vertical-align:top; display: inline-block;">
<table style="width: 100%;">

<tr>
<td style="height: 20px;">
<span id = "priced-filter"  style="width: 100%; display: inline-block; text-align: right; ">BBS</span>
</td>
</tr>

<tr>
<td style="width: 100%">

<div id="forum-div">
<div id="forum-result" style="color: #ddd; margin-top: 15px; margin-left: 10px; margin-right:10px"></div>
</div>

</td>
</tr>
</table>
</div>





</div>

</div>


</div>


<script>



function status_result(action, c, q, status, log, data_length) {
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        if (xmlhttp.readyState === 4 && xmlhttp.status === 200) {
        }
    };
    xmlhttp.open("POST", "status_result.php", true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.setRequestHeader("X-Requested-With", "XMLHttpRequest");
    xmlhttp.send("action=" + encodeURIComponent(action) + "&c=" + encodeURIComponent(c) + "&q=" + encodeURIComponent(q) + "&status=" + encodeURIComponent(status) + "&log=" + encodeURIComponent(log) + "&data_length=" + encodeURIComponent(data_length));
    
    
    return false;
}




function link_clicked(pagename) {
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        if (xmlhttp.readyState === 4 && xmlhttp.status === 200) {
        }
    };
    xmlhttp.open("POST", "link_clicked.php", true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.setRequestHeader("X-Requested-With", "XMLHttpRequest");
    xmlhttp.send("q=" + encodeURIComponent(pagename));
    return false;
}



function submitword_priced(word) {
    
    
    if (word.length === 0) {
        return;
    } else {
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            if (xmlhttp.readyState === 4 && xmlhttp.status === 200) {
                var received_text = xmlhttp.responseText;
                var result_array = JSON.parse(received_text);
                
                var is_dealed = false;
                var status = result_array['status'];
                var log = result_array['log'];
                var data_str = result_array['data'];
                
                if (status === 'cached' || status === 'succeed') {
                    if (data_str.length > 20) {
                        try {
                            data_array = JSON.parse(decodeURIComponent(data_str));
                        }
                        catch(err) {
                            return;
                        }
                        
                        if (data_array) {
                            
                            var forum_result_div = document.getElementById("forum-result");
                            var forum_div = document.getElementById("forum-div");
                            var forum_panel = document.getElementById("forum-panel");
                            var google_panel = document.getElementById("google-panel");
                            var result_panel_middle = document.getElementById("result-panel-middle");
                            if (!(google_panel.getAttribute("class") === "google-panel-width-backup")) {
                                google_panel.setAttribute("class", "google-panel-width-narrow");
                            }
                            forum_result_div.style.display = "";
                            forum_div.style.display = "";
                            forum_panel.style.display = "inline-block";
                            result_panel_middle.setAttribute("class", "result-panel-middle-show");
                            parse_priced(data_array,'','');
                            return;
                            
                        }
                        else {
                            status = 'client empty array';
                        }
                    }
                    else {
                        status = 'client insufficient data';
                    }
                    
                }
                
                
                
                
                // waiting for more action, submit logs
                
                c_log('fetched kongfz status: ' + status + ': ' + log);
                
                return;
            }
        };
        xmlhttp.open("POST", "wordsfp_kongfz.php", true);
        xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xmlhttp.setRequestHeader("X-Requested-With", "XMLHttpRequest");
        xmlhttp.send("q=" + encodeURIComponent(word));
    }
}



function fetchword(word, forced) {
    
    
    // TEST, if NOT cache_enabled, not load cache, return directly
    if (!g_cache_enabled) {
        
        fetchword_other('eng', word);
        fetchword_other('bing', word);
        update_status_hub('normal', word, 'cache_disabled', '');
        return;
    }
    
    // fetch cache
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        if (xmlhttp.readyState === 4 && xmlhttp.status === 200) {
            var received_text = xmlhttp.responseText;
            var result_array = JSON.parse(received_text);
            
            var is_dealed = false;
            var status = result_array['status'];
            var log = result_array['log'];
            var data_str = result_array['data'];
            
            var match = parseInt(result_array['match']);
            var weibo = parseInt(result_array['weibo']);
            c_log('valid_match: ' + match + ';  weibo: ' + weibo);
            
            if ((status === 'cached') && (match <= 3) && (weibo >=3)) {
                fetchword(word, true);
                return;
            }
            
            if ((match <= 3) && (status !== 'exceed_user')) {
                submitword_priced(word);
            }
            
            
            if (status === 'cached' || status === 'succeed') {
                if (data_str.length > 50) {
                    data_array = '';
                    try {
                        data_array = JSON.parse(decodeURIComponent(data_str));
                    }
                    catch(err) {
                        c_log(err.message);
                        status_result('update', g_ischina, word, 'client error', '(' + status + ') ' + err.message, 0);
                        
                        if (status === 'cached') {
                            fetchword(word, true);
                        }
                        else {
                            fetchword_other('eng', word);
                            fetchword_other('ishare', word);
                            fetchword_other('bing', word);
                            
                            update_status_hub('normal', word, 'issue', '');
                            c_log('uri parse issue (not cache), start backups directly');
                        }
                        return;
                    }
                    
                    if (data_array) {
                        
                        if (match <= 5) {
                            fetchword_other('eng', word);
                            fetchword_other('ishare', word);
                            fetchword_other('bing', word);
                            
                        }
                        update_status_hub('normal', word, 'data', data_array);
                        
                        is_dealed = true;
                        
                    }
                    else {
                        status = 'client empty array';
                    }
                }
                else {
                    var err_message = 'insufficient data, data_length: ' + data_str.length;
                    status_result('update', g_ischina, word, 'client error', '(' + status + ') ' + err_message, 0);
                    
                    if (status === 'cached') {
                        fetchword(word, true);
                    }
                    else {
                        fetchword_other('eng', word);
                        fetchword_other('ishare', word);
                        fetchword_other('bing', word);
                        update_status_hub('normal', word, 'issue', '');
                        c_log('insufficient data issue (not cache), start backups directly');
                    }
                    return;
                }
                
            }
            
            
            
            if (status === 'empty') {
                
                fetchword_other('eng', word);
                fetchword_other('ishare', word);
                fetchword_other('bing', word);
                update_status_hub('normal', word, 'empty', '');
                
                is_dealed = true;
            }
            
            
            
            // others, load backup
            if (!is_dealed) {
                if (status === 'exceed_user') {
                    hide_loading();
                    status_hub = [];
                    error_alert('exceed');
                }
                else {
                    fetchword_other('eng', word);
                    fetchword_other('ishare', word);
                    fetchword_other('bing', word);
                    update_status_hub('normal', word, 'issue', '');
                    c_log('fetched failed, start backup!');
                }
                
            }
            
            
            // waiting for more action, submit logs
            c_log('fetched normal status: ' + status + ': ' + log);
            
            
            // update status
            status_result('update', g_ischina, word, status, log, data_str.length);
            
            
            return;
        }
    
    };
    xmlhttp.open("POST", "wordsf_normal.php", true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.setRequestHeader("X-Requested-With", "XMLHttpRequest");
    xmlhttp.send("q=" + encodeURIComponent(word) + "&f=" + forced);

    c_log('start fetch normal: ' + word);


}


// eng, mag, bing, ishare
function fetchword_other(source_type, word) {
    
    if (source_type === 'eng') {
        if (!isLetter(word)) {
            return;
        }
    }

    
    var add_status = add_status_hub(source_type, word);
    if (!add_status) {
        return;
    }
    
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        if (xmlhttp.readyState === 4 && xmlhttp.status === 200) {
            var received_text = xmlhttp.responseText;
            var result_array = JSON.parse(received_text);
            
            var is_dealed = false;
            var status = result_array['status'];
            var log = result_array['log'];
            var data_str = result_array['data'];
            
            if (status === 'cached' || status === 'succeed') {
                if (data_str.length > 20) {
                    data_array = JSON.parse(decodeURIComponent(data_str));
                    if (data_array) {
                        
                        update_status_hub(source_type, word, 'data', data_array);
                        is_dealed = true;
                    }
                    else {
                        status = 'client empty array';
                    }
                }
                else {
                    status = 'client insufficient data';
                }
                
            }
            
            
            
            if (status === 'empty') {
                update_status_hub(source_type, word, 'empty', '');
                is_dealed = true;
            }
    
            
            // others, load backup
            if (!is_dealed) {
                update_status_hub(source_type, word, 'issue', '');
            }
            
            
            // waiting for more action, submit logs
        
            c_log('fetched ' +  source_type + ' status: ' + status + ': ' + log);
            
            
            
            return;
        }
        
    };
    xmlhttp.open("POST", "wordsf_" + source_type + ".php", true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.setRequestHeader("X-Requested-With", "XMLHttpRequest");
    xmlhttp.send("q=" + encodeURIComponent(word));
    
   
    c_log('start fetch ' + source_type + ' : ' + word);
    
}






function startsearch() {
    
    var search_word = document.getElementById("SearchWord");
    var input_keyword = search_word.value;
    if (input_keyword.length === 0 || input_keyword === previous_search) {
        return false;
    }
    
    
    
    var suspicious = suspicious_spam();
    if (suspicious) {
        return false;
    }
    
    
    validate();
    search_count = search_count + 1;
    previous_search = input_keyword;
    setTimeout(function () {
               previous_search = '';
               }, 2000);
    
    return false;
}



function optimize(str) {
    if (str.length === 0) {
        return str;
    }
    
    var char_m = [ "\.", "\,", "\(", "\)", "\'", "\"", "\<", "\>", "\。", "\，", "\（", "\）", "\‘", "\”", "\《", "\》", "\－", "\-" ];
    for (var i = 0; i < char_m.length; i++) {
        char_m[i] = '\\' + char_m[i];
    }
    
    var char_exp = new RegExp(char_m.join('|'), 'g');
    str = str.replace(char_exp, ' ');
    str = allTrimText(str);
    return str;
}



function exact_match_rate(word_raw, str, des, host) {
    
    var reg_charactor = new RegExp('[\u3400-\u9FBF]');
    
    word_raw = optimize(word_raw).toLowerCase();
    var word_raw_arr = word_raw.split(' ');
    var word_arr = [];
    for (var i=0; i < word_raw_arr.length; i++) {
        if (word_raw_arr[i].length > 0) {
            if (!reg_charactor.test(word_raw_arr[i])) { word_arr.push(word_raw_arr[i]); }
            else { word_arr = word_arr.concat(word_raw_arr[i].split('')); }
        }
    }
    
    str = optimize(str).toLowerCase();
    var str_raw_arr = str.split(' ');
    var str_arr = [];
    for (var i=0; i < str_raw_arr.length; i++) {
        if (str_raw_arr[i].length > 0) {
            if (!reg_charactor.test(str_raw_arr[i])) { str_arr.push(str_raw_arr[i]); }
            else { str_arr = str_arr.concat(str_raw_arr[i].split('')); }
        }
    }
    
    
    var word_count = word_arr.length;
    var str_count = str_arr.length;
    
    var exact_rate = 0;
    var focus_rate = Math.round(word_count*100/str_count);
    var host_value = 0;
    if (host.indexOf('ishare.iask.sina.com.cn') !== -1) { if (word_raw.indexOf('积分') === -1 && des.indexOf('积分') !== -1) { host_value -= 5; } }
    
    if (str_arr.indexOf(word_raw) !== -1) {
        exact_rate = 1100;
    }
    else {
        var exact_count = 0;
        for (var i = 0; i < word_arr.length; i++) {
            if (str_arr.indexOf(word_arr[i]) !== -1) {
                exact_count++;
            }
        }
        exact_rate = Math.round(exact_count*1000/word_count);
    }
    
    return exact_rate + focus_rate + host_value;
}



function validate() {
    
    
    var search_word = document.getElementById("SearchWord");
    // changeUrlParam('q', search_word.value);
    
    var input_keyword = optimize(search_word.value);
    var search_button = document.getElementById("SearchButton");
    var loading_icon = document.getElementById("loading-icon");
    var search_site;
    var search_url;
    var result_panel = document.getElementById("result-panel");
    var result_panel_middle = document.getElementById("result-panel-middle");
    var google_div = document.getElementById("google-div");
    var google_result_div = document.getElementById("google-result-div");
    var format_filter = document.getElementById("format-filter");
    var google_panel = document.getElementById("google-panel");
    var forum_panel = document.getElementById("forum-panel");
    var forum_div = document.getElementById("forum-div");
    var forum_result_div = document.getElementById("forum-result");
    var main_form = document.getElementById("main-form");
    var row_1 = document.getElementById("row-1");
    var row_2 = document.getElementById("row-2");
    var row_3 = document.getElementById("row-3");
    var row_1_link = document.getElementById("row-1-link");
    
    var icon_wrapper = document.getElementById("icon-wrapper");
    var theme_div = document.getElementById("theme-div");
    
    
    var logo = document.getElementById("logo");
  
    var filter_div = document.getElementById("filter-div");
  
    
    document.documentElement.scrollTop = 0;
    document.body.scrollTop = 0;
    main_form.setAttribute("class", "mainform_search");
    row_1.setAttribute("class", "row1_search");
    row_2.setAttribute("class", "row2_search");
    row_3.setAttribute("class", "row3_search");
    logo.style.display = "none";
    filter_div.style.display = "none";
   
    
    result_panel_middle.setAttribute("class", "result-panel-middle-hide");
    google_panel.setAttribute("class", "google-panel-width");
    forum_result_div.style.display = "none";
    forum_div.style.display = "none";
    forum_panel.style.display = "none";
    google_result_div.style.display = "none";
    format_filter.style.display = "none";
    result_panel.style.display = "";
 
    create_loading_icon(false);
    loading_icon.style.display = "";
    google_div.style.display = '';
    
    
    icon_wrapper.appendChild(theme_div);
    icon_wrapper.setAttribute("class", "row4_search");
    
    
    status_hub = [];
    hub_word = input_keyword;
    add_status_hub('normal', input_keyword);
    
    // submit to fetch web result
    var ul = document.getElementById("result-ul");
    ul.innerHTML = '';
    fetchword(input_keyword, false);
    
    // submit priced
    // submitword_priced(input_keyword);
    
    return false;
}




/****************************/
/*  parse result related  * /
 /****************************/


var format = ['PDF', 'TXT', 'MOBI', 'EPUB', 'AZW', 'DOC'];
var format_exp = [/pdf/i, /txt/i, /mobi(?!le)/i, /epub/i, /azw/i, /doc(?!ument)/i];
var format_other = 'OTHER';
/*
 for (var i = 0; i < format.length; i++) {
 format_exp.push(new RegExp(format[i], 'i'));
 }
 */


function get_format_exp(str) {
    for (var i = 0; i < format.length; i++) {
        if (format[i].toUpperCase() === str.toUpperCase() ) {
            return format_exp[i];
        }
    }
    return false;
}


var format_sum = [];
function addto_format_sum(str) {
    
    
    for (var i = 0; i < format_sum.length; i++) {
        if (format_sum[i][0] === str) {
            format_sum[i][1] += 1;
            return false;
        }
    }
    
    format_sum.push([str, 1]);
}


var fetched = [];
var format_sum = [];
function decodeHtml(html) {
    var txt = document.createElement("textarea");
    txt.innerHTML = html;
    return txt.value;
}
function parse_fetched(str, word, loading_more) {
    
    var ul = document.getElementById("result-ul");
    var content = '';
    fetched = [];
    fetched_raw = str;
    
 
    for (var i = 0; i < fetched_raw.length; i++) {
        var l = document.createElement("a");
        l.href = fetched_raw[i][0];
        fetched_raw[i][3] = l.hostname;
        fetched_raw[i][1] = decodeHtml(fetched_raw[i][1]);
        fetched_raw[i][2] = decodeHtml(fetched_raw[i][2]);
        
        
        
        // potential prefix mask remove
        prefix_mask = "/url?q=";
        if (fetched_raw[i][0].indexOf(prefix_mask) >= 0) {
            fetched_raw[i][0] = fetched_raw[i][0].substring( fetched_raw[i][0].indexOf(prefix_mask) +  prefix_mask.length );
            fetched_raw[i][0] = decodeURIComponent(fetched_raw[i][0]);
        }
        
        
        // remove duplicate item
        var duplicate = false;
        for (var j = 0; j < fetched.length; j++) {
            if (fetched[j][0].indexOf(fetched_raw[i][0]) >= 0 || fetched_raw[i][0].indexOf(fetched[j][0]) >= 0) {
                duplicate = true;
                break;
            }
        }
        if (duplicate) {
            continue;
        }
        
        
        // modify &sa=U parameter url; appearred in all sources
        var modify_pos = fetched_raw[i][0].indexOf('&amp;sa=U');
        fetched_raw[i][0] = fetched_raw[i][0].substring(fetched_raw[i][0], modify_pos != -1 ? modify_pos : fetched_raw[i][0].length);
        
        
        fetched.push(fetched_raw[i]);
    }
    
    
    // sort based on word
    // fetched.sort(function (a, b) { return exact_match_rate(word, b[1], b[2], b[3]) - exact_match_rate(word, a[1], a[2], a[3]); });
    
    // sort gentally
    var array_high = [];
    var array_low = [];
    for (var i=0; i<fetched.length; i++) {
        if (exact_match_rate(word, fetched[i][1], fetched[i][2], fetched[i][3]) > 1000) {
            array_high.push(fetched[i]);
        }
        else {
            array_low.push(fetched[i]);
        }
    }
    fetched = array_high.concat(array_low);
    
    if (log_enabled) {
        for (var i=0; i<fetched.length; i++) {
            fetched[i][2] += ' - ' + exact_match_rate(word, fetched[i][1], fetched[i][2], fetched[i][3]);
        }
    }
    
    
    
    // sort to put vdisk on rear
    var array_stable = [];
    var array_vdisk = [];
    for (var i=0; i<fetched.length; i++) {
        if (fetched[i][3] === 'vdisk.weibo.com') {
            array_vdisk.push(fetched[i]);
        }
        else {
            array_stable.push(fetched[i]);
        }
    }
    fetched = array_stable.concat(array_vdisk);
    
    
    
    format_sum = [];
    for (var i = 0; i < fetched.length; i++) {
        
        content += '<li style="list-style: none; line-height: 1.2;"><div style="margin-bottom: 15px;">';
        content += '<div><a href="' + fetched[i][0] + '" target="_blank" ><span style="font-size: 18px; font-family: arial,sans-serif;">' + fetched[i][1] + '<span></a></div>';
        content += '<div class="span-des" >' + fetched[i][2] + '</div>';
        content += '<div class="span-host" >' + fetched[i][3] + '</div>';
        content += '</div></li>';
        var format_matched = false;
        for (var j = 0; j < format.length; j++) {
            if (fetched[i][1].match(format_exp[j]) || fetched[i][2].match(format_exp[j])) {
                addto_format_sum(format[j]);
                format_matched = true;
            }
        }
        if (!format_matched) {
            addto_format_sum(format_other); 
        }
    }
    
    
    
    if (loading_more) {
        var search_more = '<li style="list-style: none; line-height: 1.2;"><div id="loading-more" style="font-weight: bold; margin-top: 10px;">LOADING MORE <span id="loadingmore-eclipse">. . .</span></div></li>';
        content = content +  search_more;
    }
    
    ul.innerHTML = content;
    
    
    format_sum.sort(function (a, b) {
                    if (a[0] === format_other) {
                    return 1;
                    }
                    if (b[0] === format_other) {
                    return -1;
                    }
                    return b[1] - a[1];
                    });
    // extract format info
    var format_filter = document.getElementById("format-filter");
    var content2 = [];
    if (format_sum.length >= 2) {
        for (var i = 0; i < format_sum.length; i++) {
            var str = '<a id="' + format_sum[i][0] + '" href="#" onclick="return filter_content(\'' + format_sum[i][0] + '\')">' + format_sum[i][0] + "(" + format_sum[i][1] + ")</a>";
            content2.push(str);
        }
        
        var str = content2.join(' | ');
        format_filter.innerHTML = str;
        format_filter.style.display = 'inline-block';
    }
    
    
    
}




var priced = [];
function parse_priced(str, word, loading_more) {
    
    var priced_filter = document.getElementById("priced-filter");
    priced_filter.innerHTML = 'PRINTED BOOK';
    
    var ul = document.getElementById("forum-result");
    var content = '';
    priced = [];
    priced_raw = str;
    
    
    var limit_title_oneline = 20;
    var limit_des_oneline = 20;
    var limit_title;
    var limit_des;
    var length_title;
    var length_des;
    for (var i = 0; i < priced_raw.length; i++) {
        
        limit_title = 40;
        limit_des = 40;
        
        priced_raw[i][0] = 'kongfz_img.php?url=' + encodeURIComponent(priced_raw[i][0]); // pic
        
        priced_raw[i][1] = priced_raw[i][1]; // href
        priced_raw[i][2] = allTrimText(decodeHtml(priced_raw[i][2])) // title
        length_title = priced_raw[i][2].length;
        if (length_title > limit_title) {
            priced_raw[i][2] = priced_raw[i][2].substring(0,limit_title-3) + '...';
        }
        if (length_title > limit_title_oneline) {
            limit_des = limit_des_oneline;
        }
        
        priced_raw[i][3] = allTrimText(decodeHtml(priced_raw[i][3])) // des
        length_des = priced_raw[i][3].length;
        if (length_des > limit_des) {
            priced_raw[i][3] = priced_raw[i][3].substring(0,limit_des-3) + '...';
        }
        
        priced_raw[i][4] = allTrimText(decodeHtml(priced_raw[i][4])) // price
        priced_raw[i][5] = allTrimText(decodeHtml(priced_raw[i][5])) // used
        priced_raw[i][6] = allTrimText(decodeHtml(priced_raw[i][6])) // time
        
        
        priced.push(priced_raw[i]);
    }
    
    format_sum = [];
    content += '<a target="_blank" href="http://search.kongfz.com"><span class="span-host">纸质图书资源 (kongfz.com):</span></a>';
    content += '<ul style="padding-left: 0px;">';
    
    
    for (var i = 0; i < priced.length; i++) {
        
        
        content += '<li style="margin-bottom:10px;">';
        
        // content += '<div style="display: inline-block;width: 82px;vertical-align: top;"><a target="_blank" href="' + priced[i][1] + '"><img src="' + priced[i][0] + '" style="width: 80px;max-height: 80px;"></a></div>';
        
        
        // content += '<div style="display: inline-block;width: 230px;vertical-align: top;"><a target="_blank" href="' + priced[i][1] + '" style="font-weight: bold;">' + priced[i][2] +'</a><div><span style="float:left;">发布时间: ' + priced[i][6] + '</span><span style="float:right;">状态: ' + priced[i][5] + '</span></div><div></div><div>' +  priced[i][3] + '</div></div>';
        
        content += '<div class="priced_item_left"><a target="_blank" href="' + priced[i][1] + '" style="font-weight: bold;">' + priced[i][2] +'</a><div><span style="float:left;">发布时间: ' + priced[i][6] + '</span><span style="float:right;">状态: ' + priced[i][5] + '</span></div><div></div><div>' +  priced[i][3] + '</div></div>';
        
        content +=  '<div class="priced_item_right"><div>¥' + priced[i][4] +'</div></div>';

    }
    content += '</ul>';
    
    ul.innerHTML = content;
    
}









function notfound_alert() {
    var search_word = document.getElementById("SearchWord");
    var input_keyword = search_word.value;
    var ul = document.getElementById("result-ul");
    var content = '';
    
    content = '<div style="font-size: 16px;"><br><b>' + input_keyword + '</b> - is not found.<br><br><br>Suggestions: <br><ul style="line-height: 1.2;margin-top: 10px;"><li>Make sure all words are spelled correctly.</li><li>Try different keywords.</li><li>Try more general keywords.</li></ul></div>';
  
    
    ul.innerHTML = content;
}



function error_alert(msg) {
    var search_word = document.getElementById("SearchWord");
    var input_keyword = search_word.value;
    var ul = document.getElementById("result-ul");
    var content = '';
    
    
    if (msg === 'exceed') {
        content = '<div style="font-size: 16px;"><br><span style="font-weight:bold;">Information</span><br><br> - You have reached search limits today, some results will not be able to show. Please try again tomorrow. Thanks.</div>' + '<div style="font-size: 17px;"><br><span style="font-size: 18px; font-weight:bold">提示</span><br><br> - 您的查询已达到今日的最高次数, 一些结果将无法显示. 请明日再试. 谢谢.</div>';
        
    }
    else {
        content = '<div style="font-size: 16px;"><br><b>ERROR</b><br><br> - An error has occurred, Please <b><a href="#" onclick="return validate();" style="text-decoration: underline;">try again</a></b>.</div>';
    }
    
    
    ul.innerHTML = content;
}



function filter_content(str) {
    
    var ul = document.getElementById("result-ul");
    var google_div = document.getElementById('google-div');
    var format_filters = document.getElementById('format-filter');
    var format_fs = format_filters.getElementsByTagName('a');
    for (var i = 0; i < format_fs.length; i++) {
        if (format_fs[i].getAttribute('id') === str) {
            format_fs[i].setAttribute("style", "font-weight: bolder;");
        }
        else {
            format_fs[i].setAttribute("style", "font-weight: normal;");
        }
        
    }
    
    
    var filtered = [];
    if (str === format_other) {
        filtered = filter_other();
    }
    else
    {
        filtered = filter_format(str);
    }
    
    
    var f_content = '';
    for (var i = 0; i < filtered.length; i++) {
        f_content += '<li style="list-style: none; line-height: 1.2;"><div style="">';
        f_content += '<a href="' + filtered[i][0] + '" target="_blank" ><span style="font-size: 18px; font-family: arial,sans-serif;">' + filtered[i][1] + '<span></a>';
        f_content += '<br>';
        f_content += '<span class="span-des" >' + filtered[i][2] + '</span>';
        f_content += '<br>';
        f_content += '<span class="span-host" >' + filtered[i][3] + '</span>';
        f_content += '<br>';
        f_content += '<br></div></li>';
    }
    ul.innerHTML = f_content;
    google_div.scrollTop = 0;
    
    return false;
}


function filter_format(str) {
    
    var str_exp = get_format_exp(str);
    var cached = [];
    var cached_strict = [];
    var cached_loose = [];
    for (var i = 0; i < fetched.length; i++) {
        var match = false;
        var total_matched = 0;
        if (fetched[i][1].match(str_exp) || fetched[i][2].match(str_exp)) {
            match = true;
        }
        
        for (var j = 0; j < format_exp.length; j++) {
            if (fetched[i][1].match(format_exp[j]) || fetched[i][2].match(format_exp[j])) {
                total_matched += 1;
            }
        }
        
        
        var link = fetched[i][0];
        var title = fetched[i][1];
        var des = fetched[i][2];
        var host = fetched[i][3];
        var replaced_title = '<span class="highlight">' + title.match(str_exp) + '</span>';
        var replaced_des = '<span class="highlight">' + des.match(str_exp) + '</span>';
        title = title.split(str_exp).join(replaced_title);
        des = des.split(str_exp).join(replaced_des);
        if (match) {
            if (total_matched <= 1) {
                cached_strict.push([link, title, des, host]);
            }
            else {
                cached_loose.push([link, title, des, host]);
            }
        }
        
    }
    
    cached = cached_strict.concat(cached_loose);
    return cached;
}




function filter_other() {
    
    var cached = [];
    for (var i = 0; i < fetched.length; i++) {
        var match = false;
        for (var j = 0; j < format.length; j++) {
            if (fetched[i][1].match(format_exp[j]) || fetched[i][2].match(format_exp[j])) {
                match = true;
                break;
            }
        }
        
        if (!match) {
            var link = fetched[i][0];
            var title = fetched[i][1];
            var des = fetched[i][2];
            var host = fetched[i][3];
            cached.push([link, title, des, host]);
        }
    }
    return cached;
}



function hide_loading() {
    
    var loading_icon = document.getElementById("loading-icon");
    var google_result_div = document.getElementById("google-result-div");
    var google_panel = document.getElementById("google-panel");
    loading_icon.style.display = 'none';
    google_result_div.style.display = '';
    google_panel.style.display = 'inline-block';
    
}

function hide_loadingmore() {
    
    var loading_more = document.getElementById("loading-more");
    if (loading_more) {
        loading_more.style.display = 'none';
    }
    
}



function toggle_visibility(id) {
    var e = document.getElementById(id);
    if(e.style.display == 'block')
        e.style.display = 'none';
    else
        e.style.display = 'block';
}


</script>


<div id="wechat-overlay" onclick="wechat_overlay_hide();" style="
display: none;
position: absolute;
width: 100%;
height: 100%;
top: 0px;
left: 0px;
z-index: 100;
text-align:center;
vertical-align:middle;
background-color: rgba(0,0,0,0);
padding-top: 150px;
">
</div>


</body>
</html>

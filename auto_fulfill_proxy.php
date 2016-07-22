<?php
    
    include_once ('header_config.php');
    include_once ('hitcounter/counter_config.php');
    include_once ('simple_html_dom.php');
    

    
  
     
    // get url name
    function get_url_name($url) {
        return reset(explode("/", end(explode("://", $url, 2)), 2));
    }
    

    function parse_raw($url_prefix, $ports, &$log){
        
        $raw_list = array();
        
        
        foreach ($ports as $port) {
        
            $url = $url_prefix . $port;
            
            // create curl resource
            $ch = curl_init();
         
            // set url
            curl_setopt($ch, CURLOPT_URL, $url);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        
            // $output contains the output string
            $output = curl_exec($ch);
        
        
            if (curl_errno($ch)) {
                $estr = curl_error($ch);
                $status = 'error';
                $log = $estr;
                return $raw_list;
            }
        
        
            curl_close($ch);
        

            $ip_pattern = '/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/';
            $ret = preg_match_all($ip_pattern, $output, $matches);
            
            $raw_ips = array_unique($matches[0]);
            
            foreach($raw_ips as $ip_item) {
                $raw_list[] = array($ip_item, $port);
            }
        }

        
        
        $log = count($raw_list);
        
        return $raw_list;
    }
    
    
    
    
    // previous google proxy
    
    
    function proxy_fetch_check($link, $search_handler, $str, &$status, &$log, $keyword, &$keyword_count) {
        
        $handler = $search_handler['handler'];
        $mirror_prefix = $search_handler['mirror_prefix'];
        $proxy_address = $search_handler['proxy_address'];
        $proxy_port = $search_handler['proxy_port'];
        $proxy_type = $search_handler['proxy_type'];
        
        
        $fetched = array();
        $url = "";
        $keyword_count = 0;
        
        
         

        if ($handler === 'proxy') {
            
            $result = mysql_query("SELECT id FROM words_proxy WHERE address='$proxy_address' AND port='$proxy_port'", $link);
            $row = mysql_fetch_array($result);
            
            
            if ($row) {
                $status = 'proxy already existed';
                $log = $row['id'];
                return $fetched;
                
            }
            
        }
        
        else {
            $status = 'escape error';
            $log = 'handler is not proxy';
            return $fetched;
        }
        
        
        
        
        // create curl resource
        $ch = curl_init();
        
        
        
        // PROXY type href
        $url = "https://www.google.com/search?num=40&q=" . $str;
        
        
        
        $proxy = $search_handler['proxy_address'].':'.$search_handler['proxy_port'];
        
        curl_setopt($ch, CURLOPT_PROXY, $proxy);
        if ($search_handler['proxy_type'] === "socks5") {
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
        }
        
        
        
        // set url
        curl_setopt($ch, CURLOPT_URL, $url);
        
        // just send empty cookie.
        curl_setopt($ch, CURLOPT_COOKIE, '');
        
        //return the transfer as a string
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $user_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.111 Safari/537.36';
        curl_setopt($ch,CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_CONNECTIONTIMEOUT, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        
        // $output contains the output string
        $output = curl_exec($ch);
        
        
        
        if (curl_errno($ch)) {
            $estr = curl_error($ch);
            $status = 'error';
            $log = $estr;
            return $fetched;
        }
        
        
        
        $last_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        
        
        // check whether robot
        $robot_exp = "sorry/IndexRedirect";
        if (strpos($last_url, $robot_exp) !== false) {
            $status = 'robot';
            $log = 'robot';
            return $fetched;
        }
        
        
        
        // close curl resource to free up system resources
        curl_close($ch);
        
        

        // parse the html page
        $html = str_get_html($output);
        
        $ret = $html->find('.g');
        
        // check result existance
        if (count($ret) === 0) {
            
            $nofound_msgs = $html->find('.mnr-c', 0);
            if ($nofound_msgs) {
                $status = 'empty';
                $log = 'empty';
                return $fetched;
            }
            
            
            $nofound_msgs2 = $html->find('ul._Gnc', 0);
            if ($nofound_msgs2) {
                $status = 'empty';
                $log = 'empty2';
                return $fetched;
            }
            
            $status = 'unknown';
            $log = 'unknown';
            
            return $fetched;
        }
        
        
        
        
        
        foreach ($ret as $value) {
            $li = array();
            $l = $value->find('a', 0);
            
            $href_raw = $l->href;
            
            
            // for ie specific,  to remove search engine prefix;
            $href = end(explode("/url?url=", $href_raw, 2));
            
            // check whether mirror masked
            if ($search_handler['handler'] === 'mirror' && get_url_name($href) === get_url_name($search_handler['mirror_prefix'])) {
                $fetched = array();
                $status = 'error';
                $log = 'mirror masked';
                return $fetched;
            }
            
            
            $title = $l->plaintext;
            
            $s = $value->find('.st', 0);
            $des = $s->plaintext;
            
            $fetched[] = array($href, $title, $des);
            
            
            /////
            // if (stripos($title, rawurldecode($keyword) ) !== false){
            //     $keyword_count = $keyword_count + 1;
            // }
            //
 
        }
        
        
        $status = 'succeed';
        return $fetched;
        
    }
    
    
    
    // start parse result
    function proxy_fetch_check_ishare($link, $search_handler, $str, &$status, &$log, $keyword, &$keyword_count) {
        
        $handler = $search_handler['handler'];
        $mirror_prefix = $search_handler['mirror_prefix'];
        $proxy_address = $search_handler['proxy_address'];
        $proxy_port = $search_handler['proxy_port'];
        $proxy_type = $search_handler['proxy_type'];
        
        
        $fetched = array();
        $url = "";
        $keyword_count = 0;
        
        
        
        
        if ($handler === 'proxy') {
            
            $result = mysql_query("SELECT id FROM wordsishare_proxy WHERE address='$proxy_address' AND port='$proxy_port'", $link);
            $row = mysql_fetch_array($result);
            
            
            if ($row) {
                $status = 'proxy already existed';
                $log = $row['id'];
                return $fetched;
                
            }
            
        }
        
        else {
            $status = 'escape error';
            $log = 'handler is not proxy';
            return $fetched;
        }
        
        
        
        
        // create curl resource
        $ch = curl_init();
        
        
        
        // PROXY type href
        $url = "http://ishare.iask.sina.com.cn";
        
        
        
        $proxy = $search_handler['proxy_address'].':'.$search_handler['proxy_port'];
        
        curl_setopt($ch, CURLOPT_CONNECTIONTIMEOUT, 2);
        
        curl_setopt($ch, CURLOPT_PROXY, $proxy);
        if ($search_handler['proxy_type'] === "socks5") {
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
        }
        
        
        
        // set url
        curl_setopt($ch, CURLOPT_URL, $url);
        
        // just send empty cookie.
        curl_setopt($ch, CURLOPT_COOKIE, '');
        
        //return the transfer as a string
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        
        curl_setopt( $ch, CURLOPT_ENCODING, '');
        
        // use static user agent to avoid constant unknown issue, try & test
        $user_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.111 Safari/537.36';
        
        curl_setopt($ch,CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTIONTIMEOUT, 1);
        
        
        // $output contains the output string
        $output = curl_exec($ch);
        
        
        
        if (curl_errno($ch)) {
            $estr = curl_error($ch);
            $status = 'error';
            $log = $estr;
            return $fetched;
        }
        
        
        // close curl resource to free up system resources
        curl_close($ch);
        
        
        
        // parse the html page
        $html = str_get_html($output);
        
        $ret = $html->find('input.search-input');
        
        // check result existance
        if (count($ret) === 0) {
            
            
            $status = 'unknown';
            $log = 'unknown';
            return $fetched;
        }
        
        
        $status = 'succeed';
        return $fetched;
        
    }
    
    
    
    // start parse result
    function proxy_fetch_check_pkongfz($link, $search_handler, $str, &$status, &$log, $keyword, &$keyword_count) {
        
        $handler = $search_handler['handler'];
        $mirror_prefix = $search_handler['mirror_prefix'];
        $proxy_address = $search_handler['proxy_address'];
        $proxy_port = $search_handler['proxy_port'];
        $proxy_type = $search_handler['proxy_type'];
        
        
        $fetched = array();
        $url = "";
        $keyword_count = 0;
        
        
        
        
        if ($handler === 'proxy') {
            
            $result = mysql_query("SELECT id FROM wordspkongfz_proxy WHERE address='$proxy_address' AND port='$proxy_port'", $link);
            $row = mysql_fetch_array($result);
            
            
            if ($row) {
                $status = 'proxy already existed';
                $log = $row['id'];
                return $fetched;
                
            }
            
        }
        
        else {
            $status = 'escape error';
            $log = 'handler is not proxy';
            return $fetched;
        }
        
        
        
        
        // create curl resource
        $ch = curl_init();
        
        
        
        // PROXY type href
        $url = "http://search.kongfz.com";
        
        
        
        $proxy = $search_handler['proxy_address'].':'.$search_handler['proxy_port'];
        
        curl_setopt($ch, CURLOPT_CONNECTIONTIMEOUT, 2);
        
        curl_setopt($ch, CURLOPT_PROXY, $proxy);
        if ($search_handler['proxy_type'] === "socks5") {
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
        }
        
        
        
        // set url
        curl_setopt($ch, CURLOPT_URL, $url);
        
        // just send empty cookie.
        curl_setopt($ch, CURLOPT_COOKIE, '');
        
        //return the transfer as a string
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        
        curl_setopt( $ch, CURLOPT_ENCODING, '');
        
        // use static user agent to avoid constant unknown issue, try & test
        $user_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.111 Safari/537.36';
        
        curl_setopt($ch,CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTIONTIMEOUT, 1);
        
        
        // $output contains the output string
        $output = curl_exec($ch);
        
        
        
        if (curl_errno($ch)) {
            $estr = curl_error($ch);
            $status = 'error';
            $log = $estr;
            return $fetched;
        }
        
        
        // close curl resource to free up system resources
        curl_close($ch);
        
        
        
        // parse the html page
        $html = str_get_html($output);
        
        $ret = $html->find('div[id=search_box]');
        
        // check result existance
        if (count($ret) === 0) {
            
            
            $status = 'unknown';
            $log = 'unknown';
            return $fetched;
        }
        
        
        $status = 'succeed';
        return $fetched;
        
    }
    
    
    
    // start parse result
    function proxy_fetch_check_eng($link, $search_handler, $str, &$status, &$log, $keyword, &$keyword_count) {
        
        $handler = $search_handler['handler'];
        $mirror_prefix = $search_handler['mirror_prefix'];
        $proxy_address = $search_handler['proxy_address'];
        $proxy_port = $search_handler['proxy_port'];
        $proxy_type = $search_handler['proxy_type'];
        
        
        $fetched = array();
        $url = "";
        $keyword_count = 0;
        
        
        
        
        if ($handler === 'proxy') {
            
            $result = mysql_query("SELECT id FROM wordseng_proxy WHERE address='$proxy_address' AND port='$proxy_port'", $link);
            $row = mysql_fetch_array($result);
            
            
            if ($row) {
                $status = 'proxy already existed';
                $log = $row['id'];
                return $fetched;
                
            }
            
        }
        
        else {
            $status = 'escape error';
            $log = 'handler is not proxy';
            return $fetched;
        }
        
        
        
        
        // create curl resource
        $ch = curl_init();
        
        
        
        // PROXY type href
        $url = "http://" . get_libgen() . "/search.php";
        // $url = "http://gen.lib.rus.ec/search.php";
        
        
        
        $proxy = $search_handler['proxy_address'].':'.$search_handler['proxy_port'];
        
        curl_setopt($ch, CURLOPT_PROXY, $proxy);
        if ($search_handler['proxy_type'] === "socks5") {
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
        }
        
        
        
        // set url
        curl_setopt($ch, CURLOPT_URL, $url);
        
        // just send empty cookie.
        curl_setopt($ch, CURLOPT_COOKIE, '');
        
        //return the transfer as a string
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $user_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.111 Safari/537.36';
        curl_setopt($ch,CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_CONNECTIONTIMEOUT, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        
        // $output contains the output string
        $output = curl_exec($ch);
        
        
        
        if (curl_errno($ch)) {
            $estr = curl_error($ch);
            $status = 'error';
            $log = $estr;
            return $fetched;
        }
        
        
        // close curl resource to free up system resources
        curl_close($ch);
        
        
        
        // parse the html page
        $html = str_get_html($output);
        
        $ret = $html->find('form[name=libgen]');
        
        // check result existance
        if (count($ret) === 0) {
            
            
            $status = 'unknown';
            $log = 'unknown';
            return $fetched;
        }
        
        
        $status = 'succeed';
        return $fetched;
        
    }
    
    
    
    
    
    // start parse result
    function proxy_fetch_check_kat($link, $search_handler, $str, &$status, &$log, $keyword, &$keyword_count) {
        
        $handler = $search_handler['handler'];
        $mirror_prefix = $search_handler['mirror_prefix'];
        $proxy_address = $search_handler['proxy_address'];
        $proxy_port = $search_handler['proxy_port'];
        $proxy_type = $search_handler['proxy_type'];
        
        
        $fetched = array();
        $url = "";
        $keyword_count = 0;
        
        
        
        
        if ($handler === 'proxy') {
            
            $result = mysql_query("SELECT id FROM wordskat_proxy WHERE address='$proxy_address' AND port='$proxy_port'", $link);
            $row = mysql_fetch_array($result);
            
            
            if ($row) {
                $status = 'proxy already existed';
                $log = $row['id'];
                return $fetched;
                
            }
            
        }
        
        else {
            $status = 'escape error';
            $log = 'handler is not proxy';
            return $fetched;
        }
        
        
        
        
        // create curl resource
        $ch = curl_init();
        
        
        
        // PROXY type href
        $url = "https://kat.cr";
        
        
        
        $proxy = $search_handler['proxy_address'].':'.$search_handler['proxy_port'];
        
        curl_setopt($ch, CURLOPT_PROXY, $proxy);
        if ($search_handler['proxy_type'] === "socks5") {
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
        }
        
        
        
        // set url
        curl_setopt($ch, CURLOPT_URL, $url);
        
        // just send empty cookie.
        curl_setopt($ch, CURLOPT_COOKIE, '');
        
        //return the transfer as a string
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $user_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.111 Safari/537.36';
        curl_setopt($ch,CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_CONNECTIONTIMEOUT, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        
        curl_setopt( $ch, CURLOPT_ENCODING, '');

        
        // $output contains the output string
        $output = curl_exec($ch);
        
        
        
        if (curl_errno($ch)) {
            $estr = curl_error($ch);
            $status = 'error';
            $log = $estr;
            return $fetched;
        }
        
        
        // close curl resource to free up system resources
        curl_close($ch);
        
        
        
        // parse the html page
        $html = str_get_html($output);
        
        $ret = $html->find('form[id=searchform]');
        
        // check result existance
        if (count($ret) === 0) {
            
            
            $status = 'unknown';
            $log = 'unknown';
            return $fetched;
        }
        
        
        $status = 'succeed';
        return $fetched;
        
    }

    
    
    
    
    // start parse result
    function proxy_fetch_check_bing($link, $search_handler, $str, &$status, &$log, $keyword, &$keyword_count) {
        
        $handler = $search_handler['handler'];
        $mirror_prefix = $search_handler['mirror_prefix'];
        $proxy_address = $search_handler['proxy_address'];
        $proxy_port = $search_handler['proxy_port'];
        $proxy_type = $search_handler['proxy_type'];
        
        
        $fetched = array();
        $url = "";
        $keyword_count = 0;
        
        
        
        
        if ($handler === 'proxy') {
            
            $result = mysql_query("SELECT id FROM wordsbing_proxy WHERE address='$proxy_address' AND port='$proxy_port'", $link);
            $row = mysql_fetch_array($result);
            
            
            if ($row) {
                $status = 'proxy already existed';
                $log = $row['id'];
                return $fetched;
                
            }
            
        }
        
        else {
            $status = 'escape error';
            $log = 'handler is not proxy';
            return $fetched;
        }
        
        
        
        
        // create curl resource
        $ch = curl_init();
        
        
        
        // PROXY type href
        $url = "https://www.bing.com/search?q=" . $str;
        
        
        
        $proxy = $search_handler['proxy_address'].':'.$search_handler['proxy_port'];
        
        curl_setopt($ch, CURLOPT_PROXY, $proxy);
        if ($search_handler['proxy_type'] === "socks5") {
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
        }
        
        
        
        // set url
        curl_setopt($ch, CURLOPT_URL, $url);
        
        // just send empty cookie.
        curl_setopt($ch, CURLOPT_COOKIE, '');
        
        //return the transfer as a string
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $user_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.111 Safari/537.36';
        curl_setopt($ch,CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_CONNECTIONTIMEOUT, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        
        // $output contains the output string
        $output = curl_exec($ch);
        
        
        
        if (curl_errno($ch)) {
            $estr = curl_error($ch);
            $status = 'error';
            $log = $estr;
            return $fetched;
        }
        
        
        
        $last_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        
        
        // check whether robot
        $robot_exp = "sorry/IndexRedirect";
        if (strpos($last_url, $robot_exp) !== false) {
            $status = 'robot';
            $log = 'robot';
            return $fetched;
        }
        
        
        
        // close curl resource to free up system resources
        curl_close($ch);
        
        
        
        // parse the html page
        $html = str_get_html($output);
        
        $ret = $html->find('.b_algo');
        
        // check result existance
        if (count($ret) === 0) {
            
            $nofound_msgs = $html->find('.b_no');
            if ($nofound_msgs) {
                $status = 'empty';
                $log = 'empty';
            }
            else {
                $status = 'unknown';
                $log = 'unknown';
            }
            
            return $fetched;
        }
        
        
        
        
        
        foreach ($ret as $value) {
            $li = array();
            $l = $value->find('a', 0);
            
            $href_raw = $l->href;
            
            
            // for ie specific,  to remove search engine prefix;
            $href = end(explode("/url?url=", $href_raw, 2));
            
            
            $title = $l->plaintext;
            
            $s = $value->find('p', 0);
            $des = $s->plaintext;
            
            $fetched[] = array($href, $title, $des);
            
            
            if (stripos($title, $keyword) !== false){
                $keyword_count = $keyword_count + 1;
            }
            
        }
        
        
        $status = 'succeed';
        return $fetched;
        
    }
    
    
    
    // start parse result
    function proxy_fetch_check_yahoo($link, $search_handler, $str, &$status, &$log, $keyword, &$keyword_count) {
        
        $handler = $search_handler['handler'];
        $mirror_prefix = $search_handler['mirror_prefix'];
        $proxy_address = $search_handler['proxy_address'];
        $proxy_port = $search_handler['proxy_port'];
        $proxy_type = $search_handler['proxy_type'];
        
        
        $fetched = array();
        $url = "";
        $keyword_count = 0;
        
        
        
        
        if ($handler === 'proxy') {
            
            $result = mysql_query("SELECT id FROM wordsyahoo_proxy WHERE address='$proxy_address' AND port='$proxy_port'", $link);
            $row = mysql_fetch_array($result);
            
            
            if ($row) {
                $status = 'proxy already existed';
                $log = $row['id'];
                return $fetched;
                
            }
            
        }
        
        else {
            $status = 'escape error';
            $log = 'handler is not proxy';
            return $fetched;
        }
        
        
        
        
        // create curl resource
        $ch = curl_init();
        
        
        
        // PROXY type href
        $url = "https://search.yahoo.com/search?p=" . $str;
        
        
        
        $proxy = $search_handler['proxy_address'].':'.$search_handler['proxy_port'];
        
        curl_setopt($ch, CURLOPT_PROXY, $proxy);
        if ($search_handler['proxy_type'] === "socks5") {
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
        }
        
        
        
        // set url
        curl_setopt($ch, CURLOPT_URL, $url);
        
        // just send empty cookie.
        curl_setopt($ch, CURLOPT_COOKIE, '');
        
        //return the transfer as a string
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $user_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.111 Safari/537.36';
        curl_setopt($ch,CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_CONNECTIONTIMEOUT, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        
        // $output contains the output string
        $output = curl_exec($ch);
        
        
        
        if (curl_errno($ch)) {
            $estr = curl_error($ch);
            $status = 'error';
            $log = $estr;
            return $fetched;
        }
        
        
        
        $last_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        
        
        // check whether robot
        $robot_exp = "sorry/IndexRedirect";
        if (strpos($last_url, $robot_exp) !== false) {
            $status = 'robot';
            $log = 'robot';
            return $fetched;
        }
        
        
        
        // close curl resource to free up system resources
        curl_close($ch);
        
        
        
        // parse the html page
        $html = str_get_html($output);
        
        $ret_ol = $html->find('ol.searchCenterMiddle', 0);
        if (!ret_ol) {
            
            $nofound_msgs = $html->find('li[class="first last"]', 0);
            if ($nofound_msgs) {
                $status = 'empty';
                $log = 'empty';
            }
            else {
                $status = 'unknown';
                $log = 'unknown';
            }
            return $fetched;
        }
        
        
        $ret = $ret_ol->children();
        
        // check result existance
        if (count($ret) === 0) {
            
            
            $nofound_msgs = $html->find('li[class="first last"]', 0);
            if ($nofound_msgs) {
                $status = 'empty';
                $log = 'empty2';
            }
            else {
                $status = 'unknown';
                $log = 'unknown2';
            }
            return $fetched;
            
        }
        
        
        
        
        
        foreach ($ret as $value) {
            $li = array();
            
            $t = $value->find('.title', 0);
            $title = $t->plaintext;
            
            $l_div = $t->next_sibling();
            $href = $l_div->plaintext;
            
            
            // for ie specific,  to remove search engine prefix;
            $href = end(explode("/url?url=", $href_raw, 2));
            
            
            $s = $value->find('.compText', 0);
            $des = $s->plaintext;
            
            // if not valid values, skip
            if (! ($href && $title && des)) {
                continue;
            }
            
            
            $fetched[] = array($href, $title, $des);
            
            
            if (stripos($title, $keyword) !== false){
                $keyword_count = $keyword_count + 1;
            }
            
        }
        
        
        $status = 'succeed';
        return $fetched;
        
    }
    


    
    
    // start parse result
    function proxy_fetch_check_weibo($link, $search_handler, $str, &$status, &$log, $keyword, &$keyword_count) {
        
        $handler = $search_handler['handler'];
        $mirror_prefix = $search_handler['mirror_prefix'];
        $proxy_address = $search_handler['proxy_address'];
        $proxy_port = $search_handler['proxy_port'];
        $proxy_type = $search_handler['proxy_type'];
        
        
        $fetched = array();
        $url = "";
        $keyword_count = 0;
        
        
        
        
        if ($handler === 'proxy') {
            
            $result = mysql_query("SELECT id FROM wordsweibo_proxy WHERE address='$proxy_address' AND port='$proxy_port'", $link);
            $row = mysql_fetch_array($result);
            
            
            if ($row) {
                $status = 'proxy already existed';
                $log = $row['id'];
                return $fetched;
                
            }
            
        }
        
        else {
            $status = 'escape error';
            $log = 'handler is not proxy';
            return $fetched;
        }
        
        
        
        
        // create curl resource
        $ch = curl_init();
        
        
        
        // PROXY type href
        $url = "http://vdisk.weibo.com/search/?type=public&sortby=default&filetype=doc&keyword=" . $keyword;
        
        
        $proxy = $search_handler['proxy_address'].':'.$search_handler['proxy_port'];
        
        curl_setopt($ch, CURLOPT_PROXY, $proxy);
        if ($search_handler['proxy_type'] === "socks5") {
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
        }
        
        
        
        // set url
        curl_setopt($ch, CURLOPT_URL, $url);
        
        // just send empty cookie.
        curl_setopt($ch, CURLOPT_COOKIE, '');
        
        //return the transfer as a string
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $user_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.111 Safari/537.36';
        curl_setopt($ch,CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_CONNECTIONTIMEOUT, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        
        // $output contains the output string
        $output = curl_exec($ch);
        
        
        
        if (curl_errno($ch)) {
            $estr = curl_error($ch);
            $status = 'error';
            $log = $estr;
            return $fetched;
        }
        
        
        // close curl resource to free up system resources
        curl_close($ch);
        
        
        
        // parse the html page
        $html = str_get_html($output);
        
        // parse weibo
        
        $sort_main = $html->find('.v_sort_main', 0);
        
        
        // sort_main not found
        if (!$sort_main) {
            $status = 'unknown';
            $log = 'sort_main not found';
            return $fetched;
        }
        
        
        
        $sort_main_first = $sort_main->children(0);
        $sort_main_second = $sort_main->children(1);
        
        // sort_main_first not found
        if (!$sort_main_first) {
            $status = 'unknown';
            $log = 'sort_main_first not found';
            return $fetched;
        }
        
        
        
        // empty
        if ($sort_main_first->getAttribute('class') === 'vd_allnofind') {
            $status = 'empty';
            $log = 'backup result empty';
            return $fetched;
        }
        
        
        // sort_main_second not found
        if (!$sort_main_second) {
            $status = 'unknown';
            $log = 'sort_main_second not found';
            return $fetched;
        }
        
        
        // result table not found
        if ($sort_main_second->getAttribute('id') !== 'search_table') {
            $status = 'unknown';
            $log = 'result table not found';
            return $fetched;
        }
        
        
        
        // start parse result
        $ret = $sort_main_second->find('.sort_name_intro');
        
        
        if (count($ret) === 0) {
            $status = 'unknown';
            $log = 'result table is empty';
            return $fetched;
        }
        
        
        foreach ($ret as $value) {
            $li = array();
            $l = $value->find('a', 0);
            
            $href = $l->href;
            
            $title = $l->plaintext;
            
            $s = $value->find('.sort_name_time', 0);
            $des = $s->plaintext;
            
            // if not valid values, skip
            if (! ($href && $title && des)) {
                continue;
            }
            
            $fetched[] = array($href, $title, $des);
        }
        
        
        if (count($fetched) === 0) {
            $status = 'unknown';
            $log = 'escaped2 no valid values left. original ret:' . count($ret);
            return $fetched;
        }
        
        
        $status = 'succeed';
        $log = 'result succeed';
        
        return $fetched;

        
    }
    
    
    
    
    
    
    
    
    function add_search_handler($link, $db, $search_handler, &$status, &$log) {
        
        
        $handler = $search_handler['handler'];
        $mirror_prefix = $search_handler['mirror_prefix'];
        $proxy_address = $search_handler['proxy_address'];
        $proxy_port = $search_handler['proxy_port'];
        $proxy_type = $search_handler['proxy_type'];
        
        
        // current time
        date_default_timezone_set("Asia/Hong_Kong");
        $datetime = date("Y/m/d") . ' ' . date('H:i:s');
        
        
        
        
        $result = mysql_query("SELECT * FROM $db WHERE address='$proxy_address' AND port='$proxy_port'", $link);
        
        $row = mysql_fetch_array($result);
            
            
        if (!$row) {
            $adddata = mysql_query("INSERT INTO $db (address, port, type, lasttime) VALUES('$proxy_address' , '$proxy_port', '', '$datetime' )", $link);
            $status = 'proxy added';
        }
        else {
            $status = 'proxy already existed';
        }
        
        return;
    }
    
    
    function active_proxy_count($link, $db) {

        $result_active = mysql_query("SELECT id FROM $db WHERE sick=0", $link);
        $count_active = mysql_num_rows($result_active);
        return $count_active;
    }
    
    
    
    function active_waiting_proxy_count($link, $db) {
        
        date_default_timezone_set("Asia/Hong_Kong");
        $datetime = date("Y/m/d") . ' ' . date('H:i:s');
      
        $mod_offset_proxy = '-' . '1' . ' minute';
        $datetime_mod_proxy = date("Y/m/d H:i:s", strtotime($mod_offset_proxy));
        
        $result_active = mysql_query("SELECT * FROM $db WHERE lasttime<'$datetime_mod_proxy' AND sick=0 ORDER BY lasttime", $link);
        $count_active = mysql_num_rows($result_active);
        return $count_active;
    }
    
    
    
    
    function delete_lagency_proxy($link, $db) {
        
        
        $result_total = mysql_query("SELECT id FROM $db", $link);
        $count_total = mysql_num_rows($result_total);
        
        // max stored proxy resources
        $count_max = 1000;
        // $sick_baseline = 20;
        
        $to_delete = $count_total- $count_max;
        if($to_delete > 0)
        {
            for ($i = 1; $i <= $to_delete; $i++)
            {
                $delete = mysql_query("DELETE FROM $db ORDER BY sick DESC LIMIT 1");
            }
        }

    }
    
    
    

    
    function auto_fulfill_proxy($link, $db) {
        
        if ( !($db === 'wordseng_proxy' || $db === 'wordskat_proxy' || $db === 'wordsweibo_proxy' || $db === 'wordsbing_proxy' || $db === 'words_proxy' || $db === 'wordsyahoo_proxy' || $db === 'wordspkongfz_proxy'  || $db === 'wordsishare_proxy') ) {
            return;
        }
        
        $count_active = active_proxy_count($link, $db);
        $count_active_waiting = active_waiting_proxy_count($link, $db);
        
        $fulfill_available = false;
        $fulfill_required = false;
        
        
        // pause if issue of site suspecious
        if ($count_active === 0) {
            return;
        }
        
        
        $count_active_limit = 15;
        if ($db === 'wordsbing_proxy' || $db === 'wordspkongfz_proxy'  || $db === 'wordsishare_proxy') {
            $count_active_limit = 30;
        }
        
        if ($count_active < $count_active_limit) {
            $fulfill_required = true;
        }
        if ($count_active < 80 && $count_active_waiting < 5) {
            $fulfill_required = true;
        }
        
        
        
        if ($fulfill_required) {
            
            $result = mysql_query("SELECT * FROM timespan_proxy WHERE name='$db' ", $link);
            $row = mysql_fetch_array($result);
            $lasttime = $row['lasttime'];
            $log = $row['log'];
            
            date_default_timezone_set("Asia/Hong_Kong");
            $datetime = date("Y/m/d") . ' ' . date('H:i:s');
            $mod_offset_proxy = '-' . '30' . ' minute';
            $datetime_mod_proxy = date("Y/m/d H:i:s", strtotime($mod_offset_proxy));
            
            if ($lasttime < $datetime_mod_proxy) {
                
                $log = $datetime . '; ' . $log;
                $adddata = mysql_query("UPDATE timespan_proxy SET lasttime='$datetime', log='$log' WHERE name='$db' ", $link);
                $fulfill_available = true;
                
            }
        }

        
        if (!$fulfill_required || !$fulfill_available) {
            return;
        }
        
        
        $url_prefix = "http://gatherproxy.com/embed/?p=" ;
        $ports = array(
                       '8080',
                       '3128'
                       );
        
        $log_parseraw = "";
        $raw_list = parse_raw($url_prefix, $ports, $log_parseraw);
        
        
        $word = "%E5%B0%91%E6%9C%89%E4%BA%BA"; // 少有人

        // generate query str
        $str = rawurlencode( get_query_str( rawurldecode($word) ) );
        
        
        $keyword_count = 0;
  
            
        foreach($raw_list as $raw_proxy) {
                
            $search_handler = array(
                                    "handler" => "proxy",
                                    "handler_id" => "",
                                    "mirror_prefix" => "",
                                    "proxy_address" => $raw_proxy[0],
                                    "proxy_port" => $raw_proxy[1],
                                    "proxy_type" => "http",
                                    "backup_name" => "",
                                    );
                
            $status = "";
            $log = "";
            $fetched = "";
            
            if ($db === 'wordseng_proxy') {
                $fetched = proxy_fetch_check_eng($link, $search_handler, $str, $status, $log, $word, $keyword_count);
            }
            if ($db === 'wordskat_proxy') {
                $fetched = proxy_fetch_check_kat($link, $search_handler, $str, $status, $log, $word, $keyword_count);
            }
            if ($db === 'wordsweibo_proxy') {
                $fetched = proxy_fetch_check_weibo($link, $search_handler, $str, $status, $log, $word, $keyword_count);
            }
            if ($db === 'wordsbing_proxy') {
                $fetched = proxy_fetch_check_bing($link, $search_handler, $str, $status, $log, $word, $keyword_count);
            }
            if ($db === 'wordsyahoo_proxy') {
                $fetched = proxy_fetch_check_yahoo($link, $search_handler, $str, $status, $log, $word, $keyword_count);
            }
            if ($db === 'words_proxy') {
                $fetched = proxy_fetch_check($link, $search_handler, $str, $status, $log, $word, $keyword_count);
            }
            if ($db === 'wordspkongfz_proxy') {
                $fetched = proxy_fetch_check_pkongfz($link, $search_handler, $str, $status, $log, $word, $keyword_count);
            }
            if ($db === 'wordsishare_proxy') {
                $fetched = proxy_fetch_check_ishare($link, $search_handler, $str, $status, $log, $word, $keyword_count);
            }
            
            
            $result_array['status'] = $status;
            $result_array['log'] = $log;
            $result_array['data'] = rawurlencode(json_encode($fetched));
                
            if ($status === "succeed") {
                if(count($fetched)>=7 || $db === 'wordseng_proxy' || $db === 'wordskat_proxy' || $db === 'wordspkongfz_proxy' || $db === 'wordsishare_proxy') {
                    add_search_handler($link, $db, $search_handler, $status, $log);
                }
            }
                
        }
        
        
        delete_lagency_proxy($link, $db);
        

    }
    
    

    

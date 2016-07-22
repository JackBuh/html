<?php

    
    

    // restrict word
    $restrict_MOD_word = rawurldecode('%E6%B1%82'); //求
    $restrict_MOD = array($restrict_MOD_word);
    $restrict_AND = array(rawurldecode('%E9%AB%98%E6%B8%85')); // 高清
    $restrict_OR = array(rawurldecode('%E4%B9%A6'), 'pdf', 'doc', 'txt', 'mobi', 'epub', 'rar', 'zip', 'azw'); // 书, ...
    
    
    // site filter
    $filters_google = array(
                    'yun.baidu.com',
                    'pan.baidu.com',
                    // 'vdisk.weibo.com',
                    // 'emuch.net',
                    'muchong.com',
                    'wodemo.com',
                    // 'www.xiaoshuotxt.com',
                    'www.xiaoshuotxt.net',
                    );
    
    
    
    // site filter bing
    $filters_bing = array(
                     'yun.baidu.com',
                     'pan.baidu.com',
                     'muchong.com',
                     'wodemo.com',
                     'www.xiaoshuotxt.net',
                     );
    
    
    // filters validation
    $filters_validation = array(
                                'baidu',
                                'baidu',
                                // 'weibo',
                                // 'emuch',
                                'muchong',
                                'wodemo',
                                'xiaoshuotxt',
                                );
    
    
    // wholeweb excluded
    $filters_exclude = array(
                             'amazon',
                             'google',
                             'dangdang',
                             'douban',
                             'taobao',
                             'imdb',
                             'ebay',
                             '.gov',
                             'jd.com',
                             'so.',
                             'search',
                             'nytimes.com',
                             'org.cn',
                             'chinadaily',
                             'com.cn',
                             'edu.cn',
                             'microsoft',   
    );
    
    
    
    // libgen sources
    $libgen_sources = array(
                            'libgen.io',
                            'gen.lib.rus.ec',
    );
    
    
    $libgen_index = 0;
    
    
    function get_libgen() {
        global $libgen_index, $libgen_sources;
        return $libgen_sources[$libgen_index];
    }
    
    /*
    function rotate_libgen_curr() {
        global $libgen_index, $libgen_sources;
        
        if ($libgen_index === count($libgen_sources) - 1) {
            $libgen_index = 0;
        }
        else {
            $libgen_index += 1;
        }
    }
    */
     
    
    
    
    
    
    function get_restrictword_str($word) {
        
        global $restrict_MOD_word, $restrict_MOD, $restrict_AND, $restrict_OR;
        
        // generate restractword str
        $restrict_MOD_2 = ' -' . implode(' -', $restrict_MOD);
        $restrict_AND_2 = ' -' . implode(' -', $restrict_AND);
        $restrict_OR_2 = ' (' . implode(' OR ', $restrict_OR) . ')';
        
        if (strpos($word, $restrict_MOD_word) !== false) {
            $restrict_MOD_2 = '';
        }
        
        $restrictword_str = $restrict_MOD_2 . $restrict_AND_2 . $restrict_OR_2;
        return $restrictword_str;
    }
    
    
    
    // generate query str
    function get_query_str($word, $source = "google") {
        
        global $filters_google, $filters_bing;
        
        // restrict_word_str
        $restrictword_str = get_restrictword_str($word);
        
        
        // final query str
        $str = '';
        if ($source === "bing") {
            // generate filters str
            $filters = $filters_bing;
            $filters_str = ' site:' . $filters[0];
            for ($i = 1; $i < count($filters); $i++) {
                $filters_str = $filters_str . ' OR site:' . $filters[$i];
            }
            $filters_str = ' (' . $filters_str . ')';
            $str = $word . $restrictword_str;
        }
        else {
            // generate filters str
            $filters = $filters_google;
            $filters_str = ' site:' . $filters[0];
            for ($i = 1; $i < count($filters); $i++) {
                $filters_str = $filters_str . ' OR site:' . $filters[$i];
            }
            $filters_str = ' (' . $filters_str . ')';
            $str = $word . $restrictword_str;
        }

        $str = $word . $restrictword_str . $filters_str;
        return $str;
    }
    
    
    

    
  
    
    function check_filtered_url($url) {
        
        global $filters_validation;
        
        for ($i = 0; $i < count($filters_validation); $i++) {
            if (stripos($url, $filters_validation[$i]) !== false) {
                return true;
            }
        }
        return false;
    }
    
    
    function check_filter_exclude($url) {
        
        global $filters_exclude;
        
        for ($i = 0; $i < count($filters_exclude); $i++) {
            if (stripos($url, $filters_exclude[$i]) !== false) {
                return true;
            }
        }
        return false;
    }
    
    
    
    
    
    
    /////////////////
    // user agent parser
    /////////////////
    
    function parseOS($user_agent) {
        
        $os_platform    =   "Unknown OS Platform";
        
        $os_array       =   array(
                                  '/windows nt 10/i'     =>  'Windows 10',
                                  '/windows nt 6.3/i'     =>  'Windows 8.1',
                                  '/windows nt 6.2/i'     =>  'Windows 8',
                                  '/windows nt 6.1/i'     =>  'Windows 7',
                                  '/windows nt 6.0/i'     =>  'Windows Vista',
                                  '/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
                                  '/windows nt 5.1/i'     =>  'Windows XP',
                                  '/windows xp/i'         =>  'Windows XP',
                                  '/windows nt 5.0/i'     =>  'Windows 2000',
                                  '/windows me/i'         =>  'Windows ME',
                                  '/win98/i'              =>  'Windows 98',
                                  '/win95/i'              =>  'Windows 95',
                                  '/win16/i'              =>  'Windows 3.11',
                                  '/macintosh|mac os x/i' =>  'Mac OS X',
                                  '/mac_powerpc/i'        =>  'Mac OS 9',
                                  '/iphone/i'             =>  'iPhone',
                                  '/ipod/i'               =>  'iPod',
                                  '/ipad/i'               =>  'iPad',
                                  '/android/i'            =>  'Android',
                                  '/blackberry/i'         =>  'BlackBerry',
                                  '/webos/i'              =>  'Mobile',
                                  '/linux/i'              =>  'Linux',
                                  '/ubuntu/i'             =>  'Ubuntu'
                                  );
        
        foreach ($os_array as $regex => $value) {
            
            if (preg_match($regex, $user_agent)) {
                $os_platform    =   $value;
            }
            
        }
        
        return $os_platform;
        
    }
    
    function parseBrowser($user_agent) {
  
        $browser        =   "Unknown Browser";
        
        $browser_array  =   array(
                                  '/msie/i'       =>  'Internet Explorer',
                                  '/firefox/i'    =>  'Firefox',
                                  '/safari/i'     =>  'Safari',
                                  '/chrome/i'     =>  'Chrome',
                                  '/opera/i'      =>  'Opera',
                                  '/netscape/i'   =>  'Netscape',
                                  '/maxthon/i'    =>  'Maxthon',
                                  '/konqueror/i'  =>  'Konqueror',
                                  '/mobile/i'     =>  'Handheld Browser'
                                  );
        
        foreach ($browser_array as $regex => $value) { 
            
            if (preg_match($regex, $user_agent)) {
                $browser    =   $value;
            }
            
        }
        
        return $browser;
        
    }
    
    













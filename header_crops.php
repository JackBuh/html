<?php
    
    // invalid_urls filter
    $invalid_urls = array(
                          "baidu.com/share/home",
                          "baidu.com/wap/share/home",
                          );
    
    
    // replaced urls
    $replaced_urls = array(
                           array("http://emuch.net/", "http://muchong.com/"),
                           array("http://www.xiaoshuotxt.com/", "http://www.xiaoshuotxt.net/"),
                           array("http://xiaoshuotxt.com/", "http://www.xiaoshuotxt.net/"),
                           );
    
    
    // cropped title matches
    $cropped_title_str = array(
                               "移动版适用 -",
                               "移动设备适用 -",
                               "Mobile-friendly -",
                               
                               "_微盘下载 - 微博",
                               "_微盘下载",
                               "- 微盘",
                               "- 微盘 - 微博",
                               "_微盘.* \.\.\.",
                               "- 新浪微博",
                               "- vdisk\.weibo\.com",
                               
                               "_免费高速下载\|百度云网盘-分享无限制",
                               "\|百度云 网盘-分享无限制",
                               "_免费高速下载",
                               "\|百度云网盘",
                               "- 百度云网盘",
                               "- 百度云",
                               "- 百度网盘",
                               "_免费高速下载 - 百度云",
                               "_免费.* \.\.\.",
                               "\|百度.* \.\.\.",
                               "\|百度云.* …",
                               "-分享.* \.\.\.",
                               "- 云上的日子你我共享",
                               
                               "–华为网盘\|资源共享-文件备份-免费网络硬盘",
                               "- 华为网盘",
                               "–华为.* \.\.\.",
                               
                               "- 小木虫- 学术科研第一站",
                               "- 小木虫",
                               "- 小木虫.* \.\.\.",
                               
                               "- TXT小说天堂",
                               "-TXT小说天堂",
                               "\|书籍在线阅读",
                               "\|全文在线阅读",
                               "- TXT小说",
                               "\|手机电子书_TXT小说天堂"
                               
                               );
    
    
    
    // cropped des matches
    $cropped_des_str = array(
                             "移动版适用 -",
                             "移动设备适用 -",
                             "Mobile-friendly -",
                             "Site mobile -",
                             
                             "相关文档推荐\.?",
                             "通过新浪微盘下载",
                             "微盘是一款.*\.\.\.",
                             "微盘是一款.*的必备工具！"
                             
                             );
    
    
    
    function cropped_array_sort_length($a, $b) {
        if ( strlen($a) == strlen($b) ) {
            return 0;
        }
        return ( strlen($a) > strlen($b) ) ? -1 : 1;
    }
    
    
    function utf8_html_modify($str) {
        $output = htmlentities($str, ENT_NOQUOTES, "UTF-8");
        /*
        if ($output == "") {
            $output = htmlentities(utf8_encode($str), 0, "UTF-8");
        }
        */
        return $output;
    }
    
    function fetched_crops($str) {
        
        global $invalid_urls, $replaced_urls, $cropped_title_str, $cropped_des_str;
        
        if (count($str) === 0) {
            return $str;
        }
        
        $fetched_raw = $str;
        $fetched = array();
        
        
        usort($cropped_title_str, 'cropped_array_sort_length');
        $cropped_title_matches = array();
        for ($i = 0; $i < count($cropped_title_str); $i++) {
            $cropped_title_matches[] = '/'.$cropped_title_str[$i].'/';
        }
        
        
        usort($cropped_des_str, 'cropped_array_sort_length');
        $cropped_des_matches = array();
        for ($i = 0; $i < count($cropped_des_str); $i++) {
            $cropped_des_matches[] = '/'.$cropped_des_str[$i].'/';
        }
        
        
        
        for ($i = 0; $i < count($fetched_raw); $i++) {
            
            $item_raw = $fetched_raw[$i];
            
            $item_raw[1] = html_entity_decode($item_raw[1]);
            $item_raw[2] = html_entity_decode($item_raw[2]);
            
            
            // invalid url check
            $valid_url = true;
            for ($m = 0; $m < count($invalid_urls); $m++) {
                if (strpos($item_raw[0], $invalid_urls[$m]) !== false) {
                    $valid_url = false;
                    break;
                }
            }
            if (!$valid_url) {
                continue;
            }
            
            
            // url replacement, added time 2016-04-29
            for ($m = 0; $m < count($replaced_urls); $m++) {
                if (strpos($item_raw[0], $replaced_urls[$m][0]) !== false) {
                    $item_raw[0] = str_replace($replaced_urls[$m][0], $replaced_urls[$m][1], $item_raw[0]);
                    break;
                }
            }
            
            
            
            // crops matches
            for ($m = 0; $m < count($cropped_title_matches); $m++) {
                $item_raw[1] = preg_replace($cropped_title_matches[$m], '', $item_raw[1]);
            }
            
            for ($m = 0; $m < count($cropped_des_matches); $m++) {
                $item_raw[2] = preg_replace($cropped_des_matches[$m], '', $item_raw[2]);
            }
            
            
            $item_raw[1] = utf8_html_modify($item_raw[1]);
            $item_raw[2] = utf8_html_modify($item_raw[2]);
            
            
            $fetched[] = $item_raw;
            
        }
        
        
        return $fetched;
        
    }
    
    
    /*
     $item1 = array("http://baidu.com/share/hom2e2", 'title _免费balbalbal ...  _免费balbalbal ... _免费balbalbal ... _免费balbalbal ... _免费balbalbal ...  |百度云 …|百度云 …', 'Mobile-friendly - des');
     
     
     $item2 = array("link", '移动版适用 - title', 'des 微盘是一款babadfbadf的必备工具！');
     
     
     $str = array ($item1, $item2 );
     
     
     $fetched = fetched_crops($str);
     
     var_dump($fetched);
     */
    
    
    
    
    function word_optimize($str, $limit=14) {
        
        $char_m = array("\.", "\,", "\(", "\)", "\'", "\"", "\<", "\>", "\。", "\，", "\（", "\）", "\‘", "\”", "\《", "\》", "\－", "\-");
        
        for ($i = 0; $i < count($char_m); $i++) {
            $char_m[$i] = $char_m[$i];
        }
        
        $char_exp = '/' . implode("|",$char_m) . '/';
        
        $str = preg_replace($char_exp, ' ', $str);
        $str = preg_replace('/\s+/', ' ', $str);
        $str = trim($str);
        
        
        // limit to first N words
        $words = explode(' ',$str);
        $words_croped = array();
        $words_weight = 0;
        for ($i = 0; $i < count($words); $i++) {
            $item = $words[$i];
            $item_weight = 1;
            
            if (preg_match('/[\x{3400}-\x{9FBF}]+/u',$item)) {
                $item_weight = strlen($item) / 3;
            }
            if ($words_weight + $item_weight > $limit) {
                $item = implode('', array_slice(preg_split('/(?<!^)(?!$)/u', $item ),0,$limit-$words_weight));
            }
            
            $words_weight += $item_weight;
            $words_croped[] = $item;
            
            if ($words_weight >= $limit) { break; }
        }
        
        $str = implode(' ', $words_croped);
        return $str;
    }
    
    /*
     $str = '      ar     ray("\.", "\,威尔        \n      , "\(", "\)     "嘻嘻 , "\'", "\"", O(∩_∩)O哈！"\<",(～ o ～)~zZ "\>"(*^__^*) 嘻嘻……, "\。", "\，", "\（", "\）", "\‘", "\”", "\《", "\》", "\－");';
     $result = word_optimize($str);
     
     echo $result;
     */
    
    
    
    
    function exact_match_count($str, $word, &$matches, &$weibo) {
        
        $matches = 0;
        $weibo = 0;
        
        if (count($str) === 0 || count($word) === 0) {
            return false;
        }
        
        // weibo url exculder, added time 2016-04-30
        $weibo_url = 'http://vdisk.weibo.com/';
        
        
        $fetched_raw = $str;
        
        $word = preg_replace('/\s+/', '', $word);
        $word_arr = preg_split('/(?<!^)(?!$)/u', $word);
        
        for ($i = 0; $i < count($fetched_raw); $i++) {
            
            $item_raw = $fetched_raw[$i];
            
            // is weibo, not count
            if (strpos($item_raw[0], $weibo_url) !== false) {
                $weibo++;
                continue;
            }
            
            
            $item_raw[1] = html_entity_decode($item_raw[1]);
            // loop word array to check
            $exact_match = true;
            for ($m = 0; $m < count($word_arr); $m++) {
                if (stripos($item_raw[1], $word_arr[$m]) === false) {
                    $exact_match = false;
                    break;
                }
            }
            if ($exact_match) {
                $matches++;
            }
            
        }
        return true;
    }

    
    
    
    
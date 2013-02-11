<?php

// bolt
namespace bolt\browser;
use \b as b;


// helpers
class helpers {

    ////////////////////////////////////////////////
    /// csrf
    ////////////////////////////////////////////////
    public static function csrf($do, $name, $cookieCheck=true) {

        // what to do
        $cname = b::md5($name);

        // what to do
        switch($do) {

            // set
            case 'set':
                $cid = md5(uniqid("{$cname}-"));
                $token = uniqid(b::randomString()."-");
                $pl = array($token, $cid, IP);
                if ($cookieCheck) {
                    b::cookie()->set($cname, $pl, "+5 minutes", null, false, true);
                    b::memcache()->set($cid, $pl);
                    return $token;
                }
                else {
                    b::memcache()->set($cid, $pl, (60*5));
                    return "{$token}|{$cid}|".b::md5($cid);
                }

            // verify
            case 'verify':
//                if (bDevMode) { return true;}
                $tok = p("_csrf");

                // cookie check
                if ($cookieCheck) {
                    $cookie = b::cookie()->get($cname);
                    if (!$cookie OR !is_array($cookie)) { return false; }
                    list($token, $cid, $ip) = $cookie;
                    if (!$token OR !$cid OR !$ip) { return false; }
                }
                else {
                    list($token, $cid, $sig) = explode('|', $tok);
                    if ($sig != b::md5($cid)) { return false;}
                    $tok = $token;
                    $ip = IP;
                }
                $ctok = b::memcache()->get($cid); b::memcache()->delete($cid);
                if ($token != $tok OR $token != $ctok[0] OR $ctok[0] != $tok) { return false; }
                if ($ip != IP OR IP != $ctok[2]) { return false; }
                if ($cname) { b::cookie($cname)->delete(); }
                return true;
        }

        // nope
        return false;

    }



    ////////////////////////////////////////////////
    /// @brief add url params to a url
    ///
    /// @param $url base url
    /// @param $params array of params to add
    ///
    /// @return string url with additional params
    ////////////////////////////////////////////////
    static function addUrlParams($url, $params=array()) {

        // no params
        if(count($params)==0) {
            return $url;
        }

        // parse the url
        $u = parse_url($url);

        // loop and add to params
        if ( isset($u['query']) ) {
            foreach ( explode('&',$u['query']) as $i ) {
                if ( $i ) {
                    list($k,$v) = explode('=',$i);
                    if ( !array_key_exists($k,$params) ) {
                        $params[$k] = $v;
                    }
                }
            }
        }

        // reconstruct
        $url = $u['scheme']."://".$u['host'].(isset($u['port'])?":{$u['port']}":"").(isset($u['path']) ? $u['path'] : "");

        $url .= (strpos($url,'?')==false?'?':'&').http_build_query($params);

        if ( isset($u['fragment']) ) {
            $url .= $u['fragment'];
        }

        return $url;

    }


    public function location() {
        $a = func_get_args();
        $url = $a[0];
        if ( $url{0} == '$' ) {
            $a[0] = substr($url,1);
            $url = call_user_func_array("b::url",$a);
        }
        else if ( isset($a[1]) ) {
            if ( stripos($url,'http') === false )  {
                $url = URI.trim($url,'/');
            }
            $url = self::addUrlParams($url, $a[1]);
        }

        exit(header("Location:$url", true, 301));
    }

    public static function slug($str) {

        // remove any ' in the str
        $str = str_replace("'",'', html_entity_decode($str, ENT_QUOTES, 'utf-8') );

        // search
        $search = array(
            "/([^a-zA-Z0-9]+)/",
            "/([-]{2,})/"
        );

        // now the bug stuff
        return strtolower(trim(preg_replace($search, '-', $str),'-'));

    }



    public static function show_404($page=b404) {

        ob_clean();
        header("HTTP/1.1 404 Not Found",TRUE,404);

        if (!file_exists(b404)) {
            $page = b404;
        }


        exit(include($page));
    }

}

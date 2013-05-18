<?php

// bolt
namespace bolt\browser;
use \b as b;

b::bolt()->setFallbacks('\bolt\browser\helpers');

// helpers
class helpers {

    static function buildUrl($parts, $start=false, $opts=HTTP_URL_REPLACE) {
        return \http_build_url($start, $parts, $opts);
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

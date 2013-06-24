<?php

namespace bolt;
use \b;

// depend on bolt core and plugin browser
b::depend('bolt-core-*')->plug('browser', '\bolt\browser');

class browser extends \bolt\plugin\singleton {

    public function error($message, $code=500) {
        $c = new \bolt\browser\controller\request();
        $c->setStatus($code);
        $c->setContent('<!doctype html>
                <html>
                    <body>
                        <h1>Error: '.$code.'</h1>
                        <p>'.$message.'</p>
                    </body>
                </html>
            ');
        return $c;
    }


}
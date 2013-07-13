<?php

namespace bolt\browser\response;
use \b as b;

b::depend("bolt-browser-response")
    ->response
    ->plug('xhr', '\bolt\browser\response\xhr');

// json
class xhr extends handler {

    // accept or header
    public static $contentType = array(
        100 => 'text/javascript;text/xhr',
        101 => 'text/javascript;text/xhr;secure',
    );


    //
    public function handle() {

        // body
        $body = $this->getContent();

        // holders
        $js = array();

        if (stripos($body, '<script') !== false) {

            // dom up the body
            $dom = new \DOMDocument();
            $dom->strictErrorChecking = false;
            @$dom->loadHTML($body, LIBXML_NOERROR & LIBXML_NOWARNING);

            foreach ($dom->getElementsByTagName('script') as $node) {
                $js[] = $node->nodeValue;
                $node->parentNode->removeChild($node);
            }

            $body = $dom->saveHTML($dom->getElementsByTagName('body')->item(0)->firstChild);

        }

        // json
        $j = new json();
        $j->setStatus($this->getStatus());
        $j->setContentType($this->getContentType());
        $j->setContent(array(
            'content' => $body,
            'data' => $this->getData(),
            'javascript' => $js,
        ));

        $this->setContentType('application/json');

        return $j->handle();

    }

}
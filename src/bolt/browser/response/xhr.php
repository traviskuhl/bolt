<?php

namespace bolt\browser\response;
use \b as b;

// json
class xhr extends base {

    const TYPE = 'xhr';

    // accept or header
    public $contentType = 'application/json';

    public function render() {

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
                if (stripos($node->getAttribute('type'), 'template') !== false) {continue;}
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
            'html' => $body,
            'data' => $this->getData(),
            'javascript' => $js,
        ));

        return $j->render();

    }

}
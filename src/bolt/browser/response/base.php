<?php

namespace bolt\browser\response;
use \b;

class base implements \bolt\browser\iResponse {

        const TYPE = 'plain';

        protected $_headers;

        protected $_content = false;
        protected $_data = array();

        protected $status = 200;
        protected $contentType = 'text/plain';
        protected $charset = false;

        private $_parent = false;

        /**
         * construct a response
         *
         * @return self
         */
        public function __construct() {
            $this->_headers = b::bucket();
        }

        public function __invoke() {
            return $this->getResponse();
        }

        public function setParent($parent) {
            $this->_parent = $parent;
            return $this;
        }

        public function getResponse() {

            // check if content can be rendered
            if (is_callable($this->_content)) {
                $this->_content = call_user_func($this->_content);
            }

            // give it
            return $this->render();

        }

        public function render() {
            return $this->_content;
        }

        /**
         * MAGIC return params, where:
         *     status -> _status
         *     accept -> _accept
         *     contentType -> _contentType
         *     headers -> _headers bucket
         *
         * @param $name
         * @return mixed
         */
        public function __get($name) {
            switch($name) {
                case 'headers':
                    return $this->_headers;
            };
            return false;
        }

        public function addHeader($name, $value) {
            $this->_headers->set($name, $value);
            if ($this->_parent) {
                $this->_parent->addHeader($name, $value);
            }
        }

        public function addHeaders($headers) {
            foreach ($headers as $name => $value) {
                $this->addHeader($name, $value);
            }
            return $this;
        }

        public function setContent($content) {
            $this->_content = $content;
            return $this;
        }

        public function getContent() {
            return $this->_content;
        }

        public function setContentType($type) {
            $this->contentType = $type;
            if ($this->_parent) {
                $this->_parent->setContentType($type);
            }
            return $this;
        }

        public function getContentType() {
            return $this->contentType;
        }

        /**
         * get response headers
         *
         * @return headers bucket
         */
        public function getHeaders() {
            return $this->_headers;
        }

        public function setStatus($status) {
            $this->status = $status;
            if ($this->_parent) {
                $this->_parent->setStatus($status);
            }
            return $this;
        }

        /**
         * get the response status
         *
         * @return int response status
         */
        public function getStatus() {
            return $this->status;
        }

        /**
         * set response data
         *
         * @param $data
         * @return self
         */
        public function setData($data) {
            $this->_data = $data;
            return $this;
        }

        /**
         * get response data
         *
         * @return response data
         */
        public function getData() {
            return $this->_data;
        }

}
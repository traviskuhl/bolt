<?php

class helpersTest extends bolt_test {

    protected $salt = 'abc123';

    public function setUp() {
        b::init(array(
                'config' => array(
                    'salt' => $this->salt
                )
            ));
    }

    public function testPayloadCreate() {
        $payload = array('a' => 'b');
        $json = base64_encode(json_encode($payload));
        $str = ":".b::md5($json).$json;
        $this->assertEquals($str, b::payload($payload));
    }
    public function testPayloadReas() {
        $payload = array('a' => 'b');
        $json = base64_encode(json_encode($payload));
        $str = ":".b::md5($json).$json;
        $this->assertEquals($payload, b::payload($str));
    }
    public function testCryptWithSalt() {
        $str = 'ab';
        $salt = '123';
        $this->assertEquals(crypt($str, '$5$rounds=5000$'.$salt.'$'), b::crypt($str, $salt));
    }
    public function testCryptWithSaltLong() {
        $str = 'ab';
        $salt = '$6$rounds=6000$abc$';
        $this->assertEquals(crypt($str, $salt), b::crypt($str, $salt));
    }
    public function testMCrypt() {

        $str = 'abc';
        $salt = 'act123';

        // encrypt
        $td = mcrypt_module_open('tripledes', '', 'ecb', '');

        // figure our how long our key should be
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_DEV_URANDOM);
        $ks = mcrypt_enc_get_key_size($td);

        // make our key
        $key = substr(md5($salt), 0, $ks);
        mcrypt_generic_init($td, $key, $iv);


        $data = base64_encode(mcrypt_generic($td, $str));

        // end it
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);

        $e = b::encrypt($str, $salt);

        $this->assertEquals($data, $e);
        $this->assertEquals($str, b::decrypt($e, $salt));

    }
    public function testRandStrLen() {
        $this->assertEquals(5, strlen(b::randString(5)));
    }
    public function testMd5() {
        $salt = $this->salt;
        $str = 'poop';
        $md5 = md5($salt.$str.strrev($salt));
        $this->assertEquals($md5, b::md5($str, $salt));
    }
    public function testUtcTime() {
        $dt = new \DateTime('now',new \DateTimeZone('UTC'));
        $this->assertEquals(b::utctime(), $dt->getTimestamp());
    }
    public function testTZConvert() {
        $from = 'UTC';
        $to = 'America/Los_Angeles';
        $ts = time();

        $dt = new \DateTime(null, new \DateTimeZone($from));
        $dt->setTimestamp($ts);
        $dt->setTimezone(new \DateTimeZone($to));

        $this->assertEquals($dt->format('U'), b::convertTimestamp($ts, $to, $from));
    }


}

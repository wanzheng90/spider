<?php
/**
 * Created by PhpStorm.
 * User: llf
 * Date: 2018/12/25
 * Time: 13:34
 */
class Spider
{
    protected $_ch;

    protected $header = 0;

    protected $option = [];

    public function __construct()
    {
        if (!$this->_ch) {
            $this->_ch = curl_init();
        }
    }


    /**
     * @param array $params
     * @return self
     */
    public function setHeader($params = [])
    {
        if (!empty($params)) {
            $header = [];
            foreach ($params as $key => $val) {
                $header[] = $key . ":" . $val;
            }
            curl_setopt($this->_ch, CURLOPT_HTTPHEADER, $header);
        }
        return $this;
    }

    public function setCookie()
    {
        return $this;
    }

    /**
     * 设置不验证ssl
     *
     * @return $this
     */
    public function setUnCheckSsl()
    {
        curl_setopt($this->_ch, CURLOPT_SSL_VERIFYPEER, false);
        return $this;
    }

    /**
     * 设置返回文件流形式
     *
     * @return $this
     */
    public function setReturnStream()
    {
        curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, true); // 返回文件流形式而不直接输出
        return $this;
    }

    public function get($url = '', $data = [])
    {
        return call_user_func([$this, 'curl'], $url, $data);
    }

    public function post($url = '', $data = [])
    {
        curl_setopt($this->_ch, CURLOPT_POST, 1);
        return call_user_func([$this, 'curl'], $url, $data);
    }

    protected function curl($url = '', $data = [])
    {
        $result = NULL;
        if ($url) {
            curl_setopt($this->_ch, CURLOPT_URL, $url);
//            curl_setopt($this->_ch, CURLOPT_SSL_VERIFYPEER, false);
//            curl_setopt($this->_ch, CURLOPT_SSL_VERIFYHOST, false);
//            curl_setopt($this->_ch, CURLOPT_SSLVERSION, 1);
            $result = curl_exec($this->_ch);
            if (curl_errno($this->_ch)) {
                p(curl_error($this->_ch));
            }
        } else {

        }
        return mb_convert_encoding($result, 'utf-8', 'gbk');
    }

    public function __destruct()
    {
        curl_close($this->_ch);
    }
}



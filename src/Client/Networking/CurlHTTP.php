<?php
namespace ParagonIE\AsgardClient\Networking;

class CurlHTTP implements HTTPInterface
{
    public $lastHeader;
    public $lastBody;
    public $lastResponseCode;
    
    /**
     * Clear all of the cookies
     * 
     * @return int|boolean
     */
    public function flushCookies()
    {
        return \file_put_contents(CONFIGROOT.'/cookies.txt', '');
    }
    
    /**
     * HTTP GET request
     * 
     * @param string $url - Where are we sending this request?
     * @param mixed $params - HTTP GET parameters
     * @param array $options - CURL options
     * 
     * @return string - HTTP Response body
     */
    public function get($url, $params = [], $options = [])
    {
        $ch = \curl_init($url . '?' . \http_build_query($params));
        if (!empty($options)) {
            \curl_setopt_array($ch, $options);
        }
        \curl_setopt_array(
            $ch, 
            [
                CURLOPT_TIMEOUT => 30,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_COOKIEFILE => CONFIGROOT.'/cookies.txt',
                CURLOPT_COOKIEJAR => CONFIGROOT.'/cookies.txt',
                CURLOPT_HEADER => true
            ]
        );
        $response = \curl_exec($ch);

        // We only want the last response.
        $header_size = \curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = \substr($response, 0, $header_size);
        $body = \substr($response, $header_size);

        $this->lastResponseCode = \curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->lastHeader = $header;
        $this->lastBody = $body;
        
        \curl_close($ch);
        return $body;
    }
    
    /**
     * HTTP HEAD request
     * 
     * @param string $url - Where are we sending this request?
     * @param mixed $params - HTTP GET parameters
     * @param array $options - CURL options
     * 
     * @return string - HTTP Header
     */
    public function head($url, $params = [], $options = [])
    {
        $ch = \curl_init($url . '?' . \http_build_query($params));
        if (!empty($options)) {
            \curl_setopt_array($ch, $options);
        }
        \curl_setopt_array(
            $ch, 
            [
                    CURLOPT_RETURNTRANSFER => 1,
                    CURLOPT_COOKIEFILE => CONFIGROOT.'/cookies.txt',
                    CURLOPT_COOKIEJAR => CONFIGROOT.'/cookies.txt',
                    CURLOPT_HEADER => true
            ]
        );

        $result = \curl_exec($ch);
        $this->lastResponseCode = \curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->lastHeader = $result;
        $this->lastBody = null;
        
        \curl_close($ch);
        return $result;
    }
    
    /**
     * HTTP POST request
     * 
     * @param string $url - Where are we sending this request?
     * @param mixed $params - HTTP POST parameters
     * @param array $options - CURL options
     * 
     * @return string - HTTP Response body
     */
    public function post($url, $params = [], $options = [])
    {
        
        $ch = \curl_init($url);
        if (!empty($options)) {
            \curl_setopt_array($ch, $options);
        }
        \curl_setopt_array(
            $ch, 
            [
                    CURLOPT_HEADER => true,
                    CURLOPT_POSTFIELDS => \http_build_query($params),
                    CURLOPT_COOKIEFILE => CONFIGROOT.'/cookies.txt',
                    CURLOPT_COOKIEJAR => CONFIGROOT.'/cookies.txt',
                    CURLOPT_RETURNTRANSFER => 1
            ]
        );
        $response = \curl_exec($ch);

        // We only want the last response.
        $header_size = \curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = \substr($response, 0, $header_size);
        $body = \substr($response, $header_size);

        $this->lastResponseCode = \curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->lastHeader = $header;
        $this->lastBody = $body;
        
        \curl_close($ch);
        return $body;
    }
    
    /**
     * Get the last HTTP response body
     * 
     * @return string
     */
    public function getLastBody()
    {
        return $this->lastBody;
    }
    
    /**
     * Get the last HTTP response header
     * 
     * @return string
     */
    public function getLastHeader()
    {
        return $this->lastHeader;
    }
    
    /**
     * Get the last HTTP response code
     * 
     * @return int
     */
    public function getLastResponseCode()
    {
        return $this->lastResponseCode;
    }
}

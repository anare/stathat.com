<?php

/**
 * StatHat Request object
 *
 * @author Anar Alishov <anar.alishov@gmail.com>
 */
class StatHatRequest
{
    /**
     * API URL
     */
    const STATHAT_URL = 'http://api.stathat.com/';

    /**
     * Synchronized method call
     */
    const STATHAT_SYNC = 'sync';

    /**
     * Asynchronously method call
     */
    const STATHAT_ASYNC = 'async';

    /**
     * Method type
     *
     * @var string
     */
    protected $method;

    /**
     * @param string $url
     * @param string $data
     * @param null $optional_headers
     * @return string
     * @throws Exception
     */
    protected function sync($url, $data, $optional_headers = null)
    {
        $url = self::STATHAT_URL . $url;
        $params = array(
            'http' => array(
                'method' => 'POST',
                'content' => $data
            )
        );

        if ($optional_headers !== null) {
            $params['http']['header'] = $optional_headers;
        }

        $ctx = stream_context_create($params);
        $fp = @fopen($url, 'rb', false, $ctx);

        if (!$fp) {
            throw new Exception("Problem with $url, $php_errormsg");
        }

        $response = @stream_get_contents($fp);

        if ($response === false) {
            throw new Exception("Problem reading data from $url, $php_errormsg");
        }

        return $response;
    }

    /**
     * @param string $url
     * @param array $params
     * @param null $optional_headers
     */
    protected function async($url, $params, $optional_headers = null)
    {
        $url = self::STATHAT_URL . $url;
        $post_params = array();
        foreach ($params as $key => &$val) {
            if (is_array($val)) {
                $val = implode(',', $val);
            }
            $post_params[] = $key . '=' . urlencode($val);
        }
        $post_string = implode('&', $post_params);

        $parts = parse_url($url);

        $fp = fsockopen(
            $parts['host'],
            isset($parts['port']) ? $parts['port'] : 80,
            $errno,
            $errstr,
            30
        );

        $out = "POST " . $parts['path'] . " HTTP/1.1\r\n";
        $out .= "Host: " . $parts['host'] . "\r\n";
        $out .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $out .= "Content-Length: " . strlen($post_string) . "\r\n";
        if ($optional_headers) {
            $out .= $optional_headers;
        }
        $out .= "Connection: Close\r\n\r\n";
        if (isset($post_string)) {
            $out .= $post_string;
        }

        fwrite($fp, $out);
        fclose($fp);
    }

    /**
     * @param $type
     * @return $this
     */
    public function setType($type)
    {
        $this->method = $type;

        return $this;
    }

    /**
     * @param string $url
     * @param mixed $data
     * @param null $optional_headers
     */
    public function post($url, $data, $optional_headers = null)
    {
        $method = $this->method;

        return $this->$method($url, $data, $optional_headers);
    }
}

/**
 * Interface ApiStatHat
 * for ASync/Sync Object
 */
interface ApiStatHatInterface
{

    /**
     * @param StatHatRequest $request
     * @param array $keys Array of ('key' => '', 'userKey' => '', 'email' => '')
     */
    public function __construct(StatHatRequest $request, $keys = array('key' => '', 'userKey' => '', 'email' => ''));

    /**
     * @return $this
     */
    public function initialize();

    /**
     * @param string $count
     * @return string
     */
    public function count($count);

    /**
     * @param string $value
     * @return string
     */
    public function value($value);

    /**
     * @param string $statName
     * @param string $count
     * @return string
     */
    public function ezCount($statName, $count);

    /**
     * @param string $statName
     * @param string $value
     * @return string
     */
    public function ezValue($statName, $value);
}

/**
 * Abstract ApiStatHat Class
 *
 * @author Anar Alishov <anar.alishov@gmail.com>
 */
abstract class ApiStatHat implements ApiStatHatInterface
{

    /**
     * StatHat Request object
     *
     * @var StatHatRequest
     */
    protected $request;

    /**
     * StatHat keys: key, user key
     *
     * @var array
     */
    protected $keys;

    /**
     * Initialize
     */
    public function __construct(StatHatRequest $request, $keys = array('key' => '', 'userKey' => '', 'email' => ''))
    {
        $this->request = $request;
        $this->keys = $keys;

        $this->initialize();
    }

}

/**
 * ASync StatHat Class
 *
 * @author Anar Alishov <anar.alishov@gmail.com>
 */
class ASyncStatHat extends ApiStatHat
{

    /**
     * {@inheritdoc}
     */
    public function initialize()
    {
        $this->request->setType(StatHatRequest::STATHAT_ASYNC);
    }

    /**
     * @param string $count
     * @return string
     */
    public function count($count)
    {
        return $this->request->post(
            "c",
            array(
                'key' => $this->keys['key'],
                'ukey' => $this->keys['userKey'],
                'count' => $count
            )
        );
    }

    /**
     * @param string $value
     * @return string
     */
    function value($value)
    {
        return $this->request->post(
            "v",
            array(
                'key' => $this->keys['key'],
                'ukey' => $this->keys['userKey'],
                'value' => $value
            )
        );
    }

    /**
     * @param string $statName
     * @param string $count
     * @return string
     */
    function ezCount($statName, $count)
    {
        return $this->request->post(
            "ez",
            array(
                'email' => $this->keys['email'],
                'stat' => $statName,
                'count' => $count
            )
        );
    }

    /**
     * @param string $statName
     * @param string $value
     * @return string
     */
    function ezValue($statName, $value)
    {
        return $this->request->post(
            "ez",
            array(
                'email' => $this->keys['email'],
                'stat' => $statName,
                'value' => $value
            )
        );
    }
}

/**
 * Sync StatHat Class
 *
 * @author Anar Alishov <anar.alishov@gmail.com>
 */
class SyncStatHat extends ApiStatHat
{
    /**
     * {@inheritdoc}
     */
    public function initialize()
    {
        $this->request->setType(StatHatRequest::STATHAT_SYNC);
    }

    /**
     * @param string $count
     * @return string
     */
    function count($count)
    {
        return $this->request->post(
            "c",
            'key=' . $this->keys['key'] . '&ukey=' . $this->keys['userKey'] . '&count=' . $count
        );
    }

    /**
     * @param string $value
     * @return string
     */
    function value($value)
    {
        return $this->request->post(
            "v",
            'key=' . $this->keys['key'] . '&ukey=' . $this->keys['userKey'] . '&value=' . $value
        );
    }

    /**
     * @param string $statName
     * @param string $count
     * @return string
     */
    function ezCount($statName, $count)
    {
        return $this->request->post(
            "ez",
            'key=' . $this->keys['email'] . '&stat==' . $statName . '&count=' . $count
        );
    }

    /**
     * @param string $statName
     * @param string $value
     * @return string
     */
    function ezValue($statName, $value)
    {
        return $this->request->post(
            "ez",
            'key=' . $this->keys['email'] . '&stat==' . $statName . '&value=' . $value
        );
    }
}

/**
 * StatHat Class
 *
 * @author Anar Alishov <anar.alishov@gmail.com>
 */
class StatHat
{

    /**
     * Request object
     * @var ApiStatHatInterface
     */
    protected $api;

    /**
     * @param ApiStatHatInterface $api
     */
    public function __construct(ApiStatHatInterface $api)
    {
        $this->api = $api;
    }

    /**
     * @param string $count
     * @return string
     */
    function count($count)
    {
        return $this->api->count($count);
    }

    /**
     * @param string $value
     * @return string
     */
    function value($value)
    {
        return $this->api->value($value);
    }

    /**
     * @param string $statName
     * @param string $count
     * @return string
     */
    function ezCount($statName, $count)
    {
        return $this->api->ezCount($statName, $count);
    }

    /**
     * @param string $statName
     * @param string $value
     * @return string
     */
    function ezValue($statName, $value)
    {
        return $this->api->ezValue($statName, $value);
    }
}

/**
 * Example
 */
/*
$statHat = new StatHat(new ASyncStatHat(new StatHatRequest(), array(
    'key' => '***************************',
    'userKey' => '********************',
    'email' => '****************'
)));

for ($i = 0; $i < 20; $i++) {
    $statHat->count('test', $i);
    $statHat->ezCount('test', $i);
}
*/

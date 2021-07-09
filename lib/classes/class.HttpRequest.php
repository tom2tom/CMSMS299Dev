<?php
/**
 * Class for making HTTP requests to external servers
 * @package CMS
 * @license GPL
 */
namespace CMSMS;

//use CMSMS\AppConfig;
use CMSMS\Crypto;
use const CMS_ROOT_URL;
use const CMS_VERSION;
use const TMP_CACHE_LOCATION;

/**
 * HTTP class
 *
 * This is a wrapper HTTP class that uses either cURL or fsockopen to
 * harvest resources from web. This can be used with scripts that need
 * a way to communicate with various APIs who support REST.
 *
 * Modified by Robert Campbell (calguy1000@cmsmadesimple.org)
 * Renamed the class to cms_http_request
 * Fixed some bugs.
 *
 * @package     CMS
 * @license     GPL
 * @author      Md Emran Hasan <phpfour@gmail.com>
 *
 * @link        http://www.phpfour.com/lib/http
 * @since       2.99
 * @since       0.1 as global-namespace cms_http_request
 */
class HttpRequest
{
    /**
     * Contains the target URL
     *
     * @var string
     */
    private $target;

    /**
     * stream descriptor
     *
     */
    private $_remote;

    /**
     * Contains the target host
     *
     * @var string
     */
    private $host;

    /**
     * Contains the target port
     *
     * @var integer
     */
    private $port;

    /**
     * Contains the target path
     *
     * @var string
     */
    private $path;

    /**
     * Contains the target schema
     *
     * @var string
     */
    private $schema;

    /**
     * Contains the http method (GET or POST)
     *
     * @var string
     */
    private $method;

    /**
     * Contains raw post data
     *
     * @var str
     */
    private $rawPostData;

    /**
     * Contains the parameters for request
     *
     * @var array
     */
    private $params;

    /**
     * Contains the cookies for request
     *
     * @var array
     */
    private $cookies;

    /**
     * Contains the cookies retrieved from response
     *
     * @var array
     */
    private $_cookies;

    /**
     * Number of seconds to initial-connection timeout
     *
     * @var integer
     */
    private $connecttime;

    /**
     * Number of seconds to timeout
     *
     * @var integer
     */
    private $timeout;

    /**
     * Whether to try to use cURL (it must be available and suitable)
     *
     * @var boolean
     */
    private $useCurl;

    /**
     * Contains the referrer URL
     *
     * @var string
     */
    private $referrer;

    /**
     * Contains the User agent string
     *
     * @var string
     */
    private $userAgent;

    /**
     * Contains the cookie path (to be used with cURL)
     *
     * @var string
     */
    private $cookiePath;

    /**
     * Whether to use cookie at all
     *
     * @var boolean
     */
    private $useCookie;

    /**
     * Whether to store cookie for subsequent requests
     *
     * @var boolean
     */
    private $saveCookie;

    /**
     * Contains the Username (for authentication)
     *
     * @var string
     */
    private $username;

    /**
     * Contains the Password (for authentication)
     *
     * @var string
     */
    private $password;

    /**
     * Contains the fetched web source
     *
     * @var string
     */
    private $result;

    /**
     * Contains the last headers
     *
     * @var string
     */
    private $headers;

    /**
     * Contains the last call's http status code
     *
     * @var string
     */
    private $status;

    /**
     * Whether to follow http redirect or not
     *
     * @var boolean
     */
    private $redirect;

    /**
     * The maximum number of redirect to follow
     *
     * @var integer
     */
    private $maxRedirect;

    /**
     * The current number of redirects
     *
     * @var integer
     */
    private $curRedirect;

    /**
     * Contains any error occurred
     *
     * @var string
     */
    private $error;

    /**
     * Store the next token
     *
     * @var string
     */
    private $nextToken;

    /**
     * Whether to keep debug messages
     *
     * @var boolean
     */
    private $debug;

    /**
     * Stores optional http headers
     *
     * @var array
     */
    private $headerArray;

    /**
     * Stores the debug messages
     *
     * @var array
     * @todo will keep debug messages
     */
    private $debugMsg;

    /**
     * Stores proxy information (host:port)
     *
     * @var string
     */
    private $proxy;

    /**
     * Constructor for initializing the class with default values.
     */
    public function __construct()
    {
        $this->clear();
    }

    /**
     * Initialize preferences
     *
     * This function will take an associative array of config values and
     * will initialize the class variables using them.
     *
     * Example use:
     *
     * <pre>
     * $httpConfig['method']     = 'GET';
     * $httpConfig['target']     = 'http://www.somedomain.com/index.html';
     * $httpConfig['referrer']   = 'http://www.somedomain.com';
     * $httpConfig['user_agent'] = 'My Crawler';
     * $httpConfig['connecttime'] = 10;
     * $httpConfig['params']     = array('var1' => 'testvalue', 'var2' => 'somevalue');
     *
     * $http = new CMSMS\HttpRequest();
     * $http->initialize($httpConfig);
     * </pre>
     *
     * @param array $config AppConfig values as associative array
     */
    public function initialize($config = [])
    {
        $this->clear();
        foreach ($config as $key => $val) {
            if (isset($this->$key)) {
                $method = 'set' . ucfirst(str_replace('_', '', $key));

                if (method_exists($this, $method)) {
                    $this->$method($val);
                } else {
                    $this->$key = $val;
                }
            }
        }
    }

    /**
     * Clear Everything
     *
     * Clears all the properties of the class and sets the object to
     * the beginning state. Very handy if you are doing subsequent calls
     * with different data.
     */
    public function clear()
    {
//        $config = AppSingle::Config();

        // Set the request defaults
        $this->host         = '';
        $this->port         = 0;
        $this->path         = '';
        $this->target       = '';
        $this->method       = 'GET';
        $this->schema       = 'http';
        $this->params       = [];
        $this->headers      = [];
        $this->cookies      = [];
        $this->_cookies     = [];
        $this->headerArray  = [];
        $this->proxy        = null;

        // Set the config details
        $this->debug        = false;
        $this->error        = '';
        $this->status       = 0;
        $this->connecttime  = 20;
        $this->timeout      = max(25, ini_get('max_max_execution_time') - 5);
        $this->useCurl      = true;
        $this->referrer     = CMS_ROOT_URL.'::'.CMS_VERSION;
        $this->username     = '';
        $this->password     = '';
        $this->redirect     = false;
        $this->result       = '';

        // Set the cookie and agent defaults
        $this->nextToken    = '';
        $this->useCookie    = true;
        $this->saveCookie   = true;
        $this->maxRedirect  = 3;
        $this->cookiePath   = TMP_CACHE_LOCATION.'/c'.Crypto::hash_string(get_class().session_id()).'.dat'; // by default, use a cookie file that is unique only to this session.
        $this->userAgent    = ($_SERVER['HTTP_USER_AGENT'] ?? 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20100101 Firefox/70.0') . ' CMSMS:'.CMS_VERSION;
    }

    /**
     * Clear all cookies
     *
     * @author Robert Campbell (calguy1000@cmsmadesimple.org)
     */
    public function resetCookies()
    {
        if ($this->cookiePath) {
            @unlink($this->cookiePath);
        }
    }

    /**
     * Set target URL
     *
     * @param string $url URL of target resource
     */
    public function setTarget($url)
    {
        if ($url) {
            $this->target = $url;
        }
    }

    /**
     * Set http method
     *
     * @param string $method HTTP method to use (GET or POST)
     */
    public function setMethod($method)
    {
        $method = strtoupper($method);
        if ($method == 'GET' || $method == 'POST') {
            $this->method = $method;
        }
    }

    /**
     * Set referrer URL
     *
     * @param string $referrer URL of referrer page
     */
    public function setReferrer($referrer)
    {
        if ($referrer) {
            $this->referrer = $referrer;
        }
    }

    /**
     * Set User agent string
     *
     * @param string $agent Full user agent string
     */
    public function setUseragent($agent)
    {
        if ($agent) {
            $this->userAgent = $agent;
        }
    }

    /**
     * Set timeout of initial connection
     * @since 2.99
     *
     * @param int $seconds Timeout delay in seconds
     */
    public function setConnectTimeout($seconds)
    {
        if ($seconds > 0) {
            $this->connecttime = (int)$seconds;
        }
    }

    /**
     * Set timeout of execution
     *
     * @param int $seconds Timeout delay in seconds
     */
    public function setTimeout($seconds)
    {
        if ($seconds > 0) {
            $this->timeout = (int)$seconds;
        }
    }

    /**
     * Set cookie path (cURL only)
     *
     * @param string $path File location of cookiejar
     */
    public function setCookiepath($path)
    {
        if ($path) {
            $this->cookiePath = $path;
        }
    }

    /**
     * Set the post data string directly.
     *
     * @param string $data
     */
    public function setRawPostData($data)
    {
        $this->setMethod('POST');
        $this->rawPostData = $data;
    }

    /**
     * Set request parameters
     *
     * @param array $dataArray All the parameters for GET or POST
     */
    public function setParams($dataArray)
    {
        if (!is_array($dataArray)) {
            $this->setRawPostData($dataArray);
        } elseif (is_array($dataArray)) {
            $this->params = array_merge($this->params, $dataArray);
        }
    }

    /**
     * Set basic http authentication realm
     *
     * @param string $username Username for authentication
     * @param string $password Password for authentication
     */
    public function setAuth($username, $password)
    {
        if (!empty($username) && !empty($password)) {
            $this->username = $username;
            $this->password = $password;
        }
    }

    /**
     * Set maximum number of redirection to follow
     *
     * @param int $value Maximum number of redirects
     */
    public function setMaxredirect($value)
    {
        if (!empty($value)) {
            $this->maxRedirect = $value;
        }
    }

    /**
     * Add request parameters
     *
     * @param string $name Name of the parameter
     * @param string $value Value of the parameter
     */
    public function addParam($name, $value)
    {
        if (!empty($name) && $value !== '') {
            $this->params[$name] = $value;
        }
    }

    /**
     * Add a cookie to the request
     *
     * @param string $name Name of cookie
     * @param string $value Value of cookie
     */
    public function addCookie($name, $value)
    {
        if (!empty($name) && !empty($value)) {
            $this->cookies[$name] = $value;
        }
    }

    /**
     * Whether to use cURL or not
     *
     * @param bool $value Whether to use cURL or not
     */
    public function useCurl($value = true)
    {
        if (is_bool($value)) {
            $this->useCurl = $value;
        }
    }

    /**
     * Whether to use cookies or not
     *
     * @param bool $value Whether to use cookies or not
     */
    public function useCookie($value = true)
    {
        if (is_bool($value)) {
            $this->useCookie = $value;
        }
    }

    /**
     * Whether to save persistent cookies in subsequent calls
     *
     * @param bool $value Whether to save persistent cookies or not
     */
    public function saveCookie($value = true)
    {
        if (is_bool($value)) {
            $this->saveCookie = $value;
        }
    }

    /**
     * Whether to follow HTTP redirects
     *
     * @param bool $value Whether to follow HTTP redirects or not
     */
    public function followRedirects($value = true)
    {
        if (is_bool($value)) {
            $this->redirect = $value;
        }
    }

    /**
     * Get execution result body
     *
     * @return string output of execution
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Get execution result headers
     *
     * @return array last headers of execution
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Get execution status code
     *
     * @return int last http status code
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Get last execution error
     *
     * @return string last error message (if any)
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Request Header Exists?
     *
     * @param string $key The header key
     * @return bool
     */
    public function requestHeaderExists($key)
    {
        if (!is_array($this->headerArray)) {
            $this->headerArray = [];
        }
        if (strpos($key, ':') !== false) {
            $tmp = explode(':', $key);
            $key = trim($tmp[0]);
        }
        for ($i = 0, $n = count($this->headerArray); $i < $n; $i++) {
            $tmp = explode(':', $this->headerArray[$i], 1);
            $key2 = trim($tmp[0]);
            if ($key2 == $key) {
                return true;
            }
        }
        return false;
    }

    /**
     * Add a request header
     *
     * @param string $str The header string
     * @param bool $prepend push header on top of all other headers.
     */
    public function addRequestHeader($str, $prepend = false)
    {
        if (!is_array($this->headerArray)) {
            $this->headerArray = [];
        }

        $f = 0;
        if (strpos($str, ':') !== false) {
            $tmp = explode(':', $str, 1);
            $key = trim($tmp[0]);
            for ($i = 0, $n = count($this->headerArray); $i < $n; $i++) {
                $tmp = explode(':', $this->headerArray[$i], 1);
                $key2 = trim($tmp[0]);
                if ($key2 == $key) {
                    // found a duplicate.
                    $this->headerArray[$i] = $str;
                    $f = 1;
                    break;
                }
            }
        }
        if (!$f) {
            if ($prepend) {
                array_unshift($this->headerArray, $str);
            } else {
                $this->headerArray[] = $str;
            }
        }
    }

    /**
     * Test if the installed curl version (if any) is suitable
     *
     * @return bool
     */
    public static function is_curl_suitable()
    {
        // static properties here >> StaticProperties class ?
        static $_curlgood = null;

        if ($_curlgood === null) {
            $_curlgood = false;
            if (extension_loaded('curl')) {
                if (function_exists('curl_version')) {
                    $tmp = curl_version();
                    if (isset($tmp['version'])) {
                        if (version_compare($tmp['version'], '7.19.7') >= 0) {
                            $_curlgood = true;
                        }
                    }
                }
            }
        }

        return $_curlgood;
    }

    /**
     * Execute a HTTP request
     * Performs the http fetch using all the set properties. Uses CURL or
     * a stream-socket if cURL is not present. Follows redirects (if so asked).
     *
     * @param string $target URL of the target page (optional)
     * @param string $referrer URL of the referrer page (optional)
     * @param string $method The http method (GET or POST) (optional)
     * @param array $data Parameters array for GET or POST (optional)
     * @return string Response body of the target page
     */
    public function execute($target = '', $referrer = '', $method = '', $data = [])
    {
        // Populate properties
        if ($target) $this->target = $target;
        if ($referrer) $this->referrer = $referrer;
        if ($method) $this->method = $method;

        // Add new params
        if ($data) {
            $this->params = array_merge($this->params, $data);
        }

        // Process data, if presented
        $queryString = '';
        if ($this->rawPostData) {
            $queryString = $this->rawPostData;
        } elseif ($this->params) {
            $queryString = http_build_query($this->params, '', '&');
        }

        // GET method configuration
        if ($this->method == 'GET') {
            if ($queryString) {
                $this->target = $this->target . '?' . $queryString;
            }
        }

        // Parse target URL
        $urlParsed = parse_url($this->target);
        if ($this->port == 0 && isset($urlParsed['port']) && $urlParsed['port'] > 0) {
            $this->port = $urlParsed['port'];
        }

        $this->host = $urlParsed['host'];
        // Handle SSL connection request
        if ($urlParsed['scheme'] == 'https') {
            $this->port = ($this->port != 0) ? $this->port : 443;
            $this->_remote = 'ssl://'.$this->host.':'.$this->port; // OR tls://... ??
/*          if internal-use only, skip verification
            $opts = [
            'ssl' => [
//              'allow_self_signed' => true,
                'verify_host' => false,
                'verify_peer' => false,
             ],
            'tls' => [
//              'allow_self_signed' => true,
                'verify_host' => false,
                'verify_peer' => false,
             ]
            ];
            $ctx = stream_context_create($opts); //, $params);
*/
        } else {
            $this->port = ($this->port != 0) ? $this->port : 80;
            $this->_remote = 'tcp://'.$this->host.':'.$this->port;
//          $ctx = stream_context_create();
        }

        // Finalize the target path
        $this->path   = ($urlParsed['path'] ?? '/') . (isset($urlParsed['query']) ? '?' . $urlParsed['query'] : '');
        $this->schema = $urlParsed['scheme'];

        // Pass the requred cookies
        $this->_passCookies();

        // Process cookies, if requested
        $cookieString = '';
        if ($this->cookies) {
            // Get a blank slate
            $tempString   = [];

            // Convert cookiesa array into a query string (ie animal=dog&sport=baseball)
            foreach ($this->cookies as $key => $value) {
                if (strlen(trim($value)) > 0) {
                    $tempString[] = $key . '=' . urlencode($value);
                }
            }

            $cookieString = implode('&', $tempString);
        }

        // Do we want to use cURL ? If not, revert to a stream-socket
        if ($this->useCurl && self::is_curl_suitable()) {
            // Initialize PHP cURL handle
            $ch = curl_init();

            if ($this->method == 'GET') {
                // GET method configuration
                curl_setopt($ch, CURLOPT_HTTPGET, true);
// redundant    curl_setopt($ch, CURLOPT_POST, false);
            } else {
                // POST method configuration
                curl_setopt($ch, CURLOPT_POST, true);
// no effect    curl_setopt($ch, CURLOPT_HTTPGET, false);

                if (isset($queryString)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $queryString);
                }
            }

            // Basic Authentication configuration
            if ($this->username && $this->password) {
                curl_setopt($ch, CURLOPT_USERPWD, $this->username . ':' . $this->password);
            }

            if ($this->proxy) {
                curl_setop($ch, CURL_PROXY, $this->proxy);
            }

            // Custom cookie configuration
            if ($this->useCookie) {
                // we are sending cookies.
                if (isset($cookieString)) {
                    curl_setopt($ch, CURLOPT_COOKIE, $cookieString);
                } else {
                    curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookiePath);
                }
            }
            if ($this->saveCookie) {
                curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookiePath); // Save cookies here.
            }

            curl_setopt($ch, CURLOPT_HEADER, true);                // No need of headers
            if ($this->headerArray) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headerArray);
/* duplicate} else {
                curl_setopt($ch, CURLOPT_HEADER, true);           // No need of headers
*/
            }
            curl_setopt_array($ch, [
             CURLOPT_NOBODY => false,              // Return body
             CURLOPT_CONNECTTIMEOUT => $this->connecttime, // Establishment delay limit
             CURLOPT_TIMEOUT => $this->timeout,    // Total-duration limit
             CURLOPT_USERAGENT => $this->userAgent,// Webbot name
             CURLOPT_URL => $this->target,         // Target site
             CURLOPT_REFERER => $this->referrer,   // Referer value
             CURLOPT_VERBOSE => false,             // Minimize logs
             CURLOPT_SSL_VERIFYPEER => false,      // No certificate check
             CURLOPT_FOLLOWLOCATION => $this->redirect,// Follow redirects
             CURLOPT_MAXREDIRS => $this->maxRedirect,  // Limit redirections to four
             CURLOPT_RETURNTRANSFER => true,       // Return in string
            ]);
            // Get the target contents
            $content = curl_exec($ch);
            if ($content) {
                $tmp = explode("\r\n\r\n", $content, 2);
                for ($i = 0, $n = count($tmp); $i < $n; $i++) {
                    if (!$tmp[$i]) {
                        unset($tmp[$i]);
                    }
                }

                if (isset($tmp[1])) {
                    // Store the contents
                    $this->result = $tmp[1];
                }

                if (isset($tmp[0])) {
                    // Parse the headers
                    $this->_parseHeaders($tmp[0]);
                }
            }

            // Get the request info
            $status  = curl_getinfo($ch); //DEBUG ?

            // Store the error (if any)
            $this->_setError(curl_error($ch));

            // Close PHP cURL handle
            curl_close($ch);
        } else {
            // Get a stream-handle
            $res = @stream_socket_client($this->_remote, $errorNumber, $errorString, $this->connecttime); //,  STREAM_CLIENT_CONNECT, $ctx);

            // error if handle N/A
            if (!$res) {
                $this->_setError('Failed opening http socket connection: ' . $errorString . ' (' . $errorNumber . ')');
                return false;
            }

            // Set http headers with host, user-agent and content type
            $this->addRequestHeader($this->method .' '. $this->path. ' HTTP/1.1', true);
            $this->addRequestHeader('Host: ' . $this->host);
            $this->addRequestHeader('Accept: */*');
            $this->addRequestHeader('User-Agent: ' . $this->userAgent);
            if (!$this->requestHeaderExists('Content-Type')) {
                if (!$this->requestHeaderExists('Transfer-Encoding')) {
                    $this->addRequestHeader('Content-Type: application/x-www-form-urlencoded; charset=UTF-8'); // TODO for POST only ?
                } else {
                    $this->addRequestHeader('Content-Type: application/x-www-form-urlencoded');
                }
            }

            // Specify the custom cookies
            if ($this->useCookie && $cookieString != '') {
                $this->addRequestHeader('Cookie: ' . $cookieString);
            }

            // POST method configuration
            if ($this->method == 'POST') {
                $this->addRequestHeader('Content-Length: ' . strlen($queryString));
            }

            // Specify the referrer
            if ($this->referrer != '') {
                $this->addRequestHeader('Referer: ' . $this->referrer);
            }

            // Specify http authentication (basic)
            if ($this->username && $this->password) {
                $this->addRequestheader('Authorization: Basic ' . base64_encode($this->username . ':' . $this->password));
            }

            $this->addRequestHeader('Connection: close');

            // POST method configuration
            $reqData = implode("\r\n", $this->headerArray)."\r\n\r\n";
            if ($this->method == 'POST' && $queryString) { // TODO checkme parms for GET
                $reqData .= $queryString;
            }

            // We're ready to launch
            fwrite($res, $reqData);
            $content = stream_get_contents($res);
            stream_socket_shutdown($res, STREAM_SHUT_RDWR);

            if ($content) {
                $tmp = explode("\r\n\r\n", $content);
                for ($i = 0, $n = count($tmp); $i < $n; $i++) {
                    if (empty($tmp[$i])) {
                        unset($tmp[$i]);
                    }
                }
                if (isset($tmp[0])) {
                    // Parse the headers
                    $this->_parseHeaders($tmp[0]);
                } else {
                    return '';
                }

                // Do we have a 301/302 redirect ?
                if (($this->status == '301' || $this->status == '302') && $this->redirect) {
                    if ($this->curRedirect < $this->maxRedirect) {
                        // Let's find out the new redirect URL
                        $newUrlParsed = parse_url($this->headers['location']);

                        if ($newUrlParsed['host']) {
                            $newTarget = $this->headers['location'];
                        } else {
                            $newTarget = $this->schema . '://' . $this->host . '/' . $this->headers['location'];
                        }

                        // Reset some of the properties
                        $this->port   = 0;
                        $this->status = 0;
                        $this->params = [];
                        $this->method = 'POST';
                        $this->referrer = $this->target;

                        // Increase the redirect counter
                        $this->curRedirect++;

                        // Let's go !
                        $this->result = $this->execute($newTarget); // recurse
                    } else {
                        $this->_setError('Too many redirects.');
                        return '';
                    }
                } elseif (isset($tmp[1])) {
                    // Store the contents
                    // TODO fix this hack
                    // work around format of content provided via socket, like
                    // N[N...]<newline>REALCONTENTS<newline>0 where the leading number is prob. a hex btyelength of the content
                    $val = rtrim($tmp[1], "\r\n 0"); // kill trailing line
                    $p = strpos($val, "\n");
                    $this->result = ltrim(substr($val, $p)); // kill leading line
                }
            }
        }
        // There it is! We have it!! Return to base !!!
        return $this->result;
    }

    /**
     * Parse Headers (internal)
     *
     * Parse the response headers and store them for finding the response
     * status, redirection location, cookies, etc.
     *
     * @param string $responseHeader Raw header response
     * @access private
     * @internal
     */
    private function _parseHeaders($responseHeader)
    {
        // Break up the headers
        $headers = explode("\r\n", $responseHeader);

        // Clear the header array
        $this->_clearHeaders();

        // Get resposne status
        if ($this->status == 0) {
            // Oooops !
            if (!preg_match("/http\/[0-9]+\.[0-9]+[ \t]+([0-9]+)[ \t]*(.*)\$/i", $headers[0], $matches)) {
                $this->_setError('Unexpected HTTP response status');
                return false;
            }

            // Gotcha!
            $this->status = $matches[1];
            array_shift($headers);
        }

        // Prepare all the other headers
        foreach ($headers as $header) {
            // Get name and value
            $headerName  = strtolower($this->_tokenize($header, ':'));
            $headerValue = trim(chop($this->_tokenize("\r\n")));

            // If its already there, then add as an array. Otherwise, just keep there
            if (isset($this->headers[$headerName])) {
                if (gettype($this->headers[$headerName]) == 'string') {
                    $this->headers[$headerName] = [$this->headers[$headerName]];
                }

                $this->headers[$headerName][] = $headerValue;
            } else {
                $this->headers[$headerName] = $headerValue;
            }
        }

        // Save cookies if asked
        if ($this->saveCookie && isset($this->headers['set-cookie'])) {
            $this->_parseCookie();
        }
    }

    /**
     * Clear the headers array (internal)
     *
     * @internal
     * @access private
     */
    public function _clearHeaders()
    {
        $this->headers = [];
    }

    /**
     * Parse Cookies (internal)
     *
     * Parse the set-cookie headers from response and add them for inclusion.
     *
     * @access private
     * @internal
     */
    public function _parseCookie()
    {
        // Get the cookie header as array
        if (gettype($this->headers['set-cookie']) == 'array') {
            $cookieHeaders = $this->headers['set-cookie'];
        } else {
            $cookieHeaders = [$this->headers['set-cookie']];
        }

        // Loop through the cookies
        for ($cookie = 0, $n = count($cookieHeaders); $cookie < $n; $cookie++) {
            $cookieName  = trim($this->_tokenize($cookieHeaders[$cookie], '='));
            $cookieValue = $this->_tokenize(';');

            $urlParsed   = parse_url($this->target);

            $domain      = $urlParsed['host'];
            $secure      = '0';

            $path        = '/';
            $expires     = '';

            while (($name = trim(urldecode($this->_tokenize('=')))) != '') {
                $value = urldecode($this->_tokenize(';'));

                switch ($name) {
                case 'path': $path     = $value; break;
                case 'domain': $domain = $value; break;
                case 'secure': $secure = ($value != '') ? '1' : '0'; break;
                }
            }

            $this->_setCookie($cookieName, $cookieValue, $expires, $path, $domain, $secure);
        }
    }

    /**
     * Set cookie (internal)
     *
     * Populate the internal _cookies array for future inclusion in
     * subsequent requests. This actually validates and then populates
     * the object properties with a dimensional entry for cookie.
     *
     * @param string Cookie name
     * @param string Cookie value
     * @param string Cookie expire date
     * @param string Cookie path
     * @param string Cookie domain
     * @param string Cookie security (0 = non-secure, 1 = secure)
     * @access private
     * @internal
     */
    private function _setCookie(string $name, string $value, string $expires = '', string $path = '/', string $domain = '', int $secure = 0)
    {
        if (strlen($name) == 0) {
            return($this->_setError('No valid cookie name was specified.'));
        }

        if (strlen($path) == 0 || strcmp($path[0], '/')) {
            return($this->_setError("$path is not a valid path for setting cookie $name."));
        }

        if ($domain == '' || !strpos($domain, '.', $domain[0] == '.' ? 1 : 0)) {
            return($this->_setError("$domain is not a valid domain for setting cookie $name."));
        }

        $domain = strtolower($domain);

        if (!strcmp($domain[0], '.')) {
            $domain = substr($domain, 1);
        }

        $name  = $this->_encodeCookie($name, true);
        $value = $this->_encodeCookie($value, false);

        $secure = (int)$secure;

        $this->_cookies[] = [
            'name'      =>  $name,
            'value'     =>  $value,
            'domain'    =>  $domain,
            'path'      =>  $path,
            'expires'   =>  $expires,
            'secure'    =>  $secure
        ];
    }

    /**
     * Encode cookie name/value (internal)
     *
     * @param string $value Value of cookie to encode
     * @param string $name Name of cookie to encode
     * @return string encoded string
     * @access private
     * @internal
     */
    private function _encodeCookie(string $value, string $name)
    {
        return($name ? str_replace('=', '%25', $value) : str_replace(';', '%3B', $value));
    }

    /**
     * Pass Cookies (internal)
     *
     * Get the cookies which are valid for the current request. Checks
     * domain and path to decide the return.
     */
    public function _passCookies()
    {
        if ($this->_cookies) {
            $urlParsed = parse_url($this->target);
            $tempCookies = [];

            foreach ($this->_cookies as $cookie) {
                if ($this->_domainMatch($urlParsed['host'], $cookie['domain']) && (0 === strpos($urlParsed['path'], $cookie['path']))
                    && (empty($cookie['secure']) || $urlParsed['protocol'] == 'https')) {
                    $tempCookies[$cookie['name']][strlen($cookie['path'])] = $cookie['value'];
                }
            }

            // cookies with longer paths go first
            foreach ($tempCookies as $name => $values) {
                krsort($values);
                foreach ($values as $value) {
                    $this->addCookie($name, $value);
                }
            }
        }
    }

    /**
     * Checks if cookie domain matches a request host (internal)
     *
     * Cookie domain can begin with a dot, it also must contain at least
     * two dots.
     *
     * @param string $requestHost Request host
     * @param string $cookieDomain Cookie domain
     * @return bool Match success
     * @access private
     * @internal
     */
    public function _domainMatch($requestHost, $cookieDomain)
    {
        if ('.' != $cookieDomain{0}) {
            return $requestHost == $cookieDomain;
        } elseif (substr_count($cookieDomain, '.') < 2) {
            return false;
        } else {
            return substr('.'. $requestHost, - strlen($cookieDomain)) == $cookieDomain;
        }
    }

    /**
     * Tokenize String (internal)
     *
     * Tokenize string for various internal usage. Omit the second parameter
     * to tokenize the previous string that was provided in the prior call to
     * the function.
     *
     * @param string $string The string to tokenize
     * @param string $separator The seperator to use
     * @return string Tokenized string
     * @access private
     * @internal
     */
    private function _tokenize(string $string, string $separator = '')
    {
        if (strcmp($separator, '')) {
            $separator = $string;
            $string = $this->nextToken;
        }

        for ($character = 0; $character < strlen($separator); $character++) {
            if (gettype($position = strpos($string, $separator[$character])) == 'integer') {
                $found = (isset($found) ? min($found, $position) : $position);
            }
        }

        if (isset($found)) {
            $this->nextToken = substr($string, $found + 1);
            return(substr($string, 0, $found));
        } else {
            $this->nextToken = '';
            return($string);
        }
    }

    /**
     * Set error message (internal)
     *
     * @param string $error Error message
     * @return mixed string|null
     * @access private
     */
    private function _setError($error)
    {
        if ($error != '') {
            $this->error = $error;
            return $error;
        }
    }
}

<?php
/*
require_once 'Mailchimp/Folders.php';
require_once 'Mailchimp/Templates.php';
require_once 'Mailchimp/Users.php';
require_once 'Mailchimp/Helper.php';
require_once 'Mailchimp/Mobile.php';
require_once 'Mailchimp/Conversations.php';
require_once 'Mailchimp/Ecomm.php';
require_once 'Mailchimp/Neapolitan.php';
require_once 'Mailchimp/Lists.php';
require_once 'Mailchimp/Campaigns.php';
require_once 'Mailchimp/Vip.php';
require_once 'Mailchimp/Reports.php';
require_once 'Mailchimp/Gallery.php';
require_once 'Mailchimp/Goal.php';
*/
require_once 'Mailchimp/Exceptions.php';

class Mailchimp {

    public $apikey;
    public $ch;
    public $root  = 'https://api.mailchimp.com/2.0';
    public $debug = false;

    public static $error_map = array(
        'Absplit_UnknownError' => '',
        'Absplit_UnknownSplitTest' => '',
        'Absplit_UnknownTestType' => '',
        'Absplit_UnknownWaitUnit' => '',
        'Absplit_UnknownWinnerType' => '',
        'Absplit_WinnerNotSelected' => '',
        'Avesta_Db_Exception' => '',
        'Campaign_BounceMissing' => '',
        'Campaign_DoesNotExist' => '',
        'Campaign_InvalidAbsplit' => '',
        'Campaign_InvalidAuto' => '',
        'Campaign_InvalidContent' => '',
        'Campaign_InvalidOption' => '',
        'Campaign_InvalidRss' => '',
        'Campaign_InvalidSegment' => '',
        'Campaign_InvalidStatus' => '',
        'Campaign_InvalidTemplate' => '',
        'Campaign_NotSaved' => '',
        'Campaign_StatsNotAvailable' => '',
        'Conversation_DoesNotExist' => '',
        'Conversation_ReplySaveFailed' => '',
        'Email_AlreadySubscribed' => '',
        'Email_AlreadyUnsubscribed' => '',
        'Email_NotExists' => '',
        'Email_NotSubscribed' => '',
        'File_Not_Found_Exception' => '',
        'Folder_Exists_Exception' => '',
        'Folder_Not_Found_Exception' => '',
        'Goal_SaveFailed' => '',
        'Invalid_Analytics' => '',
        'Invalid_ApiKey' => '',
        'Invalid_AppKey' => '',
        'Invalid_DateTime' => '',
        'Invalid_EcommOrder' => '',
        'Invalid_Email' => '',
        'Invalid_Folder' => '',
        'Invalid_IP' => '',
        'Invalid_Options' => '',
        'Invalid_PagingLimit' => '',
        'Invalid_PagingStart' => '',
        'Invalid_SendType' => '',
        'Invalid_Template' => '',
        'Invalid_TrackingOptions' => '',
        'Invalid_URL' => '',
        'List_AlreadySubscribed' => '',
        'List_CannotRemoveEmailMerge' => '',
        'List_DoesNotExist' => '',
        'List_InvalidBounceMember' => '',
        'List_InvalidImport' => '',
        'List_InvalidInterestFieldType' => '',
        'List_InvalidInterestGroup' => '',
        'List_InvalidMergeField' => '',
        'List_InvalidOption' => '',
        'List_InvalidUnsubMember' => '',
        'List_Merge_InvalidMergeID' => '',
        'List_MergeFieldRequired' => '',
        'List_NotSubscribed' => '',
        'List_TooManyInterestGroups' => '',
        'List_TooManyMergeFields' => '',
        'Max_Size_Reached' => '',
        'MC_ContentImport_InvalidArchive' => '',
        'MC_InvalidPayment' => '',
        'MC_PastedList_Duplicate' => '',
        'MC_PastedList_InvalidImport' => '',
        'MC_SearchException' => '',
        'Module_Unknown' => '',
        'MonthlyPlan_Unknown' => '',
        'Order_TypeUnknown' => '',
        'Parse_Exception' => '',
        'PDOException' => '',
        'Request_TimedOut' => '',
        'ServerError_InvalidParameters' => '',
        'ServerError_MethodUnknown' => '',
        'Too_Many_Connections' => '',
        'Unknown_Exception' => '',
        'User_CannotSendCampaign' => '',
        'User_Disabled' => '',
        'User_DoesExist' => '',
        'User_DoesNotExist' => '',
        'User_InvalidAction' => '',
        'User_InvalidRole' => '',
        'User_MissingEmail' => '',
        'User_MissingModuleOutbox' => '',
        'User_ModuleAlreadyPurchased' => '',
        'User_ModuleNotPurchased' => '',
        'User_NotApproved' => '',
        'User_NotEnoughCredit' => '',
        'User_UnderMaintenance' => '',
        'User_Unknown' => '',
        'ValidationError' => '',
        'XML_RPC2_Exception' => '',
        'XML_RPC2_FaultException' => '',
        'Zend_Uri_Exception' => ''
    );

    public function __construct($apikey=null, $opts=array()) {
        if (!function_exists('curl_version')) {
            throw new \RuntimeException("PHP's cURL mechansim is needed by MailChimp");
        }

        if (!$apikey) {
            $apikey = getenv('MAILCHIMP_APIKEY');
        }

        if (!$apikey) {
            $apikey = $this->readConfigs();
        }

        if (!$apikey) {
            throw new Mailchimp_Error('You must provide a MailChimp API key');
        }

        $this->apikey = $apikey;
        $dc           = 'us1';

        if (strstr($this->apikey, '-')){
            list($key, $dc) = explode('-', $this->apikey, 2);
            if (!$dc) {
                $dc = 'us1';
            }
        }

        $this->root = str_replace('https://api', 'https://' . $dc . '.api', $this->root);
        $this->root = rtrim($this->root, '/') . '/';

        if (!isset($opts['timeout']) || !is_int($opts['timeout'])) {
            $opts['timeout'] = 600;
        }
        if (isset($opts['debug'])) {
            $this->debug = true;
        }

        $this->ch = curl_init();

        if (!empty($opts['CURLOPT_FOLLOWLOCATION'])) {
            curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
        }

        curl_setopt_array($this->ch, array(
         CURLOPT_USERAGENT, 'MailChimp-PHP/2.0.6',
         CURLOPT_POST, true,
         CURLOPT_HEADER, false,
         CURLOPT_RETURNTRANSFER, true,
         CURLOPT_CONNECTTIMEOUT, 30,
         CURLOPT_TIMEOUT, $opts['timeout']
        ));
    }

    public function __destruct() {
        if(is_resource($this->ch)) {
            curl_close($this->ch);
        }
    }

    // class properties populated on demand
    public function __get($name) {
        switch($name) {
            case 'campaigns':
            case 'conversations':
            case 'ecomm':
            case 'folders':
            case 'gallery':
            case 'goal':
            case 'helper':
            case 'lists':
            case 'mobile':
            case 'neapolitan':
            case 'reports':
            case 'templates':
            case 'users':
            case 'vip':
                if(!isset($this->$name)) {
                    $class = 'Mailchimp_'.ucfirst($name);
                    $this->$name = new $class($this);
                }
                return $this->$name;
            default:
            break;
        }
    }

    public function call($url, $params) {
        $params['apikey'] = $this->apikey;

        $params = json_encode($params);
        $ch     = $this->ch;

        curl_setopt_array($ch, array(
         CURLOPT_URL, $this->root . $url . '.json',
         CURLOPT_HTTPHEADER, array('Content-Type: application/json'),
         CURLOPT_POSTFIELDS, $params,
         CURLOPT_VERBOSE, $this->debug
        ));

        $start = microtime(true);
        $this->log('Call to ' . $this->root . $url . '.json: ' . $params);
        if($this->debug) {
            $curl_buffer = fopen('php://memory', 'w+');
            curl_setopt($ch, CURLOPT_STDERR, $curl_buffer);
        }

        $response_body = curl_exec($ch);

        $info = curl_getinfo($ch);
        $time = microtime(true) - $start;
        if($this->debug) {
            rewind($curl_buffer);
            $this->log(stream_get_contents($curl_buffer));
            fclose($curl_buffer);
        }
        $this->log('Completed in ' . number_format($time * 1000, 2) . 'ms');
        $this->log('Got response: ' . $response_body);

        if(curl_error($ch)) {
            throw new Mailchimp_HttpError("API call to $url failed: " . curl_error($ch));
        }
        $result = json_decode($response_body, true);

        if(floor($info['http_code'] / 100) >= 4) {
            throw $this->castError($result);
        }

        return $result;
    }

    public function readConfigs() {
        $paths = array('~/.mailchimp.key', '/etc/mailchimp.key');
        foreach($paths as $path) {
            if(file_exists($path)) {
                $apikey = trim(file_get_contents($path));
                if ($apikey) {
                    return $apikey;
                }
            }
        }
        return false;
    }

    public function castError($result) {
        if ($result['status'] !== 'error' || !$result['name']) {
            throw new Mailchimp_Error('We received an unexpected error: ' . json_encode($result));
        }

        $class = (isset(self::$error_map[$result['name']])) ? self::$error_map[$result['name']] : 'Mailchimp_Error';
        if($class == '') {
            $class = 'Mailchimp_'.$result['name'];
        }
        return new $class($result['error'], $result['code']);
    }

    public function log($msg) {
        if ($this->debug) {
            error_log($msg);
        }
    }
}

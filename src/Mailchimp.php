<?php
/**
 * mailchimp-lib Magento Component
 *
 * @category Ebizmarts
 * @package mailchimp-lib
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 4/27/16 4:36 PM
 * @file: Mailchimp.php
 */

require_once 'Mailchimp/Abstract.php';
require_once 'Mailchimp/Lists.php';
require_once 'Mailchimp/Exceptions.php';
require_once 'Mailchimp/Root.php';
require_once 'Mailchimp/AuthorizedApps.php';
require_once 'Mailchimp/Automation.php';
require_once 'Mailchimp/BatchOperations.php';
require_once 'Mailchimp/CampaignFolders.php';
require_once 'Mailchimp/Campaigns.php';
require_once 'Mailchimp/Conversations.php';
require_once 'Mailchimp/EcommerceStores.php';
require_once 'Mailchimp/EcommerceCarts.php';
require_once 'Mailchimp/EcommerceCustomers.php';
require_once 'Mailchimp/EcommerceOrders.php';
require_once 'Mailchimp/EcommerceProducts.php';
require_once 'Mailchimp/EcommerceProductsVariants.php';
require_once 'Mailchimp/FileManagerFiles.php';
require_once 'Mailchimp/FileManagerFolders.php';


class Mailchimp
{
    protected $_apiKey;
    protected $_ch;
    protected $_root    = 'https://api.mailchimp.com/3.0';
    protected $_debug   = false;

    const POST      = 'POST';
    const GET       = 'GET';

    public function __construct($apiKey=null,$opts=array())
    {
        if(!$apiKey)
        {
            throw new Mailchimp_Error('You must provide a MailChimp API key');
        }
        $this->_apiKey   = $apiKey;
        $dc             = 'us1';
        if (strstr($this->_apiKey, "-")){
            list($key, $dc) = explode("-", $this->_apiKey, 2);
            if (!$dc) {
                $dc = "us1";
            }
        }
        $this->_root = str_replace('https://api', 'https://' . $dc . '.api', $this->_root);
        $this->_root = rtrim($this->_root, '/') . '/';

        if (!isset($opts['timeout']) || !is_int($opts['timeout'])){
            $opts['timeout'] = 600;
        }
        if (isset($opts['debug'])){
            $this->_debug = true;
        }


        $this->_ch = curl_init();

        if (isset($opts['CURLOPT_FOLLOWLOCATION']) && $opts['CURLOPT_FOLLOWLOCATION'] === true) {
            curl_setopt($this->_ch, CURLOPT_FOLLOWLOCATION, true);
        }

        curl_setopt($this->_ch, CURLOPT_USERAGENT, 'Ebizmart-MailChimp-PHP/3.0.0');
        curl_setopt($this->_ch, CURLOPT_HEADER, false);
        curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->_ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($this->_ch, CURLOPT_TIMEOUT, $opts['timeout']);
        curl_setopt($this->_ch, CURLOPT_USERPWD, "noname:".$this->_apiKey);

        $this->lists                            = new Mailchimp_Lists($this);
        $this->lists->segments                  = new Mailchimp_ListsSegments($this);
        $this->root                             = new Mailchimp_Root($this);
        $this->authorizedApps                   = new Mailchimp_AuthorizedApps($this);
        $this->automation                       = new Mailchimp_Automation($this);
        $this->batchOperation                   = new Mailchimp_BatchOperations($this);
        $this->campaignFolders                  = new Mailchimp_CampaignFolders($this);
        $this->campaigns                        = new Mailchimp_Campaigns($this);
        $this->conversations                    = new Mailchimp_Conversations($this);
        $this->ecommerce                        = new Mailchimp_Abstract($this);
        $this->ecommerce->stores                = new Mailchimp_EcommerceStore($this);
        $this->ecommerce->carts                 = new Mailchimp_EcommerceCarts($this);
        $this->ecommerce->customers             = new Mailchimp_EcommerceCustomers($this);
        $this->ecommerce->orders                = new Mailchimp_EcommerceOrders($this);
        $this->ecommerce->orders->lines         = new Mailchimp_EcommerceOrdersLines($this);
        $this->ecommerce->products              = new Mailchimp_EcommerceProducts($this);
        $this->ecommerce->products->variants    = new Mailchimp_EcommerceProductsVariants($this);
        $this->fileManagerFiles                 = new Mailchimp_FileManagerFiles($this);
        $this->fileManagerFolders               = new Mailchimp_FileManagerFolders($this);

    }
    public function call($url,$params,$method='GET')
    {
        if(count($params))
        {
            $params = json_encode($params);
        }

        $ch = $this->_ch;
        curl_setopt($ch, CURLOPT_URL, $this->_root . $url);
        if(count($params))
        {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_VERBOSE, $this->_debug);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST,$method);


        $response_body = curl_exec($ch);

        $info = curl_getinfo($ch);
        if(curl_error($ch)) {
            throw new Mailchimp_HttpError("API call to $url failed: " . curl_error($ch));
        }
        $result = json_decode($response_body, true);

        if(floor($info['http_code'] / 100) >= 4) {
            throw new Mailchimp_Error($result['title'].' : '.$result['detail']);
        }

        return $result;
    }
}
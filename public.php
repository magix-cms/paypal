<?php
require __DIR__  . '/PayPal-PHP-SDK/autoload.php';
require __DIR__  . '/PayPal-PHP-SDK/common.php';

use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Api\PaymentExecution;
use PayPal\Api\Capture;
// Parameters for style and presentation.
use PayPal\Api\Presentation;


include_once ('db.php');
class plugins_paypal_public extends plugins_paypal_db
{
    protected $template, $header, $data, $getlang, $moreinfo, $sanitize, $mail, $origin, $modelDomain, $config, $settings,$bridge;
    public $msg, $type, $purchase,$id_account,$about, $custom,$payment_plugin = true,$order,
        $redirect;

    /**
     * frontend_controller_home constructor.
     */
    public function __construct($t = null)
    {
        $this->template = $t instanceof frontend_model_template ? $t : new frontend_model_template();
        $formClean = new form_inputEscape();
        $this->sanitize = new filter_sanitize();
        //$this->header = new component_httpUtils_header($this->template);
        $this->header = new http_header();
        $this->data = new frontend_model_data($this);
        $this->getlang = $this->template->lang;
        $this->about = new frontend_model_about($this->template);
        $this->mail = new mail_swift('mail');
        $this->modelDomain = new frontend_model_domain($this->template);
        $this->config = $this->getItems('config', null, 'one', false);
        $this->settings = new frontend_model_setting();
        //$this->bridge = new plugins_bridge_public();
        //$this->ws = new frontend_model_webservice();

        if (http_request::isPost('msg')) {
            $this->msg = $formClean->arrayClean($_POST['msg']);
        }

        /*if (http_request::isPost('type')) {
            $this->type = $formClean->simpleClean($_POST['type']);
        }*/
        //id_account
        if(http_request::isSession('id_account')){
            $this->id_account = $_SESSION['id_account'];
        }

        if (http_request::isPost('purchase')) {
            $this->purchase = $formClean->arrayClean($_POST['purchase']);
        }
        if (http_request::isPost('custom')) {
            $array = $_POST['custom'];
            $array['order'] = $formClean->simpleClean($this->order);
            $this->custom = $array;
        }
    }
    /**
     * Assign data to the defined variable or return the data
     * @param string $type
     * @param string|int|null $id
     * @param string $context
     * @param boolean $assign
     * @return mixed
     */
    private function getItems($type, $id = null, $context = null, $assign = true) {
        return $this->data->getItems($type, $id, $context, $assign);
    }

    /**
     * @return mixed
     */
    private function setItemsAccount(){
        return $this->getItems('root',NULL,'one',false);
    }
    /**
     * Update data
     * @param $data
     * @throws Exception
     */
    private function add($data)
    {
        switch ($data['type']) {
            case 'history':
                parent::insert(
                    array(
                        //'context' => $data['context'],
                        'type' => $data['type']
                    ),
                    $data['data']
                );
                break;
        }
    }

    /**
     * @param $setConfig
     * @return array
     */
    private function setUrl($setConfig){
        $baseUrl = http_url::getUrl();
        $lang = $this->template->currentLanguage();
        $setConfig['plugin'] = isset($setConfig['plugin']) ? $setConfig['plugin'] : false;
        if($setConfig['plugin']) {
            $url = $baseUrl . '/'. $lang . '/' . $setConfig['plugin'] . '/';
            return array(
                'return' => $url . '?status=paid',
                'cancel' => $url . '?status=canceled'
            );
        }
    }

    /**
     * @param $config
     * @param $data
     */
    public function createPayment($config){

        //https://github.com/paypal/PayPal-PHP-SDK/tree/master/sample
        //https://github.com/novayadi85/paypal-SDK/blob/master/checkout.php
        $data = $this->setItemsAccount();
        // After Step 1
        $apiContext = new \PayPal\Rest\ApiContext(
            new \PayPal\Auth\OAuthTokenCredential(
                $data['clientid'], // ClientID
                $data['clientsecret']  // ClientSecret
            )
        );
        $apiContext->setConfig(
            array(
                'mode' => $data['mode'],
                'log.LogEnabled' => ($data['log'] == '1') ? true : false,
                'log.FileName' => component_core_system::basePath().'var/logs/PayPal.log',
                'log.LogLevel' => 'ERROR'
            )
        );
        // Parameters for style and presentation.
        $presentation = new Presentation();
        $presentation->setLocaleCode($this->getlang);

        // Create new payer and method
        $payer = new Payer();
        $payer->setPaymentMethod("paypal");
        //$payer->setPaymentMethod("credit_card");

        $item = new Item();
        // setName = product/service name
        $item->setName($config['setName'])//'Crédits 10'
            ->setCurrency($config['currency'])
            ->setQuantity($config['quantity'])
            //->setSku("10")
            ->setPrice($config['price']);

        $itemList = new ItemList();
        $itemList->setItems(array($item));

        // Set redirect urls
        $setUrl = $this->setUrl($config);
        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl($setUrl['return'])
            ->setCancelUrl($setUrl['cancel']);

        // Set payment amount
        $amount = new Amount();
        $amount->setCurrency($config['currency'])
            ->setTotal($config['price']);

        // Set transaction object
        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setItemList($itemList)
            ->setDescription("Payment description")
            ->setInvoiceNumber(uniqid())
            ->setCustom($config['custom']);
        //'account=25&credit=10&promocode=RTY25'

        // Create the full payment object
        $payment = new Payment();
        $payment->setIntent('sale')
            ->setPayer($payer)
            ->setRedirectUrls($redirectUrls)
            ->setTransactions(array($transaction));

        // Create payment with valid API context
        try {
            $payment->create($apiContext);

            // Get PayPal redirect URL and redirect user
            $approvalUrl = $payment->getApprovalLink();
            header("location:".$approvalUrl);

            // REDIRECT USER TO $approvalUrl
        } catch (PayPal\Exception\PayPalConnectionException $ex) {
            echo $ex->getCode();
            echo $ex->getData();
            die($ex);
        } catch (Exception $ex) {
            die($ex);
        }
    }

    /**
     * @param $config
     */
    public function captureOrder($config){
        //$getPayment = ['status' => 'pending'];
        $setData = $this->setItemsAccount();
        $apiContext = new \PayPal\Rest\ApiContext(
            new \PayPal\Auth\OAuthTokenCredential(
                $setData['clientid'], // ClientID
                $setData['clientsecret']  // ClientSecret
            )
        );


        $apiContext->setConfig(
            array(
                'mode' => $setData['mode'],
                'log.LogEnabled' => ($setData['log'] == '1') ? true : false,
                'log.FileName' => component_core_system::basePath().'var/logs/PayPal.log',
                'log.LogLevel' => 'ERROR'
            )
        );
        $this->add(array(
            'type' => 'history',
            'data' => array(
                'order_h' => $_GET['token'],
                'status_h' =>  $_GET['status']
            )
        ));
        // ### Approval Status
        // Determine if the user approved the payment or not
        if (isset($_GET['status']) && $_GET['status'] == 'success' && isset($_GET["paymentId"]) && isset($_GET["PayerID"])) {
            // Get the payment Object by passing paymentId
            // payment id was previously stored in session in
            // CreatePaymentUsingPayPal.php
            $paymentId = $_GET['paymentId'];
            $payment = Payment::get($paymentId, $apiContext);
            // ### Payment Execute
            // PaymentExecution object includes information necessary
            // to execute a PayPal account payment.
            // The payer_id is added to the request query parameters
            // when the user is redirected from paypal back to your site
            $execution = new PaymentExecution();
            $execution->setPayerId($_GET['PayerID']);

            try {
                // Execute the payment
                // (See bootstrap.php for more on `ApiContext`)
                $result = $payment->execute($execution, $apiContext);

                //
                if (isset($config['debug']) && $config['debug'] != false && $config['debug'] === 'printer') {
                    // NOTE: PLEASE DO NOT USE RESULTPRINTER CLASS IN YOUR ORIGINAL CODE. FOR SAMPLE ONLY
                    ResultPrinter::printResult("Executed Payment", "Payment", $payment->getId(), $execution, $result);
                    exit();
                }else {
                    try {
                        $payment = Payment::get($paymentId, $apiContext);
                        if ($payment->getState() === 'approved') {
                            $transactions = $payment->getTransactions();
                            $transaction = $transactions[0];
                            $relatedResources = $transaction->getRelatedResources();
                            $relatedResource = $relatedResources[0];
                            $order = $relatedResource->getOrder();
                            parse_str($transaction->custom);

                            /*print '<pre>';
                            var_dump($account);
                            var_dump($promocode);
                            //print_r($relatedResource);
                            //print_r($relatedResource->getSale());
                            print '</pre>';
                            print ($payment->getId()).'<br />';
                            print($payment->getState()).'<br />';
                            print($relatedResource->getSale()->getAmount()->getTotal()).'<br />';*/
                            /*$getPayment = array(
                                'amount' => $relatedResource->getSale()->getAmount()->getTotal(),
                                'currency' => $relatedResource->getSale()->getAmount()->getCurrency(),
                                'method' => 'paypal'
                            );*/

                            $getPayment = [
                                'amount' => $relatedResource->getSale()->getAmount()->getTotal(),
                                'method' => 'paypal',
                                'metadata' => [],//isset($transaction->custom) ? parse_str($transaction->custom) : NULL,
                                'status' => 'paid'
                            ];

                            if(isset($config['debug']) && $config['debug'] == 'printer'){
                                $log = new debug_logger(MP_LOG_DIR);
                                $log->tracelog('start payment');
                                $log->tracelog(json_encode($getPayment));
                                $log->tracelog('sleep');
                            }
                        }else{

                            $getPayment = [
                                'status' => 'failed'
                            ];
                        }
                    } catch (Exception $ex) {
                        // NOTE: PLEASE DO NOT USE RESULTPRINTER CLASS IN YOUR ORIGINAL CODE. FOR SAMPLE ONLY
                        /*ResultPrinter::printError("Get Payment", "Payment", null, null, $ex);
                        exit(1);*/
                        $logger = new debug_logger(MP_LOG_DIR);
                        $logger->log('php', 'error', 'An error has occured : ' . $ex->getMessage(), debug_logger::LOG_MONTH);
                    }


                    /*print '<pre>';
                    var_dump($account);
                    var_dump($promocode);
                    //print_r($relatedResource);
                    //print_r($relatedResource->getSale());
                    print '</pre>';
                    print ($result->getId()).'<br />';
                    print($result->getState()).'<br />';
                    print($relatedResource->getSale()->getAmount()->getTotal()).'<br />';
                    //print($result->getIntent()).'<br />';
                    //print_r($transaction->custom).'<br />';*/

                    //
                }

            } catch (Exception $ex) {
                // NOTE: PLEASE DO NOT USE RESULTPRINTER CLASS IN YOUR ORIGINAL CODE. FOR SAMPLE ONLY
                /*ResultPrinter::printError("Executed Payment", "Payment", null, null, $ex);
                exit(1);*/
                $logger = new debug_logger(MP_LOG_DIR);
                $logger->log('php', 'error', 'An error has occured : '.$ex->getMessage(), debug_logger::LOG_MONTH);
            }
            // NOTE: PLEASE DO NOT USE RESULTPRINTER CLASS IN YOUR ORIGINAL CODE. FOR SAMPLE ONLY
            //ResultPrinter::printResult("Get Payment", "Payment", $payment->getId(), null, $payment);

            //return $payment;
        } elseif(isset($_GET['status']) && $_GET['status'] == 'canceled') {
            //$baseUrl = http_url::getUrl();
            //header("location:".$baseUrl);
            // NOTE: PLEASE DO NOT USE RESULTPRINTER CLASS IN YOUR ORIGINAL CODE. FOR SAMPLE ONLY
            /*ResultPrinter::printResult("User Cancelled the Approval", null);
            exit;*/

            $getPayment = [
                'status' => 'canceled'
            ];

        }else{
            $getPayment = [
                'status' => 'failed'
            ];
        }
        $log = new debug_logger(MP_LOG_DIR);
        $log->tracelog('captureOrder');
        $log->tracelog(json_encode($getPayment));
        $log->tracelog('stop');

        return $getPayment;
    }
    public function getPaymentStatus(){
        $history = $this->getItems('lastHistory',NULL,'one',false);
        return $history['status_h'];
    }
    /**
     *
     */
    public function run(){
        //if(isset($_COOKIE['mc_cart'])) {
        if(isset($_GET['token'])){

            $captureOrder = $this->captureOrder(['debug'=>false]);

            if(is_array($captureOrder)){

                $log = new debug_logger(MP_LOG_DIR);
                $log->tracelog('start payment');
                $log->tracelog(json_encode($captureOrder));
                $log->tracelog('sleep');
            }

            $history = $this->getItems('history',array('order_h'=>$_GET['token']),'one',false);

            $status = 'pending';
            switch ($history['status_h']) {
                case 'paid':
                    $status = 'success';
                    break;
                case 'failed':
                    $status = 'error';
                    break;
                case 'canceled':
                case 'expired':
                    $status = 'canceled';
                    break;
            }
            if(isset($_COOKIE['mc_cart'])) {
                header("location:/$this->getlang/cartpay/order/?step=done_step&status=$status");
            }else{
                if(isset($this->redirect)){
                    $baseUrl = http_url::getUrl();
                    header( "Refresh: 3;URL=$baseUrl/$this->getlang/$this->redirect/" );
                }
                $this->template->display('paypal/index.tpl');
            }

        }else{
            if(isset($this->purchase)){
                // init session
                /*$session = new http_session();
                $session->start('mc_account');
                $session->token('token_ac');
                $array_sess = array(
                    'id_account'   => $_SESSION['id_account'],
                    'keyuniqid_ac' => $_SESSION['keyuniqid_ac'],
                    'email_ac'     => $_SESSION['email_ac']
                );
                $session->run($array_sess);*/
                /*$account = new plugins_account_public();
                $account->securePage();*/

                // create custom field
                /*$data = array(
                    'account'   =>  $_SESSION['id_account'],
                    'credit'    =>  $this->purchase['credit'],
                    'promocode' =>  $this->purchase['promocode']
                );
                $custom = http_build_query($data);*/
                $this->template->addConfigFile(
                    array(component_core_system::basePath() . '/plugins/paypal/i18n/'),
                    array('public_local_'),
                    false
                );
                $this->template->configLoad();

                $collection = $this->about->getCompanyData();
                // config data for payment
                $config = array(
                    'plugin'    =>  'paypal',
                    'setName'   =>  $this->template->getConfigVars('order_on') . ' ' . $collection['name'],
                    'price'    =>  $this->purchase['amount'],
                    'currency' => 'EUR',//$this->purchase['currency'],
                    'quantity'  =>  isset($this->custom['quantity']) ? $this->custom['quantity'] : 1,
                    'debug'     =>  false//pre,none,printer
                );
                $this->createPayment($config);
            }else{
                $this->template->display('paypal/index.tpl');
            }

        }
    }
}
?>
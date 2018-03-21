<?php
include_once ('db.php');
class plugins_paypal_admin extends plugins_paypal_db
{
    public $edit, $action, $tabs;
    protected $controller,$data,$template, $message, $plugins, $xml,$modelLanguage,$collectionLanguage,$header;
    
    public $getlang, $plugin, $id, $getpage, $clientId,$clientSecret,$mode,$log;

    /**
     * constructeur
     */
    public function __construct()
    {
        $this->template = new backend_model_template();
        $this->plugins = new backend_controller_plugins();
        $formClean = new form_inputEscape();
        $this->message = new component_core_message($this->template);
        $this->data = new backend_model_data($this);
        $this->header = new http_header();
        
        // Global

        if (http_request::isGet('edit')) {
            $this->edit = $formClean->numeric($_GET['edit']);
        }
        if (http_request::isGet('action')) {
            $this->action = $formClean->simpleClean($_GET['action']);
        } elseif (http_request::isPost('action')) {
            $this->action = $formClean->simpleClean($_POST['action']);
        }

        if (http_request::isGet('tab')) {
            $this->tab = $formClean->simpleClean($_GET['tab']);
        }

        if (http_request::isGet('id')) {
            $this->id = (integer)$formClean->numeric($_GET['id']);
        }
        // POST
        if (http_request::isPost('clientId')) {
            $this->clientId = $formClean->simpleClean($_POST['clientId']);
        }
        if (http_request::isPost('clientSecret')) {
            $this->clientSecret = $formClean->simpleClean($_POST['clientSecret']);
        }
        if (http_request::isPost('mode')) {
            $this->mode = $formClean->simpleClean($_POST['mode']);
        }
        if (http_request::isPost('log')) {
            $this->log = $formClean->simpleClean($_POST['log']);
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
     * @param $data
     * @throws Exception
     */
    private function upd($data)
    {
        switch ($data['type']) {
            case 'config':
                parent::update(
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
     * Update data
     * @param $data
     * @throws Exception
     */
    private function add($data)
    {
        switch ($data['type']) {
            case 'newConfig':
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


    private function save(){
        $setData = $this->getItems('root',NULL,'one',false);
        if($setData['id_paypal']){

            $this->upd(
                array(
                    'type' => 'config',
                    'data' => array(
                        'clientId'      =>  $this->clientId,
                        'clientSecret'  =>  $this->clientSecret,
                        'mode'          =>  $this->mode,
                        'log'           =>  $this->log,
                        'id'            =>  $setData['id_paypal']
                    )
                )
            );
        }else{
            $this->add(
                array(
                    'type' => 'newConfig',
                    'data' => array(
                        'clientId'      =>  $this->clientId,
                        'clientSecret'  =>  $this->clientSecret,
                        'mode'          =>  $this->mode,
                        'log'           =>  $this->log
                    )
                )
            );
        }
        $this->message->json_post_response(true, 'update');
    }
    /**
     * Execute plugin
     */
    public function run()
    {
        if(isset($this->action)) {
            switch ($this->action) {
                case 'edit':
                    $this->save();
                    break;
            }
        }else{
            $data = $this->getItems('root',NULL,'one',false);
            $this->template->assign('paypal', $data);
            $this->template->display('index.tpl');
        }
    }
}
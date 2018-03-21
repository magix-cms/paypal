<?php
class plugins_paypal_db
{
    /**
     * @param $config
     * @$params bool $data
     * @return mixed|null
     * @throws Exception
     */
    public function fetchData($config, $params = false)
    {
        $sql = '';

        if (is_array($config)) {
            if ($config['context'] === 'all') {
                switch ($config['type']) {
                    case 'data':
                        $sql = 'SELECT hp.* FROM mc_paypal AS hp';
                        break;
                }

                return $sql ? component_routing_db::layer()->fetchAll($sql, $params) : null;
            }
            elseif ($config['context'] === 'one') {
                switch ($config['type']) {
                    case 'root':
                        $sql = 'SELECT * FROM mc_paypal ORDER BY id_paypal DESC LIMIT 0,1';
                        break;
                }

                return $sql ? component_routing_db::layer()->fetch($sql, $params) : null;
            }
        }
    }
    /**
     * @param $config
     * @param array $params
     * @throws Exception
     */
    public function insert($config, $params = array())
    {
        if (is_array($config)) {
            $sql = '';

            switch ($config['type']) {
                case 'newConfig':

                    $sql = 'INSERT INTO mc_paypal (clientId,clientSecret,mode,log)
		            VALUE(:clientId,:clientSecret,:mode,:log)';

                    break;
            }

            if($sql !== '') component_routing_db::layer()->insert($sql,$params);
        }
    }

    /**
     * @param $config
     * @param array $params
     * @throws Exception
     */
    public function update($config, $params = array())
    {
        if (is_array($config)) {
            $sql = '';

            switch ($config['type']) {
                case 'config':
                    $sql = 'UPDATE mc_paypal
                    SET clientId=:clientId,
                        clientSecret=:clientSecret,
                        mode=:mode,
                        log=:log
                    WHERE id_paypal=:id';
                    break;
            }

            if($sql !== '') component_routing_db::layer()->update($sql,$params);
        }
    }
}
?>
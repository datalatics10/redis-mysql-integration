<?php

require_once dirname(__FILE__) . "/config/config.php";
$GLOBALS = array(
    'redis' => $redis,
    'db' => $db,
    'config' => $config
);

class Token {

    private $store_id;

    public function __construct($store_id) {
        $this->store_id = $store_id;
    }

    public function get_unique_token() {
        $redis = $GLOBALS['redis'];
        $db = $GLOBALS['db'];
        $config = $GLOBALS['config'];
        $existing = $redis->lrange($this->store_id, 0, -1);
        if (count($existing) <= 500 && $redis->get($this->store_id . "_status") == '0') {
            $redis->set($this->store_id . "_status", '1');
            $this->update_list();
        }
        $data = $redis->lPop($this->store_id);
        if ($data == FALSE) {
            return "No Token Available";
        } else {
            $data = json_decode($data);
            $stmt = $db->prepare("update " . $config['database']['tablename'] . " set available=0 where pk_id =" . $data->pk_id);
            $stmt->execute();

            return $data->token_code;
        }
    }

    private function update_list() {
        $redis = $GLOBALS['redis'];
        $db = $GLOBALS['db'];
        $config = $GLOBALS['config'];

        $stmt = $db->prepare("select * from " . $config['database']['tablename'] . " where store_id=$this->store_id and available=1 LIMIT 2000");
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($result) > 0) {
            $this->update_helper($result, $redis, $db);
        } elseif (count($result) == 0 && count($redis->lrange($this->store_id, 0, -1)) == 0) {
            $stmt = $db->prepare("select * from " . $config['database']['tablename'] . " where store_id=$this->store_id and available=2");
            $stmt->execute();
            $result1 = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (count($result1) > 0) {
                $this->update_helper($result1, $redis, $db);
            }
        }

        $redis->set($this->store_id . "_status", '0');
    }

    private function update_helper($result, $redis, $db) {
        $config = $GLOBALS['config'];
        $pks = array_map(function($val) {
            return $val['pk_id'];
        }, $result);
        $pks = implode(",", $pks);
        $stmt = $db->prepare("update " . $config['database']['tablename'] . "  set available=2 where pk_id in ($pks)");
        $stmt->execute();
        foreach ($result as $res) {
            $redis->rPush($this->store_id, json_encode($res));
        }
    }

}

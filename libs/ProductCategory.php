<?php

    class ProductCategory {

        public $table = 'keepa_prodcategories';
        public $pdo;

        public $fields = array('asin','node_id');
        public $fieldsDescr = array('asin'=>'s','node_id'=>'s');

        public $belongTo = array(
            'keepa_product'
        );

        public function __construct() {
            $this->pdo = App::$pdo;
        }

        public function remove($asin) {
            $stmt = $this->pdo->prepare('DELETE FROM '. $this->table .' WHERE asin=?');
            $stmt->bindParam(1, $asin);
            $stmt->execute();
            return true;
        }

        public function addAll($categories, $asin) {
            foreach($categories as $cat){
                $stm = $this->pdo->prepare('INSERT INTO `'. $this->table .'` SET '. pdoSet($this->fields, $values, $this->reduceFields(array('asin'=>$asin, 'node_id'=>$cat))));
                $stm->execute($values);
            }
            return true;
        }

        public function updateAll($categories, $asin) {
            $this->remove($asin);
            $this->addAll($categories, $asin);
            return true;
        }

        function reduceFields($product) {
            $product_array = json_decode(json_encode($product), true);
            foreach ($this->fieldsDescr as $key => $value) {
                if(isset($product_array[$key])) {
                    if($value == 'i')
                        $result[$key] = (int)$product_array[$key];
                    else if($value == 'd')
                        $result[$key] = date('Y-m-d H:i:s', keepTimeToUnux($product_array[$key]));
                    else
                        $result[$key] = $product_array[$key];
                } else {
                    if($value == 'i')
                        $result[$key] = null;
                    else
                        $result[$key] = null;
                }
            }
            return $result;
        }
    }
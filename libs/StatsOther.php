<?php

    class StatsOther {

        public $table = 'keepa_stats_others';
        public $pdo;

        public $fields = array('asin', 'totalOfferCount', 'stockAmazon', 'stockBuyBox', 'retrievedOfferCount', 'tradeInPrice', 'buyBoxPrice', 'buyBoxShipping', 'buyBoxIsUnqualified', 'buyBoxIsShippable', 'buyBoxIsPreorder', 'buyBoxIsFBA', 'buyBoxIsAmazon', 'buyBoxIsMAP', 'isAddonItem');

        public $fieldsDescr = array('asin'=>'s', 'totalOfferCount'=>'i', 'stockAmazon'=>'i', 'stockBuyBox'=>'i', 'retrievedOfferCount'=>'i', 'tradeInPrice'=>'i', 'buyBoxPrice'=>'i', 'buyBoxShipping'=>'i', 'buyBoxIsUnqualified'=>'i', 'buyBoxIsShippable'=>'i', 'buyBoxIsPreorder'=>'i', 'buyBoxIsFBA'=>'i', 'buyBoxIsAmazon'=>'i', 'buyBoxIsMAP'=>'i', 'isAddonItem'=>'i');

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

        public function add($stats, $asin) {
            if($this->validate($stats)) {
                $stm = $this->pdo->prepare('INSERT INTO `'. $this->table .'` SET '. pdoSet($this->fields, $values, $this->reduceFields($stats)));
                $values['asin'] = $asin;
                $stm->execute($values);
                return $this->pdo->lastInsertId();
            } else {
                return false;
            }
        }

        function reduceFields($product) {
            $product_array = json_decode(json_encode($product), true); // Be sure that it will be array.
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
                        $result[$key] = '';
                }
            }
            return $result;
        }

        function validate($product) {
            $product_array = json_decode(json_encode($product), true);
            foreach ($this->fields as $value) {
                if(isset($product_array[$value])) {
                    return true;
                }
            }

            return false;
        }
    }
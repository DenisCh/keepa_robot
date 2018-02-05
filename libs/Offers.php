<?php

    class Offers {

        public $table = 'keepa_offers';
        public $pdo;

        public $fields = array('asin', 'offerId', 'lastSeen', 'sellerId', 'condition', 'conditionComment', 'isPrime', 'isMAP', 'isShippable', 'isAddonItem', 'isPreorder', 'isWarehouseDeal', 'isScam', 'isAmazon', 'isPrimeExcl');
        public $hasMany = array(
            'offers_stockCsv',
            'offerCSV'
        );
        public $belongTo = array(
            'keepa_product'
        );

        public $fieldsDescr = array('asin'=>'s', 'offerId'=>'i', 'lastSeen'=>'d', 'sellerId'=>'s', 'condition'=>'i', 'conditionComment'=>'s', 'isPrime'=>'i', 'isMAP'=>'i', 'isShippable'=>'i', 'isAddonItem'=>'i', 'isPreorder'=>'i', 'isWarehouseDeal'=>'i', 'isScam'=>'i', 'isAmazon'=>'i', 'isPrimeExcl'=>'i');

        public function __construct() {
            $this->pdo = App::$pdo;
        }

        function add($offer, $asin) {
            $stm = $this->pdo->prepare('INSERT INTO `'. $this->table .'` SET '. pdoSet($this->fields, $values, $this->reduceFields($offer, $asin)));
            $stm->execute($values);
            return $this->pdo->lastInsertId();
        }

        function addOfferCSV($asin, $category_id) {
            if(!$this->existsCategory($product_id, $category_id)){
                $stm = $this->pdo->prepare('INSERT INTO `product_category` (`category_id`, `product_id`) VALUES (:category_id, :product_id)');
                $stm->execute(array(':category_id'=>$category_id, ':product_id'=>$product_id));
            }
            return true;
        }

        function removeByProduct($asin) {
            $sql = 'DELETE o.*, o1.*, s1.* FROM `'. $this->table .'` as o
                        LEFT JOIN keepa_offers_offerCsv as o1
                            ON o1.offer_id = o.`id`
                        LEFT JOIN keepa_offers_stockCsv as s1
                            ON s1.offer_id = o.`id`
                    WHERE o.`asin` = :asin';
            $stm = $this->pdo->prepare($sql);
            $stm->execute(array('asin'=>$asin));
            return true;
        }

        function reduceFields($offer, $asin) {
            $offer_array = json_decode(json_encode($offer), true);
            $offer_array['asin'] = $asin;
            foreach ($this->fieldsDescr as $key => $value) {
                if(isset($offer_array[$key])) {
                    if($value == 'i')
                        $result[$key] = (int)$offer_array[$key];
                    else if($value == 'd')
                        $result[$key] = date('Y-m-d H:i:s', keepTimeToUnux($offer_array[$key]));
                    else
                        $result[$key] = $offer_array[$key];
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
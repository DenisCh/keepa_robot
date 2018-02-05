<?php

    class Product {

        public $table = 'keepa_products';
        public $pdo;

        public $fields = array('asin', 'domainId', 'parentAsin', 'upc', 'ean', 'mpn', 'rootCategory', 'manufacturer', 'title', 'trackingSince', 'brand', 'label', 'department', 'publisher', 'productGroup', 'partNumber', 'studio', 'genre', 'model', 'color', 'size', 'edition', 'platform', 'format', 'packageHeight', 'packageLength', 'packageWidth', 'packageWeight', 'packageQuantity', 'isAdultProduct', 'isEligibleForTradeIn', 'isEligibleForSuperSaverShipping', 'lastUpdate', 'lastPriceChange', 'productType', 'hasReviews', 'isRedirectASIN', 'isSNS', 'offersSuccessful', 'type');
        public $hasMany = array(
            'keepa_prodcategories',
            'keepa_imagesCSV',
            'keepa_variationCSV',
            'keepa_stats',
            'keepa_stats_min_max',
            'keepa_stats_others',
            'keepa_offers',
            'keepa_buyBoxSellerIdHistory',
            'keepa_csv' // price history and etc.
        );

        public $fieldsDescr = array('asin'=>'s', 'domainId'=>'i', 'parentAsin'=>'s', 'upc'=>'s', 'ean'=>'s', 'mpn'=>'s', 'rootCategory'=>'s', 'manufacturer'=>'s', 'title'=>'s', 'trackingSince'=>'d', 'brand'=>'s', 'label'=>'s', 'department'=>'s', 'publisher'=>'s', 'productGroup'=>'s', 'partNumber'=>'s', 'studio'=>'s', 'genre'=>'s', 'model'=>'s', 'color'=>'s', 'size'=>'s', 'edition'=>'s', 'platform'=>'s', 'format'=>'s', 'packageHeight'=>'i', 'packageLength'=>'i', 'packageWidth'=>'i', 'packageWeight'=>'i', 'packageQuantity'=>'i', 'isAdultProduct'=>'i', 'isEligibleForTradeIn'=>'i', 'isEligibleForSuperSaverShipping'=>'i', 'lastUpdate'=>'d', 'lastPriceChange'=>'d', 'productType'=>'i', 'hasReviews'=>'i', 'isRedirectASIN'=>'i', 'isSNS'=>'i', 'offersSuccessful'=>'i', 'type'=>'s');

        public function __construct() {
            $this->pdo = App::$pdo;
        }

        public function exists($asin) {
            $stmt = $this->pdo->prepare('SELECT `id` FROM '. $this->table .' WHERE asin=?');
            $stmt->bindParam(1, $asin);
            $stmt->execute();
            $row = $stmt->fetch();
            if($row)
                return $row['id'];
            else
                return false;
        }

        function add($product) {
            $stm = $this->pdo->prepare('INSERT INTO `'. $this->table .'` SET '. pdoSet($this->fields, $values, $this->reduceFields($product)));
            $stm->execute($values);
            return $this->pdo->lastInsertId();
        }

        function update($product) {
            $stm = $this->pdo->prepare('UPDATE `'. $this->table .'` SET '. pdoSet($this->fields, $values, $this->reduceFields($product)) .' WHERE asin = :asin');
            $stm->execute($values);
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
<?php

    class Stats {

        public $table = 'keepa_stats';
        public $pdo;

        public $fields = array('asin', 'type', 'AMAZON', 'NEW', 'USED', 'SALES', 'LISTPRICE', 'COLLECTIBLE', 'REFURBISHED', 'NEW_FBM_SHIPPING', 'LIGHTNING_DEAL', 'WAREHOUSE', 'NEW_FBA', 'COUNT_NEW', 'COUNT_USED', 'COUNT_REFURBISHED', 'COUNT_COLLECTIBLE', 'EXTRA_INFO_UPDATES', 'RATING', 'COUNT_REVIEWS', 'BUY_BOX_SHIPPING', 'USED_NEW_SHIPPING', 'USED_VERY_GOOD_SHIPPING', 'USED_GOOD_SHIPPING', 'USED_ACCEPTABLE_SHIPPING', 'COLLECTIBLE_NEW_SHIPPING', 'COLLECTIBLE_VERY_GOOD_SHIPPING', 'COLLECTIBLE_GOOD_SHIPPING', 'COLLECTIBLE_ACCEPTABLE_SHIPPING', 'REFURBISHED_SHIPPING');

        public $types = array('current','avg','avg30','avg90','outOfStockPercentageInInterval');
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

        public function prepare($stats, $asin) {
            // check is stats is object or array?
            $oCSVType = new ReflectionClass('Keepa\helper\CSVType');
            $kepaTypes = $oCSVType->getConstants();
            $kepaTypes = array_flip($kepaTypes);

            foreach ($stats as $key => $value) {
                if(in_array($key, $this->types)){
                    $result['asin'] = $asin;
                    $result['type'] = $key;
                    foreach ($value as $k => $v) {
                        $result[$kepaTypes[$k]] = $v;
                    }
                    $results[] = $result;
                }
            }

            return $results;
        }

        function addAll($stats, $asin) {
            $stats = $this->prepare($stats, $asin);

            $this->pdo->beginTransaction();
            $insert_values = array();
            foreach($stats as $val){
                $question_marks[] = '('. implode(',', array_fill(0, 30, '?')) .')';
                $insert_values = array_merge($insert_values, array_values($val));
            }
            $sql = "INSERT INTO ". $this->table ." (" . implode(",", $this->fields ) . ") VALUES " . implode(',', $question_marks);

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($insert_values);
            $this->pdo->commit();
            return true;
        }
    }
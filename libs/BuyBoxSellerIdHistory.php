<?php

    class BuyBoxSellerIdHistory {

        public $table = 'keepa_buyBoxSellerIdHistory';
        public $pdo;

        public $fields = array('asin','time', 'sellerId');

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

        public function addAll($history, $asin) {

            $this->pdo->beginTransaction(); // also helps speed up your inserts.
            $insert_values = array();
            $k = 2;
            foreach($history as $val){
                if($k%2 == 0) {
                    $question_marks[] = '(?, ?, ?)';
                    $tmp = array($asin, date('Y-m-d H:i:s', keepTimeToUnux($val)));
                } else {
                    $tmp[] = $val;
                    $insert_values = array_merge($insert_values, $tmp);
                }
                $k++;
            }
            $sql = "INSERT INTO ". $this->table ." (" . implode(",", $this->fields ) . ") VALUES " . implode(',', $question_marks);
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($insert_values);
            $this->pdo->commit();
            return true;
        }

        public function updateAll($history, $asin) {
            $this->remove($asin);
            $this->addAll($history, $asin);
            return true;
        }

    }
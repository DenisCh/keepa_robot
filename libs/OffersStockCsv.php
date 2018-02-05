<?php
    class OffersStockCsv {

        public $table = 'keepa_offers_stockCsv';
        public $pdo;

        public $fields = array('offer_id', 'time', 'stock');
        public $fieldsDescr = array('offer_id'=>'i', 'time'=>'d', 'stock'=>'i');

        public $belongTo = array(
            'offers'
        );

        public function __construct() {
            $this->pdo = App::$pdo;
        }

        public function addAll($stockCsv, $offer_id) {

            $this->pdo->beginTransaction(); // also helps speed up your inserts.
            $insert_values = array();
            $k = 2;
            foreach($stockCsv as $val){
                if($k%2 == 0) {
                    $question_marks[] = '(?, ?, ?)';
                    $tmp = array($offer_id, date('Y-m-d H:i:s', keepTimeToUnux($val)));
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
    }
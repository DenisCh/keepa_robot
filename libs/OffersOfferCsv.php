<?php
    class OffersOfferCsv {

        public $table = 'keepa_offers_offerCsv';
        public $pdo;

        public $fields = array('offer_id', 'time', 'price', 'shipping_cost');
        public $fieldsDescr = array('offer_id'=>'i', 'time'=>'d', 'price'=>'i', 'shipping_cost'=>'i');

        public $belongTo = array(
            'offers'
        );

        public function __construct() {
            $this->pdo = App::$pdo;
        }

        public function addAll($offerCsv, $offer_id) {
            $this->pdo->beginTransaction(); // also helps speed up your inserts.
            $insert_values = array();
            $k = 3;
            foreach($offerCsv as $val){
                if($k%3 == 0) {
                    $question_marks[] = '(?, ?, ?, ?)';
                    $tmp = array($offer_id, date('Y-m-d H:i:s', keepTimeToUnux($val)));
                } else {
                    $tmp[] = $val;
                }

                if($k%3 == 2) {
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
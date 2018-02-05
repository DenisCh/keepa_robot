<?php
    class Csv {

        public $table = 'keepa_csv';
        public $pdo;

        public $fields = array('asin', 'type', 'time', 'value');
        public $fieldsDescr = array('asin'=>'i', 'type'=>'i', 'time'=>'d', 'value'=>'i');

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

        public function addAll($prices, $type, $asin) {

            // Here is looks like bug in API, for REFURBISHED prices array have an even count of element last element always 0
            if(count($prices)%2 != 0) {
                array_pop($prices);
            }

            $this->pdo->beginTransaction(); // also helps speed up your inserts.
            $insert_values = array();
            $k = 2;
            foreach($prices as $val){
                if($k%2 == 0) {
                    $question_marks[] = '(?, ?, ?, ?)';
                    $tmp = array($asin, $type, date('Y-m-d H:i:s', keepTimeToUnux($val)));
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
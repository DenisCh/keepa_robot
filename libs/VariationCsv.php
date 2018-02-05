<?php

    class VariationCsv {

        public $table = 'keepa_variationCsv';
        public $pdo;

        public $fields = array('asin','variation');

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

        public function addAll($variations, $asin) {
            $variations = explode(',', $variations);

            $this->pdo->beginTransaction(); // also helps speed up your inserts.
            $insert_values = array();
            foreach($variations as $variation){
                $question_marks[] = '(?, ?)';
                $insert_values = array_merge($insert_values, array($variation, $asin));
            }

            $sql = "INSERT INTO ". $this->table ." (" . implode(",", $this->fields ) . ") VALUES " . implode(',', $question_marks);

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($insert_values);
            $this->pdo->commit();
            return true;
        }

        public function updateAll($variations, $asin) {
            $this->remove($asin);
            $this->addAll($variations, $asin);
            return true;
        }

    }
<?php

    class ImageCsv {

        public $table = 'keepa_imagesCsv';
        public $pdo;

        public $fields = array('asin','image');

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

        public function addAll($images, $asin) {
            $images = explode(',', $images);

            $this->pdo->beginTransaction(); // also helps speed up your inserts.
            $insert_values = array();
            foreach($images as $img){
                $question_marks[] = '(?, ?)';
                $insert_values = array_merge($insert_values, array($asin, $img));
            }

            $sql = "INSERT INTO ". $this->table ." (" . implode(",", $this->fields ) . ") VALUES " . implode(',', $question_marks);

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($insert_values);
            $this->pdo->commit();
            return true;
        }

        public function updateAll($images, $asin) {
            $this->remove($asin);
            $this->addAll($images, $asin);
            return true;
        }

    }
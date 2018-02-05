<?php

    class StatsMinMax {

        public $table = 'keepa_stats_min_max';
        public $pdo;

        public $fields = array('asin', 'type', 'typem', 'time', 'value');

        public $types = array('min', 'max', 'minInInterval', 'maxInInterval', 'stockPerCondition3rdFBA','stockPerConditionFBM');

        public $belongTo = array(
            'keepa_product'
        );

        public function __construct() {
            $this->pdo = App::$pdo;
        }

        public function remove($asin, $types) {
            $sql = 'DELETE FROM '. $this->table .' WHERE asin=?';
            if(isset($types['stockPerConditionFBM']) && !$types['stockPerConditionFBM']) {
                $sql .= ' AND `type` != "stockPerConditionFBM"';
            }
            if(isset($types['stockPerCondition3rdFBA']) && !$types['stockPerCondition3rdFBA']) {
                $sql .= ' AND `type` != "stockPerCondition3rdFBA"';
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(1, $asin);
            $stmt->execute();
            return true;
        }

        public function prepare($stats, $asin) {
            // check is stats is object or array?
            $oCSVType = new ReflectionClass('Keepa\helper\CSVType');
            $kepaTypes = $oCSVType->getConstants();
            $kepaTypes = array_flip($kepaTypes);

            $spcTypes = $this->stockPerConditionIndex();

            foreach ($stats as $key => $value) {
                if(in_array($key, $this->types) && is_array($value)){
                    $result['asin'] = $asin;
                    $result['type'] = $key;
                    foreach ($value as $k => $v) {
                        if(is_array($v)) {
                            $result['typem'] = $kepaTypes[$k];
                            $result['time'] = date('Y-m-d H:i:s', keepTimeToUnux($v[0]));
                            $result['value'] = $v[1];
                            $results[] = $result;
                        } else if ($key == 'stockPerConditionFBM' || $key == 'stockPerCondition3rdFBA'){
                            $result['typem'] = $spcTypes[$k];
                            $result['time'] = null;
                            $result['value'] = $v;
                            $results[] = $result;
                        }
                    }
                }
            }


            return $results;
        }


        function addAll($stats, $asin) {
            $stats = $this->prepare($stats, $asin);

            $this->pdo->beginTransaction();
            $insert_values = array();
            foreach($stats as $val){
                $question_marks[] = '(?, ?, ?, ?, ?)';
                $insert_values = array_merge($insert_values, array_values($val));
            }
            $sql = "INSERT INTO ". $this->table ." (" . implode(",", $this->fields ) . ") VALUES " . implode(',', $question_marks);

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($insert_values);
            $this->pdo->commit();
            return true;
        }

        function stockPerConditionIndex() {
            $spcIndexes = array(
                0 => 'Unknown condition',
                1 => 'New',
                2 => 'Used - Like New',
                3 => 'Used - Very Good',
                4 => 'Used - Good',
                5 => 'Used - Acceptable',
                6 => 'Refurbished',
                7 => 'Collectible - Like New',
                8 => 'Collectible - Very Good',
                9 => 'Collectible - Good',
                10 => 'Collectible - Acceptable'
            );
            return $spcIndexes;
        }
    }
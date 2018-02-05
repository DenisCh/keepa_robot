<?php
    class App
    {

        public static $pdo;

        public function __construct(PDO $pdo)
        {
            self::$pdo = $pdo;
        }

        public function __get($name) {
            if(class_exists($name)) {
                return new $name;
            } else {
                return null;
            }

        }

        public function __call($name, $arguments) {
            return false;
        }

    }
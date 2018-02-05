<?php

if(!function_exists('pr')) {
    function pr($var) {
        echo '<pre>';
        print_r($var);
        echo '</pre>';
    }
}


function writeCache($content, $filename){
        $fp = fopen($filename, 'w');
        fwrite($fp, $content);
        fclose($fp);
}

function readCache($filename, $expiry){
    if (file_exists($filename)) {
        if ((time() - $expiry) > filemtime($filename))
            return FALSE;
        $cache = file($filename);
        return implode('', $cache);
    }
    return FALSE;
}

function keepTimeToUnux($time) {
    $time = ($time + 21564000)*60;
    return $time;
}


function pdoSet($allowed, &$values, $source = array()) {
    $set = '';
    $values = array();
    if (!$source) $source = &$_POST;
    foreach ($allowed as $field) {
        if (isset($source[$field])) {
            $set.="`".str_replace("`","``",$field)."`". "=:$field, ";
            $values[$field] = $source[$field];
        }
    }
    return substr($set, 0, -2);
}

function placeholders($text, $count=0, $separator=","){
    $result = array();
    if($count > 0){
        for($x=0; $x<$count; $x++){
            $result[] = $text;
        }
    }

    return implode($separator, $result);
}

function productSQL($product) {
    $product_array = json_decode(json_encode($product), true);
    $nn = array_map('toJson', $product_array);
    $productFields = productFields();
    foreach ($productFields as $key => $value) {
        if(isset($nn[$key])) {
            if($value == 'i')
                $result[$key] = (int)$nn[$key];
            else
                $result[$key] = $nn[$key];
        } else {
            if($value == 'i')
                $result[$key] = 0;
            else
                $result[$key] = '';
        }
    }
    return $result;
}

function toJson($val) {
    if(is_array($val) || is_object($val)) {
        return json_encode($val);
    } else {
        return $val;
    }
}
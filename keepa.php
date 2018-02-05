<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(0);

include 'config.php';
include 'libs/functions.php';
include 'libs/Logger.php';
include 'libs/App.php';
include 'libs/Product.php';
include 'libs/ProductCategory.php';
include 'libs/ImageCsv.php';
include 'libs/VariationCsv.php';
include 'libs/BuyBoxSellerIdHistory.php';

include 'libs/Stats.php';
include 'libs/StatsMinMax.php';
include 'libs/StatsOther.php';

include 'libs/Offers.php';
include 'libs/OffersOfferCsv.php';
include 'libs/OffersStockCsv.php';

include 'libs/Csv.php';

include 'vendor/autoload.php';

echo '<pre>';
Logger::$PATH = dirname(__FILE__) .'/logs';
$loger = Logger::getLogger(date('Y-m-d'));
$loger->log('========================== Start Robot ==========================');


// Db Connection
$dsn = "mysql:host=". HOST .";dbname=". DATABASE .";charset=utf8";
$opt = array(
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
);

try {
    $pdo = new PDO($dsn, USER, PASSWORD, $opt);
    $app = new App($pdo);
} catch (PDOException $e) {
    $loger->log('Can\'t connect to DB: '. $e->getMessage());
    die(); //We need stop screep, have no sense to do API request
}

$limit = 1; //Here is small issue in Keepa API if one of ASIN's not correct we receive Exception and can't get other ASIN's so bette to send requests only with one ASIN.
$offset = (isset($_GET['offset'])?(int)$_GET['offset'] : 0);

// Get ASIN's from DB
$stmt = $pdo->prepare('SELECT `site_sku` FROM `keepa_asin` LIMIT :offset, :limit');
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$asins = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Prepare request to Keepa
use Keepa\API\Request;
use Keepa\API\ResponseStatus;
use Keepa\helper\CSVType as CSVType;
use Keepa\helper\CSVTypeWrapper;
use Keepa\helper\KeepaTime;
use Keepa\helper\ProductAnalyzer;
use Keepa\helper\ProductType;
use Keepa\KeepaAPI;
use Keepa\objects\AmazonLocale;
$api = new KeepaAPI($_config['keepa_api_key']);
$r = Request::getProductRequest($_config['domainId'], $_config['offers'], $_config['stats_start'], $_config['stats_end'], $_config['update'], $_config['history'], $asins);  //$asins ['B017HRQ6KQ'] ['B00NMVU2RA'] ['B019FWCENO','B00LABN12W']

$request_url = 'https://api.keepa.com/product?key='.$_config['keepa_api_key'] .'&'. $r->query();

$loger->log('Request URL: https://api.keepa.com/product?key='.$_config['keepa_api_key'] .'&'. $r->query());

// Try get response
try {
    // Only for debug, to cache request result
    /*if($response = readCache('tmp/'. md5($request_url), 400000000000)) {
        $response = json_decode($response);
    } else {
        $response = $api->sendRequestWithRetry($r);
        writeCache(json_encode($response), 'tmp/'. md5($request_url));
    }*/

    $response = $api->sendRequestWithRetry($r);

    switch ($response->status) {
        case ResponseStatus::OK:

            foreach ($response->products as $product){
                try {
                    if($app->Product->exists($product->asin)) {
                        $loger->log('Update product: '. $product->asin);
                        $app->Product->update($product);
                    } else {
                        $loger->log('Add product: '. $product->asin);
                        $app->Product->add($product);
                    }

                    // Update Images ++
                    if($product->imagesCSV) {
                        $app->ImageCsv->updateAll($product->imagesCSV, $product->asin);
                    }

                    // Add Categories ++
                    if($product->categories) {
                        $app->ProductCategory->updateAll($product->categories, $product->asin);
                    }

                    // Add Asin Variations ++
                    if($product->variationCSV) {
                        $app->VariationCsv->updateAll($product->variationCSV, $product->asin);
                    }

                    // Add buyBoxSellerIdHistory ++
                    if($product->buyBoxSellerIdHistory) {
                        $app->BuyBoxSellerIdHistory->updateAll($product->buyBoxSellerIdHistory, $product->asin);
                    }

                    // Add offers ++
                    if($product->offers) {
                        $app->Offers->removeByProduct($product->asin);
                        foreach ($product->offers as $offer) {
                            $offer_id = $app->Offers->add($offer, $product->asin);
                            if($offer->offerCSV) {
                                $app->OffersOfferCsv->addAll($offer->offerCSV, $offer_id);
                            }
                            if($offer->stockCSV) {
                                $app->OffersStockCsv->addAll($offer->stockCSV, $offer_id);
                            }
                        }
                    }

                    // Add csv ++
                    if($product->csv) {
                        // Prices history keys from 0(AMAZON) to 10(NEW_FBA)
                        // Remove exists product price history
                        $app->Csv->remove($product->asin);
                        $oCSVType = new ReflectionClass('Keepa\helper\CSVType');
                        $priceTypes = $oCSVType->getConstants();
                        $priceTypes = array_flip($priceTypes);

                        foreach ($product->csv as $key => $value) {
                            if(is_array($value)) {
                                $app->Csv->addAll($value, $priceTypes[$key], $product->asin);
                            }
                        }
                    }

                    // Add stats ++
                    if($product->stats) {
                        $app->Stats->remove($product->asin);
                        $app->Stats->addAll($product->stats, $product->asin);

                        $safety = array('stockPerConditionFBM'=>true, 'stockPerCondition3rdFBA'=>true);
                        if($product->stats->stockPerConditionFBM === null) {
                            $loger->log('Warning: Keepa API return null for stockPerConditionFBM');
                            $safety['stockPerConditionFBM'] = false;

                        }
                        if($product->stats->stockPerCondition3rdFBA === null) {
                            $loger->log('Warning: Keepa API return null for stockPerCondition3rdFBA');
                            $safety['stockPerCondition3rdFBA'] = false;
                        }

                        $app->StatsMinMax->remove($product->asin, $safety);
                        $app->StatsMinMax->addAll($product->stats, $product->asin);

                        $app->StatsOther->remove($product->asin);
                        $app->StatsOther->add($product->stats, $product->asin);
                    }

                    $loger->log('SUCCESS: Product #'. $product->asin);
                } catch(PDOException $e) {
                    $loger->log('PDO: '. $e->getMessage());
                    $loger->log('PDO: '. $e->getFile());
                }

            }
            break;
        default:
            $loger->log('Response Status: '. $response->status);
            $loger->log('Error Msg: '. $response->error->message);
            $loger->logPrint(json_decode($response->error->details));
    }

} catch(Exception $e) {
    $loger->log('Exception: '. $e->getMessage());
    $loger->log('Exception: '. $e->getFile());
}

<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

define('BASE_URL',   'https://www.amazon.TLD/gp/aw/d/');
define('CACHE_PATH', __DIR__ . '/cache/');
define('CACHE_TIME', 3600 * 3);

$url = NULL;

if (isset($_GET['id'])) {
    $id_pattern = '/[A-Z0-9]+/';

    if (preg_match($id_pattern, $_GET['id'])) {
        $url = BASE_URL . $_GET['id'];
    }
} else if (isset($_GET['url'])) {
    $url_pattern       = '/https:\/\/(www\.)?amazon\.([A-Za-z]{2,3})\/[A-Za-z0-9\-]+\/(dp|product)\/([A-Z0-9]+)\/([A-Za-z0-9\?\/=%_&\-]+)?/';
    $short_url_pattern = '/https:\/\/(www\.)?amzn.to\/[A-Za-z0-9]+/';

    if (preg_match($url_pattern, $_GET['url'], $preg_groups)) {
        $url = str_replace('.TLD', '.' . $preg_groups[2], BASE_URL) . $preg_groups[4];
    } else {
        if (preg_match($short_url_pattern, $_GET['url'])) {
            $url = $_GET['url'];
        }
    }
}

use simplehtmldom\HtmlWeb;
if ($url !== NULL) {
    if (file_exists(CACHE_PATH . md5($url))
     && filemtime(CACHE_PATH . md5($url)) > time() - CACHE_TIME) {
        $print_value = file_get_contents(CACHE_PATH . md5($url));
    } else {
        include_once 'HtmlWeb.php';

        $client = new HtmlWeb();
        $html = $client->load($url);

        $price = $html->find('#price_inside_buybox', 0)->plaintext;
        if ($price !== null) {
            $price_pattern = '/([0-9\.,]+).*/';
            
            if (preg_match($price_pattern, $price, $price_group)) {
                $price = floatval(str_replace(',', '.', $price_group[1]));

                $print_value = json_encode([
                    'success' => true,
                    'price'   => $price,
                    'img'     => $html->find('#landingImage', 0)->getAttribute('src')
                ]);
            } else {
                $print_value = json_encode([
                    'success' => false,
                    'err'     => 'no_price_found'
                ]);
            }
        } else {
            $print_value = json_encode([
                'success' => false,
                'err'     => 'no_price_found'
            ]);
        }

        try {
            file_put_contents(CACHE_PATH . md5($url), $print_value, LOCK_EX);
        } catch (\Exception $e) {
            // suppress possible LOCKING error
        }
    }
} else {
    $print_value = json_encode([
        'success' => false,
        'err'     => 'invalid_parameter'
    ]);
}

echo $print_value;
<?php

require_once "vendor/autoload.php";
use Symfony\Component\HttpFoundation\JsonResponse;

$app = new Silex\Application;
$app['debug'] = true;

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));

if (preg_match("/\/public\/.+/", $_SERVER['REQUEST_URI'])) { // If contains route to file in public directory
    if (is_file(ltrim($_SERVER['REQUEST_URI'], '/'))) { // If route is file
        return false; // Silex returns false = gives back hand to webserver
    }
}

$app->get("/exercicesKana", function() use ($app) {
    return $app['twig']->render('exercicesKana.html.twig');
});

$app->get("/exercices/{kana}/{times}", function($kana, $times) use ($app) {
    switch($kana) {
        case "hiragana" : $file = file_get_contents("resources/hiragana.csv"); break;
        case "katakana" : $file = file_get_contents("resources/katakana.csv"); break;
        default : // both
        $file = file_get_contents("resources/hiragana.csv");
        $file .= file_get_contents("resources/katakana.csv");
        break;
    }

    $file = rtrim($file, "\n"); // Remove trailing newline

    $list = explode("\n", $file);
    shuffle($list);
        
    if (!preg_match("/^\d+/", $times) || $times < 1) {
        $times = 10;
    }
        
    if ($times < count($list)) {
        $list = array_slice($list, 0, $times);
    }
        
    $response = array();
        
    foreach ($list as $pair) {
        list($k, $v) = explode(",", $pair);
        $response[$k] = $v;
    }

    return new JsonResponse($response);
        
})->value("kana", "both", "times", 10);

$app->run();

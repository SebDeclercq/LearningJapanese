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

$app->get("/", function() use ($app) {
   $html =  "<div><a href='/exercisesKana'>Training : kana to romaji</a><br/>";
   $html .= "<a href='/exercisesRomaji'>Training : romaji to kana</a></div>";
   return $html;
});

$app->get("/exercisesKana", function() use ($app) {
    return $app['twig']->render('exercisesKana.html.twig');
});

$app->get("/exercisesRomaji", function() use ($app) {
    return $app['twig']->render('exercisesRomaji.html.twig');
});

$app->get("/exercises/{kana}/{times}", function($kana, $times) use ($app) {
    switch($kana) {
        case "hiragana" :
        case "hiragana2romaji" :
        $file = file_get_contents("resources/hiragana.csv"); break;
        case "katakana" :
        case "katakana2romaji" :
        $file = file_get_contents("resources/katakana.csv"); break;
        case "romaji2hiragana" :
        case "romaji2katakana" :
        $file = file_get_contents("resources/romaji.csv"); break;
        case "hiraganaAndKatakana2romaji" :
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
        
    $results = array();

    if (!preg_match("/^romaji/", $kana)) {
        foreach ($list as $pair) {
            list($k, $v) = explode(",", $pair);
            $results[$k] = $v;
        }
    }
    else {
        foreach ($list as $triplet) {
            list($romaji, $hiragana, $katakana) = explode(",", $triplet);
            switch ($kana) {
                case "romaji2hiragana" : $results[$romaji] = $hiragana; break;
                case "romaji2katakana" : $results[$romaji] = $katakana; break;
            }
        }
    }
    
    $response = new JsonResponse($results);
    $response->headers->set('Content-Type', 'application/json; charset=utf-8');
    return $response;

})->value("kana", "both", "times", 10);

$app->get("/syllabary/{syllabary}", function($syllabary) {
    $file = file_get_contents("resources/romaji.csv");
    $file = rtrim($file, "\n"); // Remove trailing newline
    $list = explode("\n", $file);
    shuffle($list);
    
    $results = array();
    foreach ($list as $triplet) {
        list($romaji, $hiragana, $katakana) = explode(",", $triplet);
        if ($syllabary == "hiragana") {
            array_push($results, $hiragana);
        }
        else {
            array_push($results, $katakana);
        }
    }

    $response = new JsonResponse($results);
    $response->headers->set('Content-Type', 'application/json; charset=utf-8');
    return $response;    
    
});

$app->run();

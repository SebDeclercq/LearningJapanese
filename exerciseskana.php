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
    $file = file_get_contents("resources/fullKataList.csv");
    $file = rtrim($file, "\n"); // Remove trailing newline
    $list = explode("\n", $file);

    if (preg_match("/^romaji/", $kana)) { // By convenience, exercises romaji => XX are based only on simple kana characters
        $list = array_splice($list, 0, 46);
    }   

    $tmpResults = $results = array();
    
    foreach ($list as $triplet) {
        list($romaji, $hiragana, $katakana) = explode(",", $triplet);
        switch($kana) {
            case "hiragana" : case "hiragana2romaji" :
                $tmpResults[$hiragana] = $romaji; break;
            case "katakana" : case "katakana2romaji" :
                $tmpResults[$katakana] = $romaji; break;
            case "romaji2hiragana" :
                $tmpResults[$romaji] = $hiragana; break;
            case "romaji2katakana" :
                $tmpResults[$romaji] = $katakana; break;
            case "hiraganaAndKatakana2romaji" :  default : // both
                $tmpResults[$hiragana] = $romaji;
                $tmpResults[$katakana] = $romaji;
                break;
        }
    }

    $keys = array_keys($tmpResults);
    shuffle($keys);
    foreach ($keys as $key) {
        $results[$key] = $tmpResults[$key]; // Shuffle associative array
    }

    if (!preg_match("/^\d+/", $times) || $times < 1) {
        $times = 10;
    }
    if ($times < count($results)) {
        $results = array_slice($results, 0, $times);
    }

    $response = new JsonResponse($results);
    $response->headers->set('Content-Type', 'application/json; charset=utf-8');
    return $response;

})->value("kana", "both", "times", 10);

$app->get("/syllabary/{syllabary}", function($syllabary) {
    $file = file_get_contents("resources/romaji.csv");
    $file = rtrim($file, "\n"); // Remove trailing newline
    $list = explode("\n", $file);
    $list = array_splice($list, 0, 46);
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

$app->get("/tmp", function() {
    $hiragana = file_get_contents("resources/hiragana.csv");
    $katakana = file_get_contents("resources/katakana.csv");
    
    $hiragana = rtrim($hiragana, "\n");
    $katakana = rtrim($katakana, "\n");
    
    $hiraganaList = $katakanaList = $fullList = array();
    
    foreach (explode("\n", $hiragana) as $pair) {
        list($hir, $syl) = explode(",", $pair);
        $fullList[$syl] = $syl.','.$hir;
    }
    foreach (explode("\n", $katakana) as $pair) {
        list($kat, $syl) = explode(",", $pair);
        $fullList[$syl] .= ','.$kat;
    }
    
    file_put_contents("resources/fullKataList.csv", implode("\n", $fullList));
});

$app->run();

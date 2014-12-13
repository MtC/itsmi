<?php

namespace MtC;

use \PDO as PDO;
use \JWT as JWT;

class Itsmi extends \Slim\Middleware
{
    public function call()
    {
        //The Slim application
        $app = $this->app;

        //The Environment object
        $env = $app->environment;

        //The Request object
        $req = $app->request;
        
        
$key = "example_key";
$token = array(
    "iss" => "http://example.org",
    "aud" => "http://example.com",
    "iat" => 1356999524,
    "nbf" => 1357000000
);

$jwt = JWT::encode($token, $key);
        
        echo $jwt;
$decoded = JWT::decode('eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6MTMzNywidXNlcm5hbWUiOiJqb2huLmRvZSJ9.qUVZ7aZVCAPBM11uXO0EW1nAQ7hSdgreURUqyy45cL8', 'My very confidential secret!!!');

print_r($decoded);
        
        //print_r($req);

        //The Response object
        $res = $app->response;
        
        $this->next->call();
    }
}
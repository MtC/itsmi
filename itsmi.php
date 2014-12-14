<?php

namespace MtC;

use \PDO as PDO;
use \JWT as JWT;

class Itsmi extends \Slim\Middleware {

    function __construct() {
        $this->params = $this->validateJSON(file_get_contents($_SERVER['DOCUMENT_ROOT'].'/vendor/mtc/itsmi/settings.json'));
        if (!$this->isDatabase()) {
            $this->createDatabase();
        }
        if (!$this->isTable()) {
            $this->createTable();
        }
    }

    public function call() {
        $app = $this->app;
        $env = $app->environment;
        $req = $app->request;
        $res = $app->response;

        // before database check
        $test_email = "mjkoks@gmail.com";
        $test_token = "abcdefgh";

        $body = $req->getBody();

        $this->params = $this->validateJSON($body);

        $test_header = $req->headers('X-Request-Login');
        $test_jwt    = $req->headers('X-Jwt');

        $test_path   = $env['PATH_INFO'];
        $test_method = $env['REQUEST_METHOD'];

        $this->privateKey = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/privkey.pem');
        $this->publicKey  = str_replace(array("\r\n", "\n", "\r"), "", file_get_contents($_SERVER['DOCUMENT_ROOT'].'/pubkey.pub'));

        if ($test_path == '/login' && $test_method == 'GET') {
            $res->header('X-Login-Token', $this->publicKey);
        } else if ($test_path == '/login' && $test_method == 'POST' && $this->decrypt($test_header, $this->privateKey)) {
            $login = $this->validateLoginValues($this->decrypt($test_header, $this->privateKey));
            if ($login) {
                $res->header('X-Jwt', $this->createJWT());
            }
        } else if ($test_jwt) {
            echo "check";
        } else {
            echo "no check";
        }

        $this->next->call();
    }

    protected function isDatabase () {
        $_con  = new PDO(
            "mysql:host={$this->params->host};dbname=INFORMATION_SCHEMA",
                $this->params->userName,
                $this->params->password);
        $_sql  = "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = :itsmi";
        $_stmt = $_con->prepare($_sql);
        $_itsmi= $this->params->dbname;
        $_stmt->bindParam(':itsmi',$_itsmi,PDO::PARAM_STR);
        $_stmt->execute();
        $_exists = $_stmt->fetchAll(PDO::FETCH_ASSOC);
        $_con    = null;
        return count($_exists) ? true : false;
    }

    protected function createDatabase () {
        $_con  = new PDO(
            "mysql:host={$this->params->host}",
               $this->params->userName,
               $this->params->password
        );
        $_stmt = $_con->prepare("CREATE DATABASE `{$this->params->dbname}` COLLATE 'utf8_general_ci'");
        $_stmt->execute();
        $_con  = null;
    }

    protected function isTable () {
        $_con  = new PDO(
            "mysql:host={$this->params->host};dbname={$this->params->dbname}",
               $this->params->userName,
               $this->params->password
        );
        $_sql  = "SHOW TABLES LIKE '{$this->params->table}'";
        $_stmt = $_con->query($_sql);
        return ($_stmt && $_stmt->rowCount() > 0) ? true : false;
    }

    protected function createTable() {
        $_con  = new PDO(
            "mysql:host={$this->params->host};dbname={$this->params->dbname}",
               $this->params->userName,
               $this->params->password
        );
        $_sql  = "  SET NAMES utf8;
                    CREATE TABLE `{$this->params->table}` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `name` varchar(20) DEFAULT NULL,
                      `email` varchar(100) NOT NULL,
                      `requested` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                      `registered` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                      `status` set('requested','rerequested','registered') NOT NULL DEFAULT 'requested',
                      `role` set('user','teacher','admin') NOT NULL DEFAULT 'user',
                      `attempts` tinyint(1) NOT NULL DEFAULT '0',
                      PRIMARY KEY (`id`),
                      UNIQUE KEY `email` (`email`),
                      UNIQUE KEY `name` (`name`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        $_stmt = $_con->prepare($_sql);
        $_stmt->execute();
        $_con  = null;
    }

    protected function validateJSON($json, $assoc_array = FALSE) {
        // decode the JSON data
        $result = json_decode($json, $assoc_array);

        // switch and check possible JSON errors
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                $error = ''; // JSON is valid
                break;
            case JSON_ERROR_DEPTH:
                $error = 'Maximum stack depth exceeded.';
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $error = 'Underflow or the modes mismatch.';
                break;
            case JSON_ERROR_CTRL_CHAR:
                $error = 'Unexpected control character found.';
                break;
            case JSON_ERROR_SYNTAX:
                $error = 'Syntax error, malformed JSON.';
                break;
            // only PHP 5.3+
            case JSON_ERROR_UTF8:
                $error = 'Malformed UTF-8 characters, possibly incorrectly encoded.';
                break;
            default:
                $error = 'Unknown JSON error occured.';
                break;
        }

        if($error !== '') {
            // throw the Exception or exit
            // exit($error);
            // do something with error
            return new \stdClass;
        }

        // everything is OK
        return $result;
    }

    protected function encrypt($data, $pubkey) {
        $_pubkey = openssl_get_publickey($pubkey);
        openssl_public_encrypt($data, $encrypted, $_pubkey);
        return base64_encode($encrypted);
    }

    protected function decrypt($data, $privkey) {
        $_privkey = openssl_get_privatekey($privkey);
        if (openssl_private_decrypt(base64_decode($data), $decrypted, $_privkey)) {
            $data = $decrypted;
        } else {
            openssl_private_decrypt(base64_decode($data), $decrypted, $_privkey);
            $data = false;
        }
        return $data;
    }

    protected function validateLoginValues ($oLoginValues) {
        $oValues = $this->validateJSON($oLoginValues);
        if (isset($oValues->email) && isset($oValues->token) && isset($oValues->iat)) {
            // more to come
            return true;
        }
        return false;
    }

    protected function createJWT () {
        $secret = "zomaar onzin";
        $token = array(
            "iss" => "http://nt2lab.nl",
            "iat" => \time(),
            "jti" => 1
        );
        return JWT::encode($token, $secret);
    }
}
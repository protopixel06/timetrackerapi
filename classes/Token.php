<?php

/**
 * File for manipulating with tokens.
 * 
 * This file contains functions for creating, checking, deleting and getting tokens.
 * 
 * PHP version 7.4.27
 * 
 * LICENSE: This source file is subject to version 3 of the GNU GPL license
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/licenses/gpl-3.0.html.
 * 
 * @category   Core
 * @author     Protopixel <protopixel06@gmail.com>
 * @license    MIT License
 * @version    prerelease-1.0
 * @since      File available since 1.0-pre1
 */

require_once "config.php";
class Token
{
    public static function create($id)
    {
        $token = rand(10000000000000000, 99999999999999999);
        DB::query("INSERT INTO sessions (uid, token, logged_at) VALUES (:uid, :token, :time)", array(':uid' => $id, ':token' => $token, ':time' => time()));
        return $token;
    }

    public static function check($token)
    {
        $at = DB::count("SELECT * FROM sessions WHERE token=:token", array(':token' => $token)) ? DB::query("SELECT * FROM sessions WHERE token=:token", array(':token' => $token))[0]['logged_at'] : 1;
        $now = time();
        $exp = $at + DB::query("SELECT * FROM config")[0]['token_exp'];
        if ($now >= $exp) {
            self::delete($token);
            return false;
        } else {
            return DB::query("SELECT * FROM sessions WHERE token=:token", array(':token' => $token))[0]['uid'];
        }
    }

    public static function getFromHeaders()
    {
        $headers = null;

        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } else if (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            //print_r($requestHeaders);
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }

            // HEADER: Get the access token from the header
            if (!empty($headers) && preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
            return null;
        }
    }

    public static function getUserId($token)
    {
        return DB::query("SELECT * FROM sessions WHERE token=:token", array(':token' => $token))[0]['uid'];
    }

    public static function getUsername($token)
    {
        return DB::query("SELECT * FROM users WHERE id=:uid", array(':uid' => self::getUserId($token)));
    }

    public static function delete($token)
    {
        DB::query("DELETE FROM sessions WHERE token=:token", array(':token' => $token));
    }
}

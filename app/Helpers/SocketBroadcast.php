<?php
namespace App\Helpers;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;


class SocketBroadcast
{
     protected static $channel = null;

     protected static $username = null;
     protected static $data = [];

     public static function channel($channel)
     {
          self::$channel = $channel;
          return new static();
     }

     public static function destination($username = [])
     {
          self::$username = $username;
          return new static();
     }


     public static function dispatch($data = [])
     {
          $username = self::$username;
          $channel = self::$channel;
          
          Bus::chain([
               fn() => self::sendRequest(
                    username: $username,
                    channel: $channel,
                    data: $data
               )
          ])->dispatch();
     }

     /**
      *  SocketBroadcast::channel('testing')
      *  ->destination(['99c02fd2-7094-4064-a85f-e73bdf1cb50b'])
      *  ->send([
      *      'nama' => 'sample',
      *      'email' => 'oke@mail.com'
      *  ]);
      */

     public static function send($data = [])
     {
          return self::sendRequest(
               username: self::$username,
               channel: self::$channel,
               data: $data
          );
     }

     public static function sendRequest($username, $channel, $data)
     {
          try{
               if ($channel && $username) {
                    $url = config('services.socket_broadcast.url') . "/send";
                    $token = config('services.socket_broadcast.token');
                    $result = Http::withToken($token)
                         ->withHeaders([
                              "username" => json_encode($username)
                         ])
                         ->post($url, [
                              'channel' => $channel,
                              'data' => $data
                         ]);
                    return $result;
               }
          }catch(\Exception $e){}
     }
}
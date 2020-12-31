<?php

namespace App\Http\Controllers;

use xPaw\MinecraftPing;
use xPaw\MinecraftPingException;
use xPaw\MinecraftQuery;
use xPaw\MinecraftQueryException;

use GuzzleHttp\Client;

use DB;

class MinecraftController extends Controller
{
    function index()
    {
        return ['eyyo' => 'this is index'];
    }

    function syncTelegram()
    {
        DB::table('last_sync')->where('id', 2)->update([
            'at' => date('Y-m-d H:i:s'),
        ]);

        $minecraftDatas = DB::table('active_user')->where('id_last_sync', 1)->get();
        $telegramDatas = DB::table('active_user')->where('id_last_sync', 2)->get();

        $computedMinecraftDatas = [];
        foreach ($minecraftDatas as $key => $value) {
            $computedMinecraftDatas[] = $value->username;
        }

        $computedTelegramDatas = [];
        foreach ($telegramDatas as $key => $value) {
            $computedTelegramDatas[] = $value->username;
        }

        $telegramDatasLeft = DB::table('active_user')->where('id_last_sync', 2)->whereNotIn('username', $computedMinecraftDatas)->get();
        $telegramDatasJoin = DB::table('active_user')->where('id_last_sync', 1)->whereNotIn('username', $computedTelegramDatas)->get();

        $computedTelegramDatasLeft = [];
        foreach ($telegramDatasLeft as $key => $value) {
            $computedTelegramDatasLeft[] = $value->username;
        }

        $computedTelegramDatasJoin = [];
        foreach ($telegramDatasJoin as $key => $value) {
            $computedTelegramDatasJoin[] = $value->username;
        }

        $newPlayers = [];
        foreach($computedMinecraftDatas as $player) {
            $newPlayers[] = [
                'id_last_sync' => 2,
                'username' => $player,
            ];
        }

        DB::table('active_user')->where('id_last_sync', 2)->delete();
        DB::table('active_user')->insert($newPlayers);

        $string = '';
        
        $string .= 'Server Time: ';
        $string .= date('d-m-Y H:i:s');
        $string .= "\n\n";

        if (count($computedTelegramDatasJoin) > 0) {
            $string .= implode(', ', $computedTelegramDatasJoin);
            $string .= ' join the game.';
            $string .= "\n";
        }

        if (count($computedTelegramDatasLeft) > 0) {
            $string .= implode(', ', $computedTelegramDatasLeft);
            $string .= ' left the game.';
            $string .= "\n";
        }

        $string .= "\n" . 'Active Users' . "\n";
        $string .= '====================' . "\n";
        
        foreach ($minecraftDatas as $key => $value) {
            $string .= $value->username;
            $string .= "\n";   
        }

        $client = new Client([
            // Base URI is used with relative requests
            // 'base_uri' => 'http://httpbin.org',
            // You can set any number of default request options.
            'timeout'  => 2.0,
        ]);

        if (
            count($computedTelegramDatasLeft) == 0
            && count($computedTelegramDatasJoin) == 0
            ) {
                return response()->json(['nothingToDo' => 'stillTheSame'], 200);
            } else {
                $response = $client->request('POST', 'https://api.telegram.org/bot' . env('TELEGRAM_API', 'fill') . '/sendMessage', [
                    'form_params' => [
                        'chat_id' => env('TELEGRAM_CHAT_ID', 'fill'),
                        'text' => $string,
                    ]
                ]);
            }
    }

    function syncMinecraft()
    {
        $Query = new MinecraftQuery();

        try
        {
            $Query->Connect(env('MINECRAFT_HOST', '127.0.0.1'), env('MINECRAFT_PORT', '25565'));
            $players = $Query->GetPlayers();
        }
        catch( \Exception $e )
        {
            return response()->json(['msg' => $e->getMessage()], 500);
        }

        DB::table('last_sync')->where('id', 1)->update([
            'at' => date('Y-m-d H:i:s'),
        ]);

        DB::table('active_user')->where('id_last_sync', 1)->delete();

        $newPlayers = [];
        if ($players != false) {
            foreach($players as $player) {
                $newPlayers[] = [
                    'id_last_sync' => 1,
                    'username' => $player,
                ];
            }
        }

        DB::table('active_user')->insert($newPlayers);
    }

    function getPlayersData()
    {
        $Query = new MinecraftQuery();

        try
        {
            $Query->Connect(env('MINECRAFT_HOST', '127.0.0.1'), env('MINECRAFT_PORT', '25565'));
            $players = $Query->GetPlayers();
        }
        catch( \Exception $e )
        {
            return response()->json(['msg' => $e->getMessage()], 500);
        }

        return response()->json($players, 200);
    }

    function getDataDump()
    {   
        try
        {
            $Query = new MinecraftPing(env('MINECRAFT_HOST', '127.0.0.1'), env('MINECRAFT_PORT', '25565'));
            $query = $Query->Query();
        }
        catch( Exception $e )
        {
            echo $e->getMessage();
        }
        finally
        {
            if( $Query )
            {
                $Query->Close();
            }
        }
        
        $Query = new MinecraftQuery();
        
        try
        {
            $Query->Connect(env('MINECRAFT_HOST', '127.0.0.1'), env('MINECRAFT_PORT', '25565'));
            $info = $Query->GetInfo();
            $players = $Query->GetPlayers();
        }
        catch( MinecraftQueryException $e )
        {
            echo $e->getMessage();
        }

        dd(compact([
            'query',
            'info',
            'players',
        ]));
    }
}

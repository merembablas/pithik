<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Telegram\Bot\Api;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class HookController extends Controller
{

    public function index(Request $request)
    {
        $telegram = new Api(config('telegram.bots.the_pithik_bot.token'));
        $update = $telegram->getWebhookUpdate();

        if (!isset($update['message'])) {
            return response()->json([
                'status' => 'success'
            ]);
        }

        $command = $this->_getCommand($update);
        switch ($command['type']) {
            case "command":
                $this->_sendMessage($telegram, [
                    'chat_id' => $command['chat_id'],
                    'message' => 'Aku rapopo'
                ]);
                break;
            case "text":
                $this->_sendMessage($telegram, [
                    'chat_id' => $command['chat_id'],
                    'message' => $this->_shout()
                ]);
                break;
        }

        return response()->json([
            'status' => 'success'
        ]);
    }

    private function _getCommand($update) {
        $msgs = explode(' ', $update->message['text']);
        $pos = strpos($msgs[0], '/');
        $type = 'text';
    
        if ($pos === false) {
            $content = $msgs[0];
        } else {
            $type = 'command';
            $content = substr($msgs[0], 1);
        }
    
        array_shift($msgs);

        return [
            'type' => $type,
            'chat_id' => $update->message['from']['id'],
            'content' => $content,
            'parameters' => $msgs
        ];
    }

    private function _getBots() {
        $bots = DB::table('bots')->where('status', 'active')->get();
    }

    private function _sendMessage($telegram, $data) {
        $response = $telegram->sendMessage([
            'chat_id' => $data['chat_id'],
            'parse_mode' => 'MarkdownV2',
            'text' => $this->_filter($data['message'])
        ]);
    }
    private function _filter($str) {
        return str_replace('|', '\|', 
            str_replace('-', '\-', 
            str_replace('+', '\+',
            str_replace('.', '\.',
            str_replace('(', '\(',
            str_replace(')', '\)', $str
        ))))));
    }

    private function _shout() {
        $shouts = [
            "\xF0\x9F\x90\x94 Kukuruyuuuk..",
            "\xF0\x9F\x90\x94 \xF0\x9F\x90\x94 Petok.. petok..",
            "\xF0\x9F\x90\x94 Kurrr kurrr..",
            "\xF0\x9F\x90\x94 \xF0\x9F\x90\x94 Kuk kuk ruyuuuk cuuuk..",
            "\xF0\x9F\x90\x94 Kaing",
            "Cit cit.. \xF0\x9F\x90\x94",
            "\xF0\x9F\x90\x94 KOKOOOK PETHOOOOK..",
            "\xF0\x9F\x90\x94 Kikuk.. kikuk..",
            "\xF0\x9F\x90\x94 Meowwng..",
            "\xF0\x9F\x90\x94 Grooot..",
            "\xF0\x9F\x90\x94 Tokk tooook.."
        ];

        $key = array_rand($shouts, 1);

        return $shouts[$key];
    }
}

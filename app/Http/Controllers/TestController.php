<?php

namespace App\Http\Controllers;

use RestCord\DiscordClient;

class TestController extends Controller
{
    public function index()
    {
        try {
            $botToken = env('DISCORD_API', 'fill');
            $channelId = env('DISCORD_CHANNEL_ID', 'fill');

            $discord = new DiscordClient(['token' => $botToken]); // Token is required

            $res = $discord->channel->createMessage(['channel.id' => (int) $channelId, 'content' => 'Foo Bar Baz']);
        } catch(\Exception $e) {
            return response()->json(['msg' => $e->getMessage()], 500);
        }
    }

    function connectWebsocket()
    {
        ?>
        <!DOCTYPE html>
            <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <title>Gateway Connector</title>
                </head>
                <body>
                    <h1>Use this to connect to the gateway.</h1>
                    <p>
                        Once you put in your token, and click connect, it will connect, and then let you know when you can close the
                        browser. After that, you should be good to go.
                    </p>
                    <form id="form">
                        <input type="text" name="token" placeholder="token" id="token"/>
                        <input type="submit" value="Connect"/>
                    </form>

                    <script type="application/javascript">
                        var element = document.getElementById('form');
                        element.addEventListener('submit', function(event) {
                            event.preventDefault();
                            submitForm();

                            return false;
                        });

                        function submitForm() {
                            var TOKEN = document.getElementById('token').value;
                            var ws    = new WebSocket("wss://gateway.discord.gg/?encoding=json&v=6");

                            ws.onopen    = function() {return console.log("OPEN!")};
                            ws.onerror   = console.error.bind(console);
                            ws.onclose   = console.error.bind(console);
                            ws.onmessage = function(a) {
                                try {
                                    var b = JSON.parse(a.data);
                                    if (0 === b.op) {
                                        return;
                                    }

                                    if (b.op === 10) {
                                        ws.send(JSON.stringify({
                                            op: 2,
                                            d:  {
                                                token:      TOKEN,
                                                properties: {$browser: "Restcord Gateway Connect"}
                                            }
                                        }));
                                        alert('You can close this tab/window now.');
                                        ws.close();
                                    }
                                } catch (a) {console.error(a)}
                            };
                        }
                    </script>
                </body>
            </html>
        <?php
    }
}
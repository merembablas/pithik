<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Your Telegram Bots
    |--------------------------------------------------------------------------
    | You may use multiple bots at once using the manager class. Each bot
    | that you own should be configured here.
    |
    | Here are each of the telegram bots config parameters.
    |
    | Supported Params:
    |
    | - name: The *personal* name you would like to refer to your bot as.
    |
    |       - username: Your Telegram Bot's Username.
    |                       Example: (string) 'BotFather'.
    |
    |       - token:    Your Telegram Bot's Access Token.
                        Refer for more details: https://core.telegram.org/bots#botfather
    |                   Example: (string) '123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11'.
    |
    |       - commands: (Optional) Commands to register for this bot,
    |                   Supported Values: "Command Group Name", "Shared Command Name", "Full Path to Class".
    |                   Default: Registers Global Commands.
    |                   Example: (array) [
    |                       'admin', // Command Group Name.
    |                       'status', // Shared Command Name.
    |                       Acme\Project\Commands\BotFather\HelloCommand::class,
    |                       Acme\Project\Commands\BotFather\ByeCommand::class,
    |             ]
    */
    'bots'                         => [
        'the_pithik_bot' => [
            'username'            => 'the_pithik_bot',
            'token'               => env('TELEGRAM_THE_PITHIK_BOT_TOKEN', 'YOUR-BOT-TOKEN'),
            'certificate_path'    => env('TELEGRAM_CERTIFICATE_PATH', 'YOUR-CERTIFICATE-PATH'),
            'webhook_url'         => env('TELEGRAM_WEBHOOK_URL', 'YOUR-BOT-WEBHOOK-URL'),
            'commands'            => [
                //Acme\Project\Commands\MyTelegramBot\BotCommand::class
            ],
        ],

        'btcidr' => [
            'username'            => 'btcidr_iddx_bot',
            'token'               => env('TELEGRAM_BOT_BTCIDR_TOKEN', 'YOUR-BOT-TOKEN'),
        ],

        'ethidr' => [
            'username'  => 'ethidr_iddx_bot',
            'token' => env('TELEGRAM_BOT_ETHIDR_TOKEN', 'YOUR-BOT-TOKEN'),
        ],

        'bnbidr' => [
            'username'  => 'bnbidr_iddx_bot',
            'token' => env('TELEGRAM_BOT_BNBIDR_TOKEN', 'YOUR-BOT-TOKEN'),
        ],

        'xrpidr' => [
            'username'  => 'xrpidr_iddx_bot',
            'token' => env('TELEGRAM_BOT_XRPIDR_TOKEN', 'YOUR-BOT-TOKEN'),
        ],

        'stridr' => [
            'username'  => 'xlmidr_iddx_bot',
            'token' => env('TELEGRAM_BOT_XLMIDR_TOKEN', 'YOUR-BOT-TOKEN'),
        ],

        'adaidr' => [
            'username'  => 'adaidr_iddx_bot',
            'token' => env('TELEGRAM_BOT_ADAIDR_TOKEN', 'YOUR-BOT-TOKEN'),
        ],

        'ltcidr' => [
            'username'  => 'ltcidr_iddx_bot',
            'token' => env('TELEGRAM_BOT_LTCIDR_TOKEN', 'YOUR-BOT-TOKEN'),
        ],

        'linkidr' => [
            'username'  => 'linkidr_iddx_bot',
            'token' => env('TELEGRAM_BOT_LINKIDR_TOKEN', 'YOUR-BOT-TOKEN'),
        ],

        'uniidr' => [
            'username'  => 'uniidr_iddx_bot',
            'token' => env('TELEGRAM_BOT_UNIIDR_TOKEN', 'YOUR-BOT-TOKEN'),
        ],

        'dotidr' => [
            'username'  => 'dotidr_iddx_bot',
            'token' => env('TELEGRAM_BOT_DOTIDR_TOKEN', 'YOUR-BOT-TOKEN'),
        ],

        'atomidr' => [
            'username'  => 'atomidr_iddx_bot',
            'token' => env('TELEGRAM_BOT_ATOMIDR_TOKEN', 'YOUR-BOT-TOKEN'),
        ],

        'bchabcidr' => [
            'username'  => 'bchidr_iddx_bot',
            'token' => env('TELEGRAM_BOT_BCHIDR_TOKEN', 'YOUR-BOT-TOKEN'),
        ],

        'wavesidr' => [
            'username'  => 'wavesidr_iddx_bot',
            'token' => env('TELEGRAM_BOT_WAVESIDR_TOKEN', 'YOUR-BOT-TOKEN'),
        ],

        'thetaidr' => [
            'username'  => 'thetaidr_iddx_bot',
            'token' => env('TELEGRAM_BOT_THETAIDR_TOKEN', 'YOUR-BOT-TOKEN'),
        ],

        'lendidr' => [
            'username'  => 'lendidr_iddx_bot',
            'token' => env('TELEGRAM_BOT_AAVEIDR_TOKEN', 'YOUR-BOT-TOKEN'),
        ],

        'trxidr' => [
            'username'  => 'trxidr_iddx_bot',
            'token' => env('TELEGRAM_BOT_TRXIDR_TOKEN', 'YOUR-BOT-TOKEN'),
        ],

        'tradeplan' => [
            'username'  => 'tradeplan_iddx_bot',
            'token' => env('TELEGRAM_BOT_TRADEPLAN_TOKEN', 'YOUR-BOT-TOKEN'),
        ],

        'dogeidr' => [
            'username'  => 'dogeidr_iddx_bot',
            'token' => env('TELEGRAM_BOT_DOGEIDR_TOKEN', 'YOUR-BOT-TOKEN'),
        ],

        'bttidr' => [
            'username'  => 'bttidr_iddx_bot',
            'token' => env('TELEGRAM_BOT_BTTIDR_TOKEN', 'YOUR-BOT-TOKEN'),
        ],

        'attidr' => [
            'username'  => 'attidr_iddx_bot',
            'token' => env('TELEGRAM_BOT_ATTIDR_TOKEN', 'YOUR-BOT-TOKEN'),
        ],

        'maticidr' => [
            'username'  => 'maticidr_iddx_bot',
            'token' => env('TELEGRAM_BOT_MATICIDR_TOKEN', 'YOUR-BOT-TOKEN'),
        ],

        'zilidr' => [
            'username'  => 'zilidr_iddx_bot',
            'token' => env('TELEGRAM_BOT_ZILIDR_TOKEN', 'YOUR-BOT-TOKEN'),
        ],

        'aoaidr' => [
            'username'  => 'aoaidr_iddx_bot',
            'token' => env('TELEGRAM_BOT_AOAIDR_TOKEN', 'YOUR-BOT-TOKEN'),
        ],

        'vidyxidr' => [
            'username'  => 'vidyxidr_iddx_bot',
            'token' => env('TELEGRAM_BOT_VIDYXIDR_TOKEN', 'YOUR-BOT-TOKEN'),
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Bot Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the bots you wish to use as
    | your default bot for regular use.
    |
    */
    'default'                      => 'mybot',

    /*
    |--------------------------------------------------------------------------
    | Asynchronous Requests [Optional]
    |--------------------------------------------------------------------------
    |
    | When set to True, All the requests would be made non-blocking (Async).
    |
    | Default: false
    | Possible Values: (Boolean) "true" OR "false"
    |
    */
    'async_requests'               => env('TELEGRAM_ASYNC_REQUESTS', false),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Handler [Optional]
    |--------------------------------------------------------------------------
    |
    | If you'd like to use a custom HTTP Client Handler.
    | Should be an instance of \Telegram\Bot\HttpClients\HttpClientInterface
    |
    | Default: GuzzlePHP
    |
    */
    'http_client_handler'          => null,

    /*
    |--------------------------------------------------------------------------
    | Resolve Injected Dependencies in commands [Optional]
    |--------------------------------------------------------------------------
    |
    | Using Laravel's IoC container, we can easily type hint dependencies in
    | our command's constructor and have them automatically resolved for us.
    |
    | Default: true
    | Possible Values: (Boolean) "true" OR "false"
    |
    */
    'resolve_command_dependencies' => true,

    /*
    |--------------------------------------------------------------------------
    | Register Telegram Global Commands [Optional]
    |--------------------------------------------------------------------------
    |
    | If you'd like to use the SDK's built in command handler system,
    | You can register all the global commands here.
    |
    | Global commands will apply to all the bots in system and are always active.
    |
    | The command class should extend the \Telegram\Bot\Commands\Command class.
    |
    | Default: The SDK registers, a help command which when a user sends /help
    | will respond with a list of available commands and description.
    |
    */
    'commands'                     => [
        Telegram\Bot\Commands\HelpCommand::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Command Groups [Optional]
    |--------------------------------------------------------------------------
    |
    | You can organize a set of commands into groups which can later,
    | be re-used across all your bots.
    |
    | You can create 4 types of groups:
    | 1. Group using full path to command classes.
    | 2. Group using shared commands: Provide the key name of the shared command
    | and the system will automatically resolve to the appropriate command.
    | 3. Group using other groups of commands: You can create a group which uses other
    | groups of commands to bundle them into one group.
    | 4. You can create a group with a combination of 1, 2 and 3 all together in one group.
    |
    | Examples shown below are by the group type for you to understand each of them.
    */
    'command_groups'               => [
        /* // Group Type: 1
           'commmon' => [
                Acme\Project\Commands\TodoCommand::class,
                Acme\Project\Commands\TaskCommand::class,
           ],
        */

        /* // Group Type: 2
           'subscription' => [
                'start', // Shared Command Name.
                'stop', // Shared Command Name.
           ],
        */

        /* // Group Type: 3
            'auth' => [
                Acme\Project\Commands\LoginCommand::class,
                Acme\Project\Commands\SomeCommand::class,
            ],

            'stats' => [
                Acme\Project\Commands\UserStatsCommand::class,
                Acme\Project\Commands\SubscriberStatsCommand::class,
                Acme\Project\Commands\ReportsCommand::class,
            ],

            'admin' => [
                'auth', // Command Group Name.
                'stats' // Command Group Name.
            ],
        */

        /* // Group Type: 4
           'myBot' => [
                'admin', // Command Group Name.
                'subscription', // Command Group Name.
                'status', // Shared Command Name.
                'Acme\Project\Commands\BotCommand' // Full Path to Command Class.
           ],
        */
    ],

    /*
    |--------------------------------------------------------------------------
    | Shared Commands [Optional]
    |--------------------------------------------------------------------------
    |
    | Shared commands let you register commands that can be shared between,
    | one or more bots across the project.
    |
    | This will help you prevent from having to register same set of commands,
    | for each bot over and over again and make it easier to maintain them.
    |
    | Shared commands are not active by default, You need to use the key name to register them,
    | individually in a group of commands or in bot commands.
    | Think of this as a central storage, to register, reuse and maintain them across all bots.
    |
    */
    'shared_commands'              => [
        // 'start' => Acme\Project\Commands\StartCommand::class,
        // 'stop' => Acme\Project\Commands\StopCommand::class,
        // 'status' => Acme\Project\Commands\StatusCommand::class,
    ],
];

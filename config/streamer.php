<?php


return [

    /*
    |--------------------------------------------------------------------------
    | Application handlers
    |--------------------------------------------------------------------------
    |
    | Handlers classes that should be invoked with Streamer listen command
    | based on streamer.event.name => [local_handlers] pairs
    |
    | Local handlers should implement MessageReceiver contract
    |
    */
    // domain represents the owner of the event, should be unique by th
    'domain' => env('APP_NAME', ''),
    'listen_and_fire' => [
//        \Domain\Example\Entity\User::EVENT_NAME => [
//            \App\Listeners\Streams\ExampleListener::class,
//        ],
//        \Domain\Example\Events\User::EVENT_NAME => [
//            \App\Listeners\Streams\ExampleUserActionListener::class,
//        ],
    ],
];

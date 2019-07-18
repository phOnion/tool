<?php
use Onion\Framework\Http\Events\RequestEvent;
use Onion\Framework\Server\Listeners\CryptoListener;
use Psr\EventDispatcher\ListenerProviderInterface;

return [
    'events' => [
        'listeners' => [
            [
                'event' => RequestEvent::class,
                'handlers' => [
                    CryptoListener::class,
                    'request_dispatcher',
                ]
            ]
        ],
        'providers' => [
            ListenerProviderInterface::class,
        ],
    ],
];

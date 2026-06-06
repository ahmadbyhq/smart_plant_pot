<?php

function sendTelegramMessage($message)
{
    $token = ":TOKEN_BOT";
    $chatId = "ID_CHAT";

    $url = "https://api.telegram.org/bot{$token}/sendMessage";

    $data = [
        "chat_id" => $chatId,
        "text" => $message
    ];

    $options = [
        "http" => [
            "header"  => "Content-Type: application/x-www-form-urlencoded\r\n",
            "method"  => "POST",
            "content" => http_build_query($data)
        ]
    ];

    $context = stream_context_create($options);

    return file_get_contents(
        $url,
        false,
        $context
    );
}

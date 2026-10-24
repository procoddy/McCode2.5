<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Core\Response;

final class HtmlResponse extends Response
{
    public function __construct(string $content = '', int $status = 200, array $headers = [])
    {
        parent::__construct($content, $status, ['Content-Type' => 'text/html; charset=UTF-8', ...$headers]);
    }
}

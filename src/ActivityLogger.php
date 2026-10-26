<?php

namespace Lab;

final class ActivityLogger
{
    private string $filePath;

    public function __construct(?string $filePath = null)
    {
        $this->filePath = $filePath ?? __DIR__ . '/../logs/events.jsonl';
    }

    public function record(string $event, array $data = []): void
    {
        $payload = array_merge([
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'event'     => $event,
        ], $data);

        file_put_contents(
            $this->filePath,
            json_encode($payload, JSON_UNESCAPED_SLASHES) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }
}
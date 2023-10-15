<?php
namespace Flynn314\Matrix;

use Flynn314\Matrix\Exception\MatrixClientException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Client\ClientInterface;

readonly class MatrixClient
{
    public function __construct(
        private string $baseUrl,
        private string $token,
        private ClientInterface $httpClient
    ) {}

    /**
     * @throws MatrixClientException
     */
    public function messagePost(string $roomId, string $message, string $messageFormatted = null, ?string $threadId = null): string
    {
        return $this->postRoomMessage($roomId, $message, $messageFormatted, $threadId);
    }

    /**
     * @throws MatrixClientException
     */
    public function emote(string $roomId, string $message, string $messageFormatted = null, ?string $threadId = null): string
    {
        return $this->postRoomMessage($roomId, $message, $messageFormatted, $threadId, ['msgtype' => 'm.emote']);
    }

    /**
     * @throws MatrixClientException
     */
    public function notice(string $roomId, string $message, string $messageFormatted = null, ?string $threadId = null): string
    {
        return $this->postRoomMessage($roomId, $message, $messageFormatted, $threadId, ['msgtype' => 'm.notice']);
    }

    /**
     * @throws MatrixClientException
     */
    public function filePost(string $roomId, string $file, ?string $threadId = null): string
    {
        $url = $this->fileUpload($file);

        $mime = mime_content_type($file);

        $data = [];
        if (strstr($mime, 'image')) {
            $data['msgtype'] = 'm.image';
        } elseif(strstr($mime, 'audio')) {
            // https://spec.matrix.org/latest/client-server-api/#fallback-for-mimage-mvideo-maudio-and-mfile
            $data['msgtype'] = 'm.audio';
        } else {
            $data['msgtype'] = 'm.file';
        }
        // todo implement m.location

        $data['url'] = $url;
        $data['info'] = [
            'mimetype' => $mime,
            'size' => filesize($file),
//            'w' => 512,
//            'h' => 512,
        ];

        return $this->postRoomMessage($roomId, basename($file), '', $threadId, $data);
    }

    /**
     * @throws MatrixClientException
     */
    public function fileUpload(string $file): string
    {
        $filename = basename($file);
        $fileData = file_get_contents($file);

        $data = $this->request('post', 'upload?filename='.$filename, [
            'binary' => $fileData,
        ], [
            'Content-Type' => mime_content_type($file),
        ]);
        if (!isset($data['content_uri']) || !$data['content_uri']) {
            throw new MatrixClientException('File upload error');
        }

        return $data['content_uri'];
    }

    /**
     * @throws MatrixClientException
     */
    public function deletePost(string $roomId, string $eventId): string
    {
        // curl -X POST "https://matrix.org/_matrix/client/r0/rooms/{roomId}/redact/{eventId}/{txnId}" -H "Authorization: Bearer YOUR_ACCESS_TOKEN" -H "Content-Type: application/json" --data '{
        $uri = sprintf('rooms/%s/redact/%s', $roomId, $eventId);

        $data = $this->request('post', $uri, ['reason' => 'Feature test']);
        if (!isset($data['event_id']) || !$data['event_id']) {
            throw new MatrixClientException('Unable to post message');
        }

        return $data['event_id'];
    }

    /**
     * @param string      $roomId
     * @param string      $body [message]
     * @param string|null $formattedBody
     * @param null|string $threadId
     * @param array       $data
     * @return string
     * @throws MatrixClientException
     */
    private function postRoomMessage(string $roomId, string $body, string $formattedBody = null, ?string $threadId = null, array $data = []): string
    {
        $data['msgtype'] = $data['msgtype'] ?? 'm.text';
        $data['body'] = $body;
        if ($formattedBody) {
            $data['format'] = 'org.matrix.custom.html';
            $data['formatted_body'] = $formattedBody;
        }
        if ($threadId) {
            $data['m.relates_to'] = [
                'rel_type' => 'm.thread',
                'event_id' => $threadId,
                'is_falling_back' => true,
                'm.in_reply_to' => [
                    'event_id' => $threadId,
                ],
            ];
        }

        $uri = sprintf('rooms/%s/send/m.room.message', $roomId);

        $data = $this->request('post', $uri, $data);
        if (!isset($data['event_id']) || !$data['event_id']) {
            throw new MatrixClientException('Unable to post message');
        }

        return $data['event_id'];
    }

    /**
     * @throws MatrixClientException
     */
    private function request(string $method, string $uri, array $data = [], array $header = []): array
    {
        if (isset($data['binary'])) {
            $uri = sprintf('%s/_matrix/media/v3/%s', $this->baseUrl, $uri);
        } else {
            $uri = sprintf('%s/_matrix/client/v3/%s', $this->baseUrl, $uri);
        }


        $header['Authorization'] = 'Bearer ' . $this->token;
        if (!isset($header['Content-Type'])) {
            $header['Content-Type'] = 'application/json';
        }
        $options = [
            'headers' => $header,
        ];

        if (isset($data['binary'])) {
            $options['body'] = $data['binary'];
            unset($data['binary']);
        } else {
            $options['json'] = $data;
        }

        try {
            // todo PSR-7
            $response = $this->httpClient->request($method, $uri, $options);
            $content = $response->getBody()->getContents();

            return json_decode($content, true);
        } catch (GuzzleException $e) {
            throw new MatrixClientException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }
}

<?php
declare(strict_types=1);

namespace Flynn314\Matrix;

use Flynn314\Matrix\Entity\File;
use Flynn314\Matrix\Entity\Message;
use Flynn314\Matrix\Entity\RelatesTo;
use Flynn314\Matrix\Entity\Sender;
use Flynn314\Matrix\Entity\Thumbnail;
use Flynn314\Matrix\Exception\MatrixClientException;
use Flynn314\Matrix\Service\VideoService;
use Flynn314\Matrix\ValueObject\MsgType;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Client\ClientInterface;

readonly class MatrixClient
{
    public function __construct(
        private string $baseUrl,
        private string $token,
        private ClientInterface $httpClient,
        private string|null $selfUserId = null,
    ) {}

    /**
     * @deprecated Not deprected, just to get your attention! Experemental method. Incomplete.
     * @param string $roomId
     * @param int $limit
     * @return array|Message[]
     */
    public function getMessages(string $roomId, int $limit = 10): array
    {
        $data = $this->request('get', sprintf('rooms/%s/messages?dir=b&limit=%d', $roomId, $limit));
        $events = [];
        foreach ($data['chunk'] as $raw) {
            if (!isset($raw['content']['msgtype'])) {
                continue;
            }
            if ('m.text' === $raw['content']['msgtype']) {
                $events[] = new Message(new Sender($raw['sender'], $raw['user_id']), $raw['content']['body']);
            }
        }

        return $events;
    }

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
        return $this->postRoomMessage($roomId, $message, $messageFormatted, $threadId, ['msgtype' => MsgType::EMOTE]);
    }

    /**
     * @throws MatrixClientException
     */
    public function notice(string $roomId, string $message, string $messageFormatted = null, ?string $threadId = null): string
    {
        return $this->postRoomMessage($roomId, $message, $messageFormatted, $threadId, ['msgtype' => MsgType::NOTICE]);
    }

    /**
     * @throws MatrixClientException
     */
    public function filePost(string $roomId, string $file, ?string $threadId = null): string
    {
        $data = [
            'info' => [
                'mimetype' => mime_content_type($file),
                'size' => filesize($file),
    //            'w' => 512,
    //            'h' => 512,
            ],
        ];

        if (str_contains($data['info']['mimetype'], 'image')) {
            $data['msgtype'] = MsgType::IMAGE;
        } elseif(str_contains($data['info']['mimetype'], 'audio')) {
            // https://spec.matrix.org/latest/client-server-api/#fallback-for-mimage-mvideo-maudio-and-mfile
            $data['msgtype'] = MsgType::AUDIO;
        } elseif(str_contains($data['info']['mimetype'], 'video')) {
            return $this->videoPost($roomId, $file, null, $threadId);
        } else {
            $data['msgtype'] = MsgType::FILE;
        }

        $data['url'] = $this->fileUpload($file);

        return $this->postRoomMessage($roomId, basename($file), '', $threadId, $data);
    }

    /**
     * @throws MatrixClientException
     */
    public function videoPost(string $roomId, string $file, ?string $thumb, ?string $threadId = null): string
    {
        $vs = new VideoService();
        $vd = $vs->getVideoDataByFilepath($file);

        $url = $this->fileUpload($file);

        $data = [
            'msgtype' => MsgType::VIDEO,
            'url' => $url,
            'w' => $vd->getWidth(),
            'h' => $vd->getHeight(),
            'info' => [
                'mimetype' => mime_content_type($file),
                'size' => filesize($file),
                'duration' => $vd->getDuration(),
                'xyz.amorgan.blurhash' => 'UIIXNu_NOZ^ltlxaxEe-01a0IUELS$MxRONG',
            ],
        ];
        if ($thumb) {
            $thumbUrl = $this->fileUpload($thumb);
            $data['info'] = array_merge($data['info'], (new Thumbnail($thumb, $thumbUrl))->toArray());
        }

        return $this->postRoomMessage($roomId, basename($file), '', $threadId, $data);
    }

    /**
     * @throws MatrixClientException
     */
    public function fileUpload(string $file): string
    {
        $filename = basename($file);
        $fileData = file_get_contents($file);

        return $this->binaryUpload($fileData, $filename, mime_content_type($file));
    }

    /**
     * @throws MatrixClientException
     */
    public function mediaPostByUri(
        string  $roomId,
        string  $messageType,
        File    $media,
        ?File   $thumb = null,
        ?string $threadId = null
    ): string {
        $data = [
            'msgtype' => (new MsgType($messageType))->getValue(),
            'url' => $media->getUri(),
            'info' => [
                'w' => $media->getWidth(),
                'h' => $media->getHeight(),
                'mimetype' => $media->getMime(),
                'size' => $media->getFileSize(),
                'duration' => $media->getDuration(),
            ],
        ];
        if ($media->getBlurHash()) {
            $data['info']['xyz.amorgan.blurhash'] = $media->getBlurHash();
        }

        if ($thumb) {
            $thumbData = [
                'thumbnail_info' => [
                    'mimetype' => $thumb->getMime(),
                    'w' => $thumb->getWidth(),
                    'h' => $thumb->getHeight(),
                    'size' => $thumb->getFileSize(),
                ],
                'thumbnail_url' => $thumb->getUri(),
            ];

            $data['info'] = array_merge($data['info'], $thumbData);
        }

        return $this->postRoomMessage($roomId, $media->getFileName(), '', $threadId, $data);
    }

    /**
     * @throws MatrixClientException
     */
    public function binaryUpload(string $fileData, string $fileName, string $mimeType): string
    {
        $data = $this->request('post', 'upload?filename='.$fileName, [
            'binary' => $fileData,
        ], [
            'Content-Type' => $mimeType,
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
     * @throws MatrixClientException
     */
    private function postRoomMessage(string $roomId, string $body, string $formattedBody = null, ?string $threadId = null, array $data = []): string
    {
        $data['msgtype'] = (new MsgType($data['msgtype'] ?? MsgType::TEXT))->getValue();
        $data['body'] = $body;
        if ($formattedBody) {
            $data['format'] = 'org.matrix.custom.html';
            $data['formatted_body'] = $formattedBody;
        }
        if ($threadId) {
            $data['m.relates_to'] = (new RelatesTo($threadId))->toArray();
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
    public function postLocation(string $roomId, float $lat, float $lon, string $locationName, ?string $threadId = null): string
    {
        $data = [
            'msgtype' => (new MsgType(MsgType::LOCATION))->getValue(),
            'geo_uri' => sprintf('geo:%.10f,%.10f', $lat, $lon),
            'body' => $locationName,
        ];
        // todo $data['info'] = (new Thumbnail())->toArray();

        if ($threadId) {
            $data['m.relates_to'] = (new RelatesTo($threadId))->toArray();
        }

        $uri = sprintf('rooms/%s/send/m.room.message', $roomId);
        $data = $this->request('post', $uri, $data);
        if (!isset($data['event_id']) || !$data['event_id']) {
            throw new MatrixClientException('Unable to post message');
        }

        return $data['event_id'];
    }

    public function typingIndicatorStart(string $roomId, int $timeOut = 120): void
    {
        if (!$this->selfUserId) {
            return;
        }
        if ($timeOut > 0) {
            $data = [
                'typing' => true,
                'timeout' => (int) ($timeOut . '000'),
            ];
        } else {
            $data = [
                'typing' => false,
            ];
        }

        $this->request('put', sprintf('rooms/%s/typing/%s', $roomId, $this->selfUserId), $data);
    }

    public function typingIndicatorStop(string $roomId): void
    {
        $this->typingIndicatorStart($roomId, 0);
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

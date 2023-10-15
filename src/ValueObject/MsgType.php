<?php
declare(strict_types=1);

namespace Flynn314\Matrix\ValueObject;

use Flynn314\ValueObject\AbstractValueObject;

final class MsgType extends AbstractValueObject
{
    public const AUDIO = 'm.audio';
    public const FILE = 'm.file';
    public const IMAGE = 'm.image';
    public const LOCATION = 'm.location';
    public const TEXT = 'm.text';
    public const VIDEO = 'm.video';

    public const EMOTE = 'm.emote';
    public const NOTICE = 'm.notice';

    protected function isValidValue($value): bool
    {
        return in_array($value, [
            self::AUDIO,
            self::EMOTE,
            self::FILE,
            self::IMAGE,
            self::LOCATION,
            self::NOTICE,
            self::TEXT,
            self::VIDEO,
        ]);
    }
}

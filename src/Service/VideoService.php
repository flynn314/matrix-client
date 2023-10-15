<?php
declare(strict_types=1);

namespace Flynn314\Matrix\Service;

use FFMpeg\FFProbe;
use Flynn314\Matrix\Entity\VideoData;

final class VideoService
{
    public function getVideoDataByFilepath(string $filepath): VideoData
    {
        $probe = FFProbe::create();
        $dimension = $probe
            ->streams($filepath) // extracts streams information
            ->videos() // filters video streams
            ->first() // returns the first video stream
            ->getDimensions();

        return new VideoData(
            $dimension->getWidth(),
            $dimension->getHeight(),
            intval($probe->format($filepath)->get('duration') * 1000)
        );
    }
}

<?php

namespace App\Repositories;

use App\Models\Video;
use App\Mapping\VideoMapping;
use Illuminate\Support\Str;

class VideoRepository
{
    public function create(array $data): Video
    {
        $data[VideoMapping::UUID] = Str::uuid();

        return Video::create($data);
    }
}

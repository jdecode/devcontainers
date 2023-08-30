<?php

namespace App\Enums;

enum ProfileImageUploadStatusEnum: string
{
    case PROCESSING = 'processing';
    case UPLOADED = 'uploaded';
    case FAILED = 'failed';
}

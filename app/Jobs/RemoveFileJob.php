<?php

namespace App\Jobs;

use App\Traits\ActivityLog;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Storage;

class RemoveFileJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use ActivityLog;

    public function __construct(private readonly string $filename)//
    {
    }

    public function handle(): void
    {
        if (Storage::disk('s3')->exists($this->filename)) {
            Storage::disk('s3')->delete($this->filename);
            return;
        }
        $this->activity(log: 'Cannot remove file', properties: ['message' => "file $this->filename does not exists"]);
    }
}

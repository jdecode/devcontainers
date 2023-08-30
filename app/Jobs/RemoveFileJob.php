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

    private string $disk;

    public function __construct(private readonly string $filename, private readonly bool $temp = false)
    {
        $this->disk = config('filesystems.default');
        if ($this->temp) {
            $this->disk = config('filesystems.default_temp');
        }
    }

    public function handle(): void
    {
        $tempPath = config('filesystems.temp_path');
        $path = $this->temp ? $tempPath . '/' . $this->filename : $this->filename;
        if (Storage::disk($this->disk)->exists($path)) {
            Storage::disk($this->disk)->delete($path);
            return;
        }
        $this->activity(log: 'Cannot remove file', properties: ['message' => "file $this->filename does not exists"]);
    }
}

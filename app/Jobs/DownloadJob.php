<?php

namespace App\Jobs;

use App\Models\DownloadExport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DownloadJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public $downloadId, public string $className, public string $method, public array $params = [])
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $donwload = DownloadExport::find($this->downloadId);

        if (!$donwload) {
            throw new \Exception("DownloadExport not found.");
        }

        $class = $this->className;
        $method = $this->method;

        try {
            if (class_exists($class) && method_exists($class, $method)) {
                $instance = new $class();
                $response = call_user_func_array([$instance, $method], $this->params);

                $donwload->update([
                    'status' => 'completed',
                    'finished_at' => now(),
                ]);

                if ($response) {
                    $donwload->update([
                        'path' => $response,
                    ]);
                }
            } else {
                $donwload->update([
                    'status' => 'failed',
                    'finished_at' => now(),
                    'error' => "Class or method does not exist.",
                ]);

                throw new \Exception("Class or method does not exist.");
            }
        } catch (\Exception $e) {
            $donwload->update([
                'status' => 'failed',
                'finished_at' => now(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}

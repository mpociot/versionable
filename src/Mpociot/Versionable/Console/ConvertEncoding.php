<?php

namespace Mpociot\Versionable\Console;

use Illuminate\Console\Command;
use Mpociot\Versionable\Version;

class ConvertEncoding extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'versionable:convert-encoding {--encoding=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert the encoding from JSON to serialize() or vice versa';

    protected $encodingCheck = [
        'serialize' => '{',
        'json' => 'a:',
    ];

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $encoding = $this->option('encoding') ?? config('versionable.encoding');
        $sourceEncoding = $encoding === 'json' ? 'serialize' : 'json';
        $versions = Version::query()->get();

        foreach ($versions as $version) {
            if (!$this->validateData($version, $sourceEncoding)) {
                $this->error("Data does not appear to be as encoded $sourceEncoding: {$version->model_data}");

                return;
            }

            $version->model_data = $encoding === 'serialize'
                ? serialize(json_decode($version->model_data, true))
                : json_encode(unserialize($version->model_data));

            $version->save();
        }

        $this->info("Converted {$versions->count()} models to '$encoding' encoding.");
    }

    protected function validateData(Version $version, $sourceEncoding)
    {
        if (strpos($version->model_data, $this->encodingCheck[$sourceEncoding]) === 0) {
            return false;
        }

        return true;
    }
}

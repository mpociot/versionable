<?php

namespace Mpociot\Versionable\Jobs;

use Adaptor\Core\Classes\ServiceExecutionData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Mpociot\Versionable\Version;
use ReflectionException;

class VersionableJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private int $id, private string $modelClass, private array $attributes, private array $originalAttributes)
    {
//        \Log::info('model', ['id' => $id, 'class' => $modelClass, 'attributes' => $this->attributes, 'original' => $this->originalAttributes]);
    }

    /**
     * @throws ReflectionException
     */
    public function handle()
    {
        $resourceReflection = new \ReflectionClass($this->modelClass);
        $staticResource = $resourceReflection->newInstanceWithoutConstructor();
        $model = $staticResource->find($this->id);

        foreach ($this->originalAttributes as $key => $value) {
            $model->setAttribute($key, $value);
        }

        $model->syncOriginal();

        foreach ($this->attributes as $key => $value) {
            $model->setAttribute($key, $value);
        }

        $model->versionableDirtyData = $model->getDirty();
        $model->updating             = $model->exists;

        Version::createVersionForModel($model);
    }
}

<?php

namespace Mpociot\Versionable\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Mpociot\Versionable\Version;
use ReflectionClass;
use ReflectionException;

class VersionableJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private int $id;
    private string $modelClass;
    private array $attributes;
    private array $originalAttributes;

    public function __construct(private Model $model, private string $reason)
    {
        $this->attributes = $this->model->getAttributes();
        $this->originalAttributes = $this->model->getOriginal();
        $this->id = $this->model->id;
        $this->modelClass = get_class($this->model);
    }

    /**
     * @throws ReflectionException
     */
    public function handle()
    {
        $resourceReflection = new ReflectionClass($this->modelClass);
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
        $model->updating = $model->exists;
        $model->reason = $this->reason;

        Version::createVersionForModel($model);
    }
}

<?php

namespace NahidFerdous\Shield\Support;

use Illuminate\Database\Eloquent\Model;

readonly class ShieldAuthContext
{
    public string $guard;

    public string $provider;

    public string $broker;

    public string $modelClass;

    public Model $model;

    public string $table;

    public function __construct()
    {
        $this->guard = requestGuardResolver();

        $this->provider = config("auth.guards.{$this->guard}.provider");
        if (! $this->provider) {
            throw new \InvalidArgumentException("Provider not defined for guard [{$this->guard}]");
        }

        $this->modelClass = resolveAuthenticatableClass($this->guard);
        $this->model = app($this->modelClass);
        $this->table = $this->model->getTable();

        // Broker resolution (multi-guard safe)
        $this->broker = config(
            "auth.providers.{$this->provider}.passwords",
            $this->provider
        );
    }
}

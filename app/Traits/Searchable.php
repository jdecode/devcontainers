<?php

namespace App\Traits;

use App\Observers\ElasticsearchObserver;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use ReflectionClass;

/**
 * @method getTable()
 * @method static observe(string $class)
 * @method getKey()
 */
trait Searchable
{
    public static function bootSearchable(): void
    {
        if (config('database.connections.elasticsearch.enabled')) {
            static::observe(ElasticsearchObserver::class);
        }
    }

    /**
     * @throws ServerResponseException
     * @throws ClientResponseException
     * @throws MissingParameterException
     */
    public function elasticsearchIndex(Client $elasticsearchClient): void
    {
        $elasticsearchClient->index([
            'index' => $this->getSearchIndex(),
            'id' => $this->getKey(),
            'body' => $this->searchableAttributes(),
        ]);
    }

    /**
     * @throws ClientResponseException
     * @throws ServerResponseException
     * @throws MissingParameterException
     */
    public function elasticsearchDelete(Client $elasticsearchClient): void
    {
        $elasticsearchClient->delete([
            'index' => $this->getSearchIndex(),
            'id' => $this->getKey(),
        ]);
    }

    public function getSearchIndex(): string
    {
        return config('app.env') . '_' . strtolower((new ReflectionClass($this))->getShortName());
    }

    abstract public function searchableAttributes(): array;
}

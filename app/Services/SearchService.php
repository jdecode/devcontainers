<?php

namespace App\Services;

use App\Exceptions\ElasticsearchNotEnabledException;
use App\Exceptions\TraitNotUsedException;
use App\Traits\Searchable;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use stdClass;

class ElasticsearchService
{
    private int $searchSize;

    private string $searchType;

    public const MATCH_ALL = 'match_all';

    public const MAX_SIZE = 10000;

    public const DEFAULT_SEARCH_TYPE = 'query_string';

    /**
     * @throws ElasticsearchNotEnabledException
     */
    public function __construct(private readonly Client $elasticsearch)
    {
        $elasticSearchConfig = config('database.connections.elasticsearch');
        if (!$elasticSearchConfig['enabled']) {
            throw new ElasticsearchNotEnabledException('Elastic search is not enabled');
        }
    }

    /**
     * Search method to use with Elasticsearch.
     * To use it, you need to add Searchable trait to specified Model
     * Fields can have suffix `^x` where `x` is weight, example: title^5
     * In above example title will be 5 time more important than other fields
     *
     * @throws ClientResponseException
     * @throws ServerResponseException
     * @throws TraitNotUsedException
     */
    public function search(string $model, ?string $query, array $fields = [], array $params = []): array
    {
        $index = $this->verifyModelsAndPrepareIndex([$model]);
        $this->setParams($params);
        return $this->searchOnElasticsearch($index, $fields, $query);
    }

    /**
     * Method allows to search across few models in all fields described in searchableAttributes model mdthod
     *
     * @throws TraitNotUsedException
     * @throws ServerResponseException
     * @throws ClientResponseException
     */
    public function searchMultiple(array $models, ?string $query, array $params = []): array
    {
        $index = $this->verifyModelsAndPrepareIndex($models);
        $this->setParams($params);
        return $this->searchOnElasticsearch($index, [], $query);
    }

    private function setParams(array $params): void
    {
        $this->searchSize = self::MAX_SIZE;
        $this->searchType = self::DEFAULT_SEARCH_TYPE;
        if (!$params) {
            return;
        }
        if (isset($params['size'])) {
            $this->searchSize = $params['size'];
        }
        if (isset($params['search_type'])) {
            $this->searchType = $params['search_type'];
        }
    }

    /**
     * @throws TraitNotUsedException
     */
    private function verifyModelsAndPrepareIndex(array $models): string
    {
        $index = [];
        foreach ($models as $model) {
            if (!in_array(Searchable::class, class_uses_recursive($model))) {
                throw new TraitNotUsedException("Searchable trait not used in $model");
            }
            $index[] = (new $model())->getSearchIndex();
        }

        return join(',', $index);
    }

    private function searchOnEloquent(array $models, array $fields, ?string $query)
    {

    }

    /**
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    private function searchOnElasticsearch(string $index, array $fields, ?string $query): array
    {
        $response = $this->elasticsearch->search([
            'index' => $index,
            'size' => $this->searchSize,
            'body' => [
                'query' => $this->query($fields, $query)
            ],
        ])->asArray();
        return [
            'total' => $response['hits']['total']['value'],
            'data' => $response['hits']['hits']
        ];
    }

    private function query(array $fields, ?string $query): array
    {
        if (!$query) {
            return [self::MATCH_ALL => new stdClass()];
        }
        $properties = ['query' => '*' . $query . '*'];
        if ($fields) {
            $properties['fields'] = $fields;
        }
        return [$this->searchType => $properties];
    }
}

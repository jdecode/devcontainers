<?php

namespace App\Services;

use App\Exceptions\NoSearchFieldException;
use App\Exceptions\TraitNotUsedException;
use App\Traits\Searchable;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use stdClass;

class SearchService
{
    private int $searchSize = 10000;

    private string $searchType = 'query_string';

    private bool $elasticSearchEnabled;

    private bool $partWordSearch = false;

    public const MATCH_ALL = 'match_all';

    public function __construct(private readonly Client $elasticsearch)
    {
        $elasticSearchConfig = config('database.connections.elasticsearch');
        $this->elasticSearchEnabled = (bool) $elasticSearchConfig['enabled'];
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
     * @throws NoSearchFieldException
     */
    public function search(string $model, ?string $query, array $fields = [], array $params = []): Builder
    {
        $this->setParams($params);
        if ($this->elasticSearchEnabled) {
            $index = $this->verifyModelsAndPrepareIndex([$model]);
            $data = $this->searchOnElasticsearch($index, $fields, $query);
            return $this->prepareBuilderFromElasticSearchResults($model, $data);
        }
        return $this->searchOnEloquent($model, $fields, $query);
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
        if (!$params) {
            return;
        }
        if (isset($params['size'])) {
            $this->searchSize = $params['size'];
        }
        if (isset($params['search_type'])) {
            $this->searchType = $params['search_type'];
        }
        if (isset($params['part_word_search'])) {
            $this->partWordSearch = (bool) $params['part_word_search'];
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

    /**
     * @throws NoSearchFieldException
     */
    private function searchOnEloquent(string $model, array $fields, ?string $query): Builder
    {
        /** @var Builder $builder */
        $builder = $model::query();
        if (empty($fields)) {
            $fields = array_keys((new $model())->searchableAttributes());
        }
        if (!$fields) {
            throw new NoSearchFieldException('No fields to search');
        }
        if ($this->partWordSearch) {
            foreach ($fields as $field) {
                $builder->orWhere($field, 'ilike', '%' . $query . '%');
            }
            return $builder;
        }
        return $builder->whereFullText($fields, $query);
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

    private function prepareBuilderFromElasticSearchResults(string $model, array $data): Builder
    {
        return $model::whereIn('id', Arr::pluck($data['data'], '_id'));
    }

    private function query(array $fields, ?string $query): array
    {
        if (!$query) {
            return [self::MATCH_ALL => new stdClass()];
        }
        if ($this->partWordSearch) {
            $query = '*' . $query . '*';
        }
        $properties = ['query' => $query];
        if ($fields) {
            $properties['fields'] = $fields;
        }
        return [$this->searchType => $properties];
    }
}

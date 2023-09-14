<?php

namespace App\Console\Commands;

use App\Exceptions\TraitNotUsedException;
use App\Traits\Searchable;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\AuthenticationException;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Illuminate\Console\Command;

class SearchReindexCommand extends Command
{
    protected $signature = 'search:reindex {model?}';

    protected $description = 'Indexes all provided model entities to Elasticsearch';
    private Client $client;

    /**
     * @throws AuthenticationException
     */
    public function __construct()
    {
        parent::__construct();
        $this->client = ClientBuilder::create()
            ->setHosts(config('database.connections.elasticsearch.hosts'))
            ->build();
    }

    /**
     * @throws ClientResponseException
     * @throws TraitNotUsedException
     * @throws ServerResponseException
     * @throws MissingParameterException
     */
    public function handle(): void
    {
        $model = $this->argument('model') ?? $this->ask('Put Model name (without `App\Models\`)');
        $model = 'App\Models\\' . $model;
        if (!class_exists($model)) {
            $this->error('Provided Model does not exists');
            return;
        }
        if (!in_array(Searchable::class, class_uses_recursive($model))) {
            throw new TraitNotUsedException("$model does not support Elasticsearch (Searchable trait is not used)");
        }
        $this->info('Indexing all entities. This might take a while...');

        foreach (call_user_func($model . '::cursor') as $entity) {
            $this->client->index([
                'index' => $entity->getSearchIndex(),
                'id' => $entity->getKey(),
                'body' => $entity->searchableAttributes(),
            ]);

            $this->output->write('.');
        }

        $this->info("\nDone!");
    }
}

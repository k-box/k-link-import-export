<?php

require_once __DIR__ . '/vendor/autoload.php';

use Carbon\Carbon;
use Dotenv\Dotenv;
use KSearchClient\Client;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;
use KSearchClient\Http\Authentication;
use KSearchClient\Model\Search\SearchParams;
use KSearchClient\Exception\SerializationException;
use JMS\Serializer\EventDispatcher\EventDispatcher;
use KSearchClient\Serializer\DeserializeErrorEventSubscriber;

$dotenv = Dotenv::create(__DIR__, '.export.env');
$dotenv->load();

$dotenv->required(['KLINK_URL', 'APP_URL', 'APP_TOKEN'])->notEmpty();


$client = Client::build($_ENV['KLINK_URL'], new Authentication($_ENV['APP_TOKEN'], $_ENV['APP_URL']));

$serializer = SerializerBuilder::create()
    ->configureListeners(function(EventDispatcher $dispatcher) {
        $dispatcher->addSubscriber(new DeserializeErrorEventSubscriber());
    })
    ->build();

echo "Pulling updated metadata from K-Link..." . PHP_EOL;

$uuids = array_keys(require('./data/source.php'));

$counter = 0;

try{
    $errors = [];
    $data = [];
    $limit = 50;
    $offset = 0;


    $searchParams = new SearchParams();
    $searchParams->search = '*';
    $searchParams->limit = 1;
    $searchParams->offset = 0;

    $result = $client->search($searchParams);

    $requests_to_do = ceil($result->totalMatches / $limit);

    echo "-> dumping $result->totalMatches entries" . PHP_EOL;

    if(!is_dir('./data/dumps')){
        mkdir('./data/dumps');
    }

    file_put_contents(
        './data/publications.php',
        '<?php return ['
    );

    for ($i=0; $i < $requests_to_do; $i++) { 
        
        try{
            $searchParams->search = '*';
            $searchParams->limit = $limit;
            $searchParams->offset = $offset;
            
            $result = $client->search($searchParams);

            try{

                foreach ($result->items as $item) {
                    
                    $serialized = $serializer->serialize($item, 'json');

                    file_put_contents("./data/dumps/$item->uuid.json", $serialized);
    
                    array_push($data, "'$serialized',");
    
                    echo ".";

                    $counter++;

                    if($counter > 0 && $counter % $limit == 0){
                        echo PHP_EOL;
                    }
                }

                file_put_contents(
                    './data/publications.php',
                    implode(PHP_EOL, $data),
                    FILE_APPEND
                );

                $data = [];

    
            } catch(\Throwable $ex){
    
                throw new SerializationException($ex->getMessage());
    
            } catch(\Exception $ex){
    
                throw new SerializationException($ex->getMessage());
    
            }
            
        }catch(Exception $ex){
            echo "E";
            $errors[] = "{$uuid}: {$ex->getMessage()}";

        }

        

        

    }

    file_put_contents(
        './data/publications.php',
        '];',
        FILE_APPEND
    );


    if(!empty($errors)){
        echo  PHP_EOL . 'errors' . PHP_EOL;
        echo join(PHP_EOL, $errors);
        file_put_contents('./data/export-errors.php', '<?php return '.var_export($errors, true).';');   
    }
    
}catch(Exception $ex){
    var_dump($ex);
}

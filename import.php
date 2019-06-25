<?php

require_once __DIR__ . '/vendor/autoload.php';

use Carbon\Carbon;
use Dotenv\Dotenv;
use KSearchClient\Client;
use JMS\Serializer\Serializer;
use KSearchClient\Model\Data\Data;
use JMS\Serializer\SerializerBuilder;
use KSearchClient\Http\Authentication;
use KSearchClient\Exception\SerializationException;
use JMS\Serializer\EventDispatcher\EventDispatcher;
use KSearchClient\Serializer\DeserializeErrorEventSubscriber;

$dotenv = Dotenv::create(__DIR__, '.import.env');
$dotenv->load();

$dotenv->required(['KLINK_URL', 'APP_URL', 'APP_TOKEN'])->notEmpty();

$client = Client::build($_ENV['KLINK_URL'], new Authentication($_ENV['APP_TOKEN'], $_ENV['APP_URL']), '3.6');

$serializer = SerializerBuilder::create()
    ->configureListeners(function(EventDispatcher $dispatcher) {
        $dispatcher->addSubscriber(new DeserializeErrorEventSubscriber());
    })
    ->build();

echo "Publishing on ".$_ENV['KLINK_URL']."..." . PHP_EOL;

try{

    if(!is_file('./data/publications.php')){
        throw new Exception("List of descriptors to publish not available");
    }

    $publications = require('./data/publications.php');

    $counter = 0;

    $errors = [];


    foreach ($publications as $publication) {
        
        $data = $serializer->deserialize($publication, Data::class, 'json');

        try{

            // removing the geo_location 
            // https://github.com/k-box/k-search/issues/17

            $data->geo_location = null;

            $result = $client->add($data, $data->properties->title);
            
            echo ".";
            
        }catch(Exception $ex){
            echo "E";
            $errors[] = "{$data->uuid}: {$ex->getMessage()}";
        }

        if($counter > 0 && $counter % 80 == 0){
            echo PHP_EOL;
        }

        $counter++;

    }


    if(!empty($errors)){
        echo  PHP_EOL . 'errors' . PHP_EOL;
        echo join(PHP_EOL, $errors);
        file_put_contents('./data/import-errors.php', '<?php return '.var_export($errors, true).';');   
    }
    
}catch(Exception $ex){
    var_dump($ex);
}

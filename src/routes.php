<?php

use Slim\Http\Request;
use Slim\Http\Response;

$app->get('/get/all', 'BeerController:getAll');
$app->post('/add/beer', 'BeerController:addBeer');
$app->post('/remove/beer', 'BeerController:removeBeer');
$app->post('/add/balance', 'BeerController:addBalance');
$app->post('/remove/balance', 'BeerController:removeBalance');

// Routes

$app->get('/', function (Request $request, Response $response, array $args) {
    // Sample log message
    $this->logger->info(gethostname() . ' - ' . $request->getUri()->getPath());

    // Render index view
    $response->getBody()->write("Welcome to root");

    return $response;
});

class ConfigController
{
    protected $container;

    public function __construct(Slim\Container $container)
    {
        $this->container = $container;
    }
}

class BeerController extends ConfigController
{

    public function getUserId($username)
    {
        $pdo = $this->container->database;
        $userTableStatement = $pdo->prepare("SELECT balance, id FROM users WHERE username = :username LIMIT 1");
        $userTableStatement->bindParam(':username', $username);
        $userTableStatement->execute();
        $userTableResults = $userTableStatement->fetchAll();
        return $userTableResults[0]['id'];
    }

    public function addBeer(Request $request, Response $response, $args)
    {
        $pdo = $this->container->database;
        $user_id = $this->getUserId($request->getHeaders()['PHP_AUTH_USER'][0]);
        $postVar = $request->getParsedBody();
        if(!isset($postVar['amount'])) {
            return $response->withStatus(400)->write($this->container->message->errorMessage('Wrong parameters', 0));
        }

        $date = date("Y-m-d");

        $beerTableStatement = $pdo->prepare("INSERT INTO beer(amount, date, user_id) VALUES (:amount, :date, :user_id)");
        $beerTableStatement->bindParam(':amount', $postVar['amount']);
        $beerTableStatement->bindParam(':date', $date);
        $beerTableStatement->bindParam(':user_id', $user_id);
        $beerTableStatement->execute();
        return $response->write($this->container->message->successMessage('Beer was added'));
    }

    public function removeBeer(Request $request, Response $response, $args)
    {
        $pdo = $this->container->database;
        $user_id = $this->getUserId($request->getHeaders()['PHP_AUTH_USER'][0]);
        $postVar = $request->getParsedBody();
        if(!isset($postVar['beer_id'])) {
            return $response->withStatus(400)->write($this->container->message->errorMessage('Wrong parameters', 0));
        }

        $date = date("Y-m-d");

        $beerTableStatement = $pdo->prepare("DELETE FROM beer WHERE beer_id = :beer_id");
        $beerTableStatement->bindParam(':beer_id', $postVar['beer_id']);
        $beerTableStatement->execute();
        return $response->write($this->container->message->successMessage('Beer was removed'));
    }

    public function addBalance(Request $request, Response $response, $args)
    {
        $pdo = $this->container->database;
        $user_id = $this->getUserId($request->getHeaders()['PHP_AUTH_USER'][0]);
        $postVar = $request->getParsedBody();
        if(!isset($postVar['amount'])) {
            return $response->withStatus(400)->write($this->container->message->errorMessage('Wrong parameters', 0));
        }

        $date = date("Y-m-d");

        $beerTableStatement = $pdo->prepare("INSERT INTO balance(amount, date, user_id) VALUES (:amount, :date, :user_id)");
        $beerTableStatement->bindParam(':amount', $postVar['amount']);
        $beerTableStatement->bindParam(':date', $date);
        $beerTableStatement->bindParam(':user_id', $user_id);
        $beerTableStatement->execute();
        return $response->write($this->container->message->successMessage('Balance was updated'));
    }

    public function removeBalance(Request $request, Response $response, $args)
    {
        $pdo = $this->container->database;
        $user_id = $this->getUserId($request->getHeaders()['PHP_AUTH_USER'][0]);
        $postVar = $request->getParsedBody();
        if(!isset($postVar['balance_id'])) {
            return $response->withStatus(400)->write($this->container->message->errorMessage('Wrong parameters', 0));
        }

        $date = date("Y-m-d");

        $beerTableStatement = $pdo->prepare("DELETE FROM balance WHERE balance_id = :balance_id");
        $beerTableStatement->bindParam(':balance_id', $postVar['balance_id']);
        $beerTableStatement->execute();
        return $response->write($this->container->message->successMessage('Balance was updated'));
    }

    public function getAll(Request $request, Response $response, $args)
    {
        $pdo = $this->container->database;
        $username = $request->getHeaders()['PHP_AUTH_USER'][0];
        $array = array();

        $userTableStatement = $pdo->prepare("SELECT balance, id FROM users WHERE username = :username LIMIT 1");
        $userTableStatement->bindParam(':username', $username);
        $userTableStatement->execute();
        $userTableResults = $userTableStatement->fetchAll();
        $array['balance'] = $userTableResults[0]['balance'];
        $array['user_id'] = $userTableResults[0]['id'];

        $balancetableStatement = $pdo->prepare("SELECT balance_id, date, amount FROM balance WHERE user_id = :user_id");
        $balancetableStatement->bindParam(':user_id', $array['user_id']);
        $balancetableStatement->execute();
        $array['balanceHistory'] = $balancetableStatement->fetchAll();

        $beerTableStatement = $pdo->prepare("SELECT beer_id, date, amount FROM beer WHERE user_id = :user_id");
        $beerTableStatement->bindParam(':user_id', $array['user_id']);
        $beerTableStatement->execute();
        $array['beerHistory'] = $beerTableStatement->fetchAll();

        return $response->write(json_encode($array));
    }

}

function prettyPrintAndDie($array)
{
    echo "<pre>" . print_r($array, true) . "</pre>";
    die();
}
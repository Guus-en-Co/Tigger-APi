<?php
// Application middleware

$container = $app->getContainer();

use \Slim\Middleware\HttpBasicAuthentication\PdoAuthenticator;
use \Slim\Middleware\HttpBasicAuthentication\AuthenticatorInterface;

class RandomAuthenticator implements AuthenticatorInterface {
    public function __invoke(array $arguments) {
        global $container;

        $usertableStatement = $container->database->prepare("SELECT id FROM users WHERE username = :username LIMIT 1");
        $usertableStatement->bindParam(':username', $arguments['user']);
        $usertableStatement->execute();
        $user_id = $usertableStatement->fetchAll();

        if($user_id && $user_id > 0) {
            return true;
        } else {
            $newUserTableStatement = $container->database->prepare("INSERT INTO users(username) VALUES (:username)");
            $newUserTableStatement->bindParam(':username', $arguments['user']);
            $newUserTableStatement->execute();
            return true;
        }
    }
}

// Checks if parameters are valid
$app->add(function($request, $response, $next) {

    if($request->getMethod() == "OPTIONS"){
        die();
        die(); // Just
        die(); // making
        die(); // sure
        die(); // you
        die(); // died
    }
    global $container;
    $container->logger->info(gethostname() . ' - ' . $container->request->getUri()->getPath());

    return $next($request, $response);
});

$app->add(new \Slim\Middleware\HttpBasicAuthentication([
    "realm" => "Protected",
    "secure" => false,
    "authenticator" => new RandomAuthenticator,
    "error" => function($request, $response, $arguments) {
        $msg = new \Messenger\Messenger();
        return $response->withStatus(401)->write($msg->errorMessage('Authentification was not accepted ', 14));
    }
]));

//
//CREATE TRIGGER beer_trigger AFTER INSERT ON beer
//FOR EACH ROW
//    UPDATE users SET balance = balance - (NEW.amount * 0.5) WHERE id = NEW.user_id

// UPDATE users SET balance = balance + (OLD.amount * 0.5) WHERE id = OLD.user_id

// UPDATE users SET balance = balance + NEW.amount WHERE id = NEW.user_id
// UPDATE users SET balance = balance - OLD.amount WHERE id = OLD.user_id
<?php
// Load config file
if (file_exists(__DIR__ . '/config.local.php')) {
    $config = require __DIR__ . '/config.local.php';
} else {
    $config = require __DIR__ . '/config.php';
}

// Add Log service to container
$container->addService('\Webiik\Log\Log', function () use (&$container, &$silent) {
    $log = new \Webiik\Log\Log();

    // In silent mode failed loggers not throw exceptions,
    // instead of it these exceptions are logged with other loggers
    // and failed loggers are skipped.
    $log->setSilent($silent);

//    // Add Mail logger for messages in error group
//    $log->addLogger(function () use (&$container) {
//        $logger = new \Webiik\Log\Logger\MailLogger();
//        $logger->setDelay(1);
//        $logger->setSubject('Webiik App Log');
//        $logger->setFrom($config['email']['from']);
//        $logger->setTo($config['email']['to']);
//        $logger->setMailService(function (string $from, string $to, string $subject, string $message) use (&$container) {
//            /** @var \Webiik\Mail\Mail $webiikMail */
//            $webiikMail = $container->get('\Webiik\Mail\Mail');
//            $webiikMailMessage = $webiikMail->createMessage();
//            $webiikMailMessage->setFrom($from);
//            $webiikMailMessage->addTo($to);
//            $webiikMailMessage->setSubject($subject);
//            $webiikMailMessage->setBody($message);
//            $undelivered = $webiikMail->send([$webiikMailMessage]);
//            if ($undelivered) {
//                // Arr of undelivered emails
//                echo 'Error sending emails.';
//            } else {
//                echo 'Log successfully sent to ' . $to . '.';
//            }
//        });
//        return $logger;
//    })->setGroup('error');

    // Add ErrorLog logger for messages in error group
    $log->addLogger(function () {
        $logger = new \Webiik\Log\Logger\ErrorLogger();
        $logger->setMessageType(3);
        $logger->setDestination(__DIR__ . '/tmp/logs/error.log');
        return $logger;
    })->setGroup('error');

    // Add ErrorLog logger for messages not in error group
    $log->addLogger(function () {
        $logger = new \Webiik\Log\Logger\ErrorLogger();
        $logger->setMessageType(3);
        $logger->setDestination(__DIR__ . '/tmp/logs/info.log');
        return $logger;
    })->setNegativeGroup('error');

    return $log;
});

// Add Mail service to container
$container->addService('\Webiik\Mail\Mail', function () {
    $mail = new \Webiik\Mail\Mail();

    // Add PHPMailer mailer
    $mail->setMailer(function () {
        return new \Webiik\Mail\Mailer\PHPMailer(new \PHPMailer\PHPMailer\PHPMailer());
    });
    return $mail;
});

//// Send some email(s)...
///* @var $mail \Webiik\Mail\Mail */
//$mail = $container->get('Webiik\Mail\Mail');
//
//// Create email message
//$message = $mail->createMessage();
////
//// Configure charset and priority
//$message->setCharset('utf-8');
//$message->setPriority(3);
//
//// Configure sender, bounce address and recipients
//$message->setFrom($config['email']['from'], $config['email']['fromName']);
//$message->setBounceAddress($config['email']['to']);
//$message->addTo($config['email']['to'], $config['email']['toName']);
//$message->addReplyTo($config['email']['to']);
//$message->addCc($config['email']['to']);
//$message->addBcc($config['email']['to']);
//
//// Configure message
//$message->setSubject('Meow!');
//$message->setBody('Meow World! This is the message from the Kitten Kingdom.');
//$message->setAlternativeBody('Alternative body of message');
//$message->addFileAttachment(__DIR__ . '/email.log');
//$message->addFileEmbed(__DIR__ . '/email.log', 'a@a');
//
//// Send email message(s)
//$undelivered = $mail->send([$message]);
//
//// Log undelivered emails
//foreach ($undelivered as $email) {
//    $container->get('wsErrLog')->log('email', 'Undelivered message to ' . htmlspecialchars($email));
//}

// Generate some error
//strtr('foo', 'bar');

// Add some logs to Log
/** @var \Webiik\Log\Log $log */
$log = $container->get('\Webiik\Log\Log');
$log->info('Meow Dolly!');
$log->info('Meow Molly!');
$log->critical('Dolly bit Molly!');

// Write logs from Log
$log->write();

// Test Router
$router = new \Webiik\Router\Router();
$router->setBaseURI('webiik/components');
$router->addRoute(['get'], '/', 'Class:method');
$router->addRoute(['get'], '/mysak/', 'Class:method', 'mouse', 'cs');
$router->addRoute(['get'], '/mouse/', 'Class:method');
$router->addRoute(['get'], '/meow/(?<name>[a-z]+)?', 'Class:method', 'meow')->sensitive();
$router->addRoute(['get'], '/mnau/(?<name>[a-z]*)?/([a-z]*)', 'Class:method', 'meow', 'cs')->mw('Class:method');
$router->addRoute(['get'], '/miau/(?<name>[a-z]+)?', 'Class:method', 'meow', 'de');
$route = $router->match();

if ($router->getHttpCode() == 200) {

    echo 'Route regex: ' . htmlspecialchars($route->getRegex()) . '</br>';
    echo 'Route regex params: ';
    print_r($route->getRegexParameters());
    echo '</br>';
    echo 'Route param values: ';
    print_r($route->getParameters());
    echo '</br>';
    echo 'Route lang: ' . $route->getLang() . '</br>';
    echo 'Route URL: ' . $route->getURL() . '</br>';
    echo 'Route URI: ' . $route->getURI() . '</br>';
    echo 'Route URI: ' . $route->getURI([], 'en') . '</br>';
//    echo 'Route URI: ' . $route->getURI(['Dolly']) . '</br>';
    echo 'Missing route params: ';
    print_r($route->getMissingParameters()); // ['2' => (\w+)]
    echo '</br>';
    echo 'Route methods: ';
    print_r($route->getMethods()); // ['get']
    echo '</br>';
    echo 'Route name: ' . $route->getName() . '</br>'; // home
    print_r($route->getController()); // ['class', 'method']
    echo '</br>';
    echo 'Route middleware: ';
    print_r($route->getMw()); // ['Class:method']
    echo '</br>';
    echo 'Route sensitiveness: ' . $route->isSensitive() . '</br>'; // true
} elseif ($router->getHttpCode() == 405) {
    // 405
    echo '405 - Method ' . $_SERVER['REQUEST_METHOD'] . ' Not Allowed' . '<br/>';
    echo '<br/>';
} elseif ($router->getHttpCode() == 404) {
    // 404
    echo '404 - Not Found';
    echo '<br/>';
}

// Test Middleware
require __DIR__ . '/HomeController.php';
require __DIR__ . '/MwTest.php';
$middleware = new \Webiik\Middleware\Middleware($container, new \Webiik\Data\Data());
$middleware->add('MwTest:run', ['yyy' => 'YYY']);
$middleware->add('HomeController:run', ['fff' => 'FFF']);
$middleware->run();

// Test Arr
echo 'Dot notation array test...<br/>';
$arr = new \Webiik\Arr\Arr();
$array = [];
$arr->set('dot.notation.test', ['key' => 'val'], $array);
//$arr->add('dot.notation.test', null, $array);
//$arr->delete('dot.notation.test', $array);
print_r($arr->get('dot.notation.test', $array));
echo '<br/>';
echo 'Is key \'meow.dolly\' in array? ' . ($arr->isIn('meow.dolly', $array) ? 'Yes' : 'No') . '<br/>';
// Test Token
$token = new \Webiik\Token\Token();
$safeToken = $token->generate();
echo 'Token (' . strlen($safeToken) . '): ' . $safeToken . '<br/>';
$cheapToken = $token->generateCheap();
echo 'Token (' . strlen($cheapToken) . '): ' . $cheapToken . '<br/>';
if ($token->compare($cheapToken, $safeToken)) {
    echo 'Tokens are same.<br/>';
} else {
    echo 'Tokens are different.<br/>';
}

// Test Cookie
$cookie = new \Webiik\Cookie\Cookie();
$cookie->setCookie('greeting', 'meow');
if ($cookie->isCookie('greeting')) {
    echo 'Greeting from cookie is: ' . $cookie->getCookie('greeting') . '<br/>';
}
//$cookie->delCookie('greeting');

// Test Session
$session = new \Webiik\Session\Session();
$session->setSessionDir(__DIR__ . '/tmp/sessions');
//$session->setSessionGcLifetime(5);
//$session->setSessionGcProbability(100);
//$session->setToSession('test', ['ahoj' => 'cau']);
$session->setToSession('foo', 'bar');
//$session->addToSession('foo', 'bar');
echo 'Session module name: ' . session_module_name() . '<br/>';
echo 'Session values: ';
print_r($session->getAllFromSession());
echo '<br/>';

// Test CSRF
$csrf = new \Webiik\Csrf\Csrf($token, $session);
echo 'CSRF token is: ' . $csrf->create() . '</br>';
echo 'Is valid: ' . $csrf->validate('lZMNJZ74L1id0xZp') . '</br>';

// Test Flash
$flash = new \Webiik\Flash\Flash($session);
$flash->addFlashCurrent('inf', 'Meow {name}!', ['name' => 'Dolly']);
$flash->addFlashNext('inf', 'Meow {name}!', ['name' => 'Molly']);
echo 'Flash messages: ';
print_r($flash->getFlashes());
echo '<br/>';

// Test Login
$login = new \Webiik\Login\Login($token, $cookie, $session);
//$login->setNamespace($route->getLang()); // use login sections
//$login->setAutoLogoutTime(3);
$login->setPermanentCookieName('remember');
//$login->setPermanentLoginTime(10);
$login->setPermanentLoginStorage(function () {
    $fs = new \Webiik\Login\Storage\FileStorage();
    $fs->setPath(__DIR__ . '/tmp/permanent');
//    $fs->setDefaultTtl(10);
    return $fs;
}); // set permanent identifier storage

//$login->login(1, true, 'user');
//$login->login(1, false, 'user');

if ($login->isLogged()) {
    echo 'User id: ' . $login->getUserId() . ' is logged.<br/>';
} else {
    echo 'User is not logged<br/>';
}

$login->updateAutoLogoutTs();
//$login->logout();

// Test Translation
$array = [
    'colors' => [
        'blue' => 'modra',
        'red' => 'cervena',
        'green' => [
            'light' => 'svetle zelena',
            'dark' => 'tmave zelena',
        ],
    ],
    'meow' => 'mnau',
];
$newArr = [
    'cars' => ['vw', 'bmw'],
    'colors' => [
        'blue' => 'blau',
    ],
    'hello' => [
        'name' => 'Miau {name} from {city}',
        'mr' => 'Miau {gender, select, =male {Mr.} =female {Mrs.}} {name}',
    ],
    'cats' => '{numCats, Plural, =-2-0 {No cat has} =1 {One cat has} =2+ {{numCats} cats have}} birthday.',
    'ice-cream' => '{gender, Select, =male {He {name}} =female {She}} likes vanilla ice cream.',
];
$translation = new \Webiik\Translation\Translation($arr);
$translation->addArr($array);
$translation->addArr($newArr);
//$arr->set('colors.blue', 'blau', $array);
//$arr->set('hello', 'hallo', $array);
//print_r($translation->getAll([]));
echo 'Translated text: <br/>';
//print_r($translation->get('hello.name', ['name' => ['name' => 'Dolly', 'city' => 'Prague']]));
//print_r($translation->get('cats', ['numCats' => 4]));
print_r($translation->get('ice-cream', ['gender' => 'male', 'name' => 'Peter']));
//print_r($translation->get('hello.name', ['name' => 'Dolly']));
//print_r($translation->get('hello.name', ['city' => 'Prague']));
echo '<br/>';
echo 'Missing keys and contexts: ';
print_r($translation->getMissing());
echo '<br/>';

// Test Database
echo 'Test database access: <br/>';
$db = new \Webiik\Database\Database();
$db->add('main', $config['database']['driver'], $config['database']['host'], $config['database']['db'], $config['database']['user'], $config['database']['pswd']);
$pdo = $db->connect();
$q = $pdo->prepare('SELECT * FROM auth_users');
$q->execute();
print_r($q->fetchAll());
echo '<br/>';

// Test View
$container->addService('\Webiik\View\View', function () {
    $view = new \Webiik\View\View();
    $view->setRenderer(function () {
        // Set Twig basic settings
        $loader = new Twig_Loader_Filesystem(__DIR__);
        $environment = new Twig_Environment($loader, array(
            'cache' => __DIR__ . '/tmp/view',
            'debug' => true,
        ));

        $renderer = new \Webiik\View\Renderer\Twig($environment);
        $renderer->core()->addExtension(new \Twig_Extension_Debug());

        return $renderer;
    });
    return $view;
});
/** @var \Webiik\View\View $view */
$view = $container->get('\Webiik\View\View');
echo $view->render('test.twig', ['name' => 'Dolly']);

// Test Validator
$validator = new \Webiik\Validator\Validator();
$validator->addInput('Hello', function () {
    return [
        new \Webiik\Validator\Rules\StrLenMin(5, 'Err: Input is shorter than 5 chars.'),
        new \Webiik\Validator\Rules\StrLenMax(10, 'Err: Input is longer than 10 chars.'),
    ];
}, 'greeting');
$invalid = $validator->validate();
print_r($invalid);
echo '<br/>';

// Test CurlHttpClient
$chc = new \Webiik\CurlHttpClient\CurlHttpClient();

//// Send multiple requests at once
//$requests = [
//    $chc->prepareRequest($config['url']['site']),
//    $chc->prepareRequest($config['url']['site']),
//];
//$responses = $chc->sendMulti($requests);
//foreach ($responses as $res) {
//    print_r($res->requestHeaders());
//    print_r($res->requestCookies());
//    print_r($res->headers());
//    print_r($res->cookies());
//}

//// Send single request
//$req = $chc->prepareRequest($config['url']['site']);
//$req->cookie('lang', 'en; expires=Wed, 01-May-2019 15:42:33 GMT; Max-Age=7772400; path=/');
//$res = $chc->send($req);
//print_r($res->requestHeaders());
//print_r($res->requestCookies());
//print_r($res->headers());
//print_r($res->cookies());
//echo htmlspecialchars($res->body());

//// Download file to server
//$req = $chc->prepareRequest($config['url']['img']);
//$req->downloadToServer('screenshot.jpg');
//$chc->send($req);

//// Download file to client
//header('Content-Disposition: attachment; filename="screenshot.jpg"');
//header('Content-Type: image/jpeg');
//$req = $chc->prepareRequest($config['url']['site']);
//$req->downloadToClient();
//$chc->send($req);

//header('Content-Disposition: attachment; filename="le-bon-berger-1440-1.mp4"');
//header('Content-Type: video/mp4');
//$req = $chc->prepareRequest($config['url']['video']);
//$req->downloadToClient(1024 * 1024);
//$chc->send($req);

//// Stream file to client
//header('Content-Type: image/jpeg');
//$req = $chc->prepareRequest($config['url']['img']);
//$req->downloadToClient();
//$chc->send($req);

//header('Content-Type: video/mp4');
//$req = $chc->prepareRequest($config['url']['video']);
//$req->downloadToClient(1024 * 1024);
//$chc->send($req);

//// Upload file from server to FTP
//$req = $chc->prepareRequest($config['ftp']['host']);
//$req->upload(__DIR__ . '/do-what-you-cant.3gp');
//$req->auth($config['ftp']['user'], $config['ftp']['pswd']);
//$res = $chc->send($req);
//if ($res->errNum() == 0) {
//    echo 'File uploaded succesfully.';
//} else {
//    echo 'File upload error: ' . $res->errMessage();
//}

//// Test progress bar
//$req = $chc->prepareRequest($config['ftp']['host']);
//$req->upload(__DIR__ . '/do-what-you-cant.3gp');
//$req->auth($config['ftp']['user'], $config['ftp']['pswd']);
//$req->progressFile('do-what-you-cant', __DIR__ . '/tmp/progress');
////$req->progressJson();
//$chc->send($req);

//// Test OAuth1Client
//$oAuth1Client = new \Webiik\OAuth1Client\OAuth1Client($chc, $token);
//
//// Your callback URL after authorization
//$oAuth1Client->setCallbackUrl('https://127.0.0.1/webiik/');
//
//// API end points
//$oAuth1Client->setAuthorizeUrl('https://api.twitter.com/oauth/authenticate');
//$oAuth1Client->setRequestTokenUrl('https://api.twitter.com/oauth/request_token');
//$oAuth1Client->setAccessTokenUrl('https://api.twitter.com/oauth/access_token');
//
//// API credentials (create yours at https://developer.twitter.com/en/apps)
//$oAuth1Client->setConsumerKey($config['twitter']['key']);
//$oAuth1Client->setConsumerSecret($config['twitter']['secret']);
//
//// Make API calls...
//
//if (!isset($_GET['oauth_verifier'])) {
//    // 1. Prepare Twitter login link
//    echo '<a href="' . $oAuth1Client->getAuthorizeUrl() . '" target="_blank">Authorize with Twitter</a><br/>';
//}
//
//if (isset($_GET['oauth_verifier'])) {
//    // 2. Verify oauth_token
//    $accessToken = $oAuth1Client->getAccessToken();
//}
//
//if (isset($accessToken['oauth_token'])) {
//    // 3. oauth_token is valid, user is authorized by Twitter
//    // Access protected resources...
//}
//
//// Test OAuth2Client
//$oAuth2Client = new \Webiik\OAuth2Client\OAuth2Client($chc);
//
//// Your callback URL after authorization
//$oAuth2Client->setRedirectUri('https://127.0.0.1/webiik/');
//
//// API end points
//$oAuth2Client->setAuthorizeUrl('https://www.facebook.com/v3.2/dialog/oauth');
//$oAuth2Client->setAccessTokenUrl('https://graph.facebook.com/v3.2/oauth/access_token');
//
//// API credentials (create yours at https://developers.facebook.com/apps/)
//$oAuth2Client->setClientId($config['facebook']['id']);
//$oAuth2Client->setClientSecret($config['facebook']['secret']);
//
//// Make API calls...
//
//// Define scope
//$scope = [
//    'email',
//];
//
//if (!isset($_GET['code'])) {
//    // 1. Prepare Facebook user login link with specified scope and grand type
//    echo '<a href="' . $oAuth2Client->getAuthorizeUrl($scope) . '" target="_blank">Authorize with Facebook</a><br/>';
//}
//
//if (isset($_GET['code'])) {
//    // 2. Verify code to obtain user access_token
//    $user = $oAuth2Client->getAccessTokenByCode();
//
//    // 3. Verify clientId and clientSecret to obtain app access_token
//    $app = $oAuth2Client->getAccessTokenByCredentials();
//}
//
//if (isset($user['access_token']) && isset($app['access_token'])) {
//    // 4. User and app access_tokens are valid, user and app are authorized by Facebook
//    // Access protected resources...
//}

// Show page content
echo 'Meow!<hr/>';
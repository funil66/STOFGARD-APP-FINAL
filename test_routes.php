<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$routes = json_decode(file_get_contents('/tmp/routes.json'), true);
$user = \App\Models\User::first();
if (!$user)
    die("No user found for testing\n");
Auth::login($user);

$errors = [];
$tested = 0;

$serverVars = [
    'SERVER_NAME' => 'localhost',
    'SERVER_PORT' => 80,
    'HTTP_HOST' => 'localhost',
    'HTTP_USER_AGENT' => 'CLI Testing',
    'REMOTE_ADDR' => '127.0.0.1',
];

foreach ($routes as $route) {
    if (!isset($route['uri']) || str_contains($route['uri'], '{') || str_contains($route['uri'], '_ignition'))
        continue;
    $uri = '/' . ltrim($route['uri'], '/');
    try {
        $req = \Illuminate\Http\Request::create($uri, 'GET', [], [], [], $serverVars);
        $res = $kernel->handle($req);
        $status = $res->getStatusCode();
        if ($status >= 500) {
            $errors[] = "500 ERROR on GET $uri";
        }
    } catch (\Throwable $e) {
        $errors[] = "EXCEPTION on GET $uri: " . $e->getMessage();
    }
    $tested++;
}

$output = "Tested $tested routes.\n";
if (empty($errors)) {
    $output .= "SUCCESS: No 500 errors found.\n";
} else {
    $output .= "FOUND " . count($errors) . " ERRORS:\n";
    $output .= implode("\n", $errors) . "\n";
}
file_put_contents('/tmp/route_test_results.txt', $output);
echo "Script finished.\n";

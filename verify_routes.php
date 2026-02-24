<?php
$routes = json_decode(file_get_contents('/tmp/routes.json'), true);
$user = \App\Models\User::first();
if (!$user) die("No user found for testing\n");
Auth::login($user);

$errors = [];
$tested = 0;

foreach ($routes as $route) {
    if (!isset($route['uri']) || str_contains($route['uri'], '{') || str_contains($route['uri'], '_ignition')) continue;
    $uri = '/' . ltrim($route['uri'], '/');
    try {
        $request = \Illuminate\Http\Request::create($uri, 'GET');
        $response = app()->make(\Illuminate\Contracts\Http\Kernel::class)->handle($request);
        $status = $response->getStatusCode();
        if ($status >= 500) {
            $errors[] = "500 ERROR on GET $uri";
        }
    } catch (\Throwable $e) {
        $errors[] = "EXCEPTION on GET $uri: " . $e->getMessage();
    }
    $tested++;
}
echo "Tested $tested routes.\n";
if (empty($errors)) {
    echo "SUCCESS: No 500 errors found.\n";
} else {
    echo "FOUND " . count($errors) . " ERRORS:\n";
    echo implode("\n", $errors) . "\n";
}

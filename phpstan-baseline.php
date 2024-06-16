<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	// identifier: new.static
	'message' => '#^Unsafe usage of new static\\(\\)\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/src/CartItem.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];

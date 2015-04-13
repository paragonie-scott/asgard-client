<?php
require_once '../autoload.php';

$config = \json_decode(\file_get_contents(CONFIGROOT.'notary_server.json'), true);

if (empty($config['enabled'])) {
    header("Content-Type: application/json");
    echo \json_encode([
            'error' => 'Notary server is disabled'
        ],
        JSON_PRETTY_PRINT
    );
    exit;
}

// Use Toro to serve our requests; but add namespacing first
\Toro::serve(
    \ParagonIE\AsgardNotary\Utilities::handlers([
        '/' => 'Index',
        '/blockchain/([0-9a-f]+)' => 'BlockChain',
        // This is the API endpoin that Paragon Initiative will use
        // to push updates:
        '/push' => 'Push'
    ])
);

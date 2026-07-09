#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/../../../../../../../" && pwd)"
WORDPRESS_COMPOSE=(docker compose -f "$ROOT_DIR/wordpress-docker/docker-compose.yml" -f "$ROOT_DIR/docker-compose.override.yml")

php_script=$(cat <<'PHP'
if (! function_exists('libresign_theme_receive_github_site_deploy_webhook')) {
    $themeDir = WP_CONTENT_DIR . '/themes/libresign';

    require_once $themeDir . '/inc/footer-fragment.php';
    require_once $themeDir . '/inc/github-site-webhook.php';
}

if (! function_exists('libresign_theme_receive_github_site_deploy_webhook')) {
    throw new RuntimeException('Theme webhook functions could not be loaded from the libresign theme directory.');
}

putenv('LIBRESIGN_SITE_DEPLOY_REPOSITORY_NAME=LibreSign/site');
putenv('LIBRESIGN_GITHUB_WEBHOOK_SECRET=test-secret');
putenv('LIBRESIGN_SITE_ORIGIN=https://libresign.example');
putenv('LIBRESIGN_SITE_DEPLOY_WORKFLOW_NAME=Deploy');
delete_option('libresign_site_fragment_last_sync');

$tempUploadsBase = sys_get_temp_dir() . '/libresign-theme-webhook-smoke-' . bin2hex(random_bytes(8));
$tempUploadsUrl  = 'https://uploads.example/libresign-theme-webhook-smoke';

add_filter('upload_dir', static function ($uploads) use ($tempUploadsBase, $tempUploadsUrl) {
    $uploads['path']    = $tempUploadsBase;
    $uploads['url']     = $tempUploadsUrl;
    $uploads['subdir']  = '';
    $uploads['basedir'] = $tempUploadsBase;
    $uploads['baseurl'] = $tempUploadsUrl;

    return $uploads;
});

$uploads = wp_upload_dir();
$headerBaseDir = trailingslashit($uploads['basedir']) . 'libresign-header';
$footerBaseDir = trailingslashit($uploads['basedir']) . 'libresign-footer';

delete_transient('libresign_theme_github_delivery_' . md5('delivery-1'));
delete_transient('libresign_theme_github_delivery_' . md5('delivery-preview-1'));

$deleteTree = static function (string $path) use (&$deleteTree): void {
    if (! is_dir($path)) {
        return;
    }

    $items = scandir($path);
    if (! is_array($items)) {
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $itemPath = $path . DIRECTORY_SEPARATOR . $item;
        if (is_dir($itemPath)) {
            $deleteTree($itemPath);
        } else {
            unlink($itemPath);
        }
    }

    rmdir($path);
};

$deleteTree($headerBaseDir);
$deleteTree($footerBaseDir);

$httpFixtures = [
    'https://libresign.example/fragments/header' => [
        'code' => 200,
        'body' => '<div class="libresign-site-header-fragment" data-fragment-css="https://libresign.example/assets/build/assets/header-fragment.css" data-fragment-js="https://libresign.example/assets/build/assets/header-fragment.js"><a href="https://libresign.example/fragments/header">Default</a><a href="https://libresign.example/fragments/pt-BR/header">PT-BR</a></div>',
    ],
    'https://libresign.example/fragments/pt-BR/header' => [
        'code' => 200,
        'body' => '<div class="libresign-site-header-fragment" data-fragment-css="https://libresign.example/assets/build/assets/header-fragment.css" data-fragment-js="https://libresign.example/assets/build/assets/header-fragment.js"><a href="https://libresign.example/pt-BR/">PT-BR</a></div>',
    ],
    'https://libresign.example/fragments/footer' => [
        'code' => 200,
        'body' => '<div class="libresign-site-footer-fragment" data-fragment-css="https://libresign.example/assets/build/assets/footer-fragment.css" data-fragment-js="https://libresign.example/assets/build/assets/footer-fragment.js"><img src="/assets/images/logo.svg" alt="Logo"></div>',
    ],
    'https://libresign.example/fragments/pt-BR/footer' => [
        'code' => 200,
        'body' => '<div class="libresign-site-footer-fragment" data-fragment-css="https://libresign.example/assets/build/assets/footer-fragment.css" data-fragment-js="https://libresign.example/assets/build/assets/footer-fragment.js"><img src="/assets/images/logo.svg" alt="Logo PT"></div>',
    ],
    'https://libresign.example/assets/build/assets/header-fragment.css' => [
        'code' => 200,
        'body' => '.libresign-site-header-fragment{background:url("/assets/images/header-logo.svg");}',
    ],
    'https://libresign.example/assets/build/assets/header-fragment.js' => [
        'code' => 200,
        'body' => 'console.log("header-fragment");',
    ],
    'https://libresign.example/assets/build/assets/footer-fragment.css' => [
        'code' => 200,
        'body' => '.libresign-site-footer-fragment{background:url(/assets/images/footer-logo.svg);}',
    ],
    'https://libresign.example/assets/build/assets/footer-fragment.js' => [
        'code' => 200,
        'body' => 'console.log("footer-fragment");',
    ],
];

add_filter('pre_http_request', static function ($preempt, $parsedArgs, $url) use ($httpFixtures) {
    if (! isset($httpFixtures[$url])) {
        if (0 === strpos($url, 'https://libresign.example/fragments/')) {
            return [
                'headers'  => [],
                'body'     => '',
                'response' => [
                    'code'    => 404,
                    'message' => 'Not Found',
                ],
                'cookies'  => [],
                'filename' => null,
            ];
        }

        return new WP_Error('missing_fixture', 'Missing HTTP fixture for ' . $url);
    }

    return [
        'headers'  => [],
        'body'     => $httpFixtures[$url]['body'],
        'response' => [
            'code'    => $httpFixtures[$url]['code'],
            'message' => 'OK',
        ],
        'cookies'  => [],
        'filename' => null,
    ];
}, 10, 3);

$payload = [
    'action' => 'completed',
    'repository' => [
        'full_name' => 'LibreSign/site',
    ],
    'workflow_run' => [
        'name' => 'Deploy',
        'head_branch' => 'main',
        'event' => 'push',
        'conclusion' => 'success',
        'head_sha' => 'abc123def456',
        'updated_at' => '2025-01-01T00:00:00Z',
        'html_url' => 'https://github.com/LibreSign/site/actions/runs/1',
    ],
];

$previewPayload = $payload;
$previewPayload['workflow_run']['name'] = 'Deploy PR previews';
$previewPayload['workflow_run']['head_branch'] = 'feature/pr-preview';
$previewBody = wp_json_encode($previewPayload);
$previewRequest = new WP_REST_Request('POST', '/libresign/v1/site-deploy-webhook');
$previewRequest->set_body($previewBody);
$previewRequest->set_header('X-GitHub-Event', 'workflow_run');
$previewRequest->set_header('X-GitHub-Delivery', 'delivery-preview-1');
$previewRequest->set_header('X-Hub-Signature-256', 'sha256=' . hash_hmac('sha256', $previewBody, 'test-secret'));
$previewRequest->set_header('User-Agent', 'GitHub-Hookshot/test');

$previewResponse = libresign_theme_receive_github_site_deploy_webhook($previewRequest);
if (is_wp_error($previewResponse)) {
    throw new RuntimeException('Expected preview webhook to be ignored, got WP_Error: ' . $previewResponse->get_error_message());
}

$previewData = $previewResponse instanceof WP_REST_Response ? $previewResponse->get_data() : $previewResponse;
if (! is_array($previewData) || ($previewData['status'] ?? '') !== 'ignored') {
    throw new RuntimeException('Expected preview webhook to be ignored. Got: ' . var_export($previewData, true));
}

if (is_dir($headerBaseDir) || is_dir($footerBaseDir)) {
    throw new RuntimeException('Preview webhook should not create fragment artifacts.');
}

$body = wp_json_encode($payload);
$signature = 'sha256=' . hash_hmac('sha256', $body, 'test-secret');

$request = new WP_REST_Request('POST', '/libresign/v1/site-deploy-webhook');
$request->set_body($body);
$request->set_header('X-GitHub-Event', 'workflow_run');
$request->set_header('X-GitHub-Delivery', 'delivery-1');
$request->set_header('X-Hub-Signature-256', $signature);
$request->set_header('User-Agent', 'GitHub-Hookshot/test');

$response = libresign_theme_receive_github_site_deploy_webhook($request);

if (is_wp_error($response)) {
    throw new RuntimeException('Expected webhook success, got WP_Error: ' . $response->get_error_message());
}

$data = $response instanceof WP_REST_Response ? $response->get_data() : $response;
if (! is_array($data) || ($data['status'] ?? '') !== 'synced') {
    throw new RuntimeException('Expected synced response payload. Got: ' . var_export($data, true));
}

$expectedFiles = [
    $headerBaseDir . '/default/header.html',
    $headerBaseDir . '/default/header.css',
    $headerBaseDir . '/pt-BR/header.html',
    $footerBaseDir . '/default/footer.html',
    $footerBaseDir . '/default/footer.css',
    $footerBaseDir . '/pt-BR/footer.html',
];

foreach ($expectedFiles as $file) {
    if (! is_file($file)) {
        throw new RuntimeException('Expected synced fragment artifact not found: ' . $file);
    }
}

$footerCss = (string) file_get_contents($footerBaseDir . '/default/footer.css');
if (strpos($footerCss, 'https://libresign.example/assets/images/footer-logo.svg') === false) {
    throw new RuntimeException('Expected footer CSS asset URL to be rewritten to absolute site origin.');
}

$headerHtml = (string) file_get_contents($headerBaseDir . '/default/header.html');
if (strpos($headerHtml, 'https://libresign.example/fragments/pt-BR/header') === false) {
    throw new RuntimeException('Expected discovered locale link to remain available in stored header HTML.');
}

echo "github-site-webhook-smoke: ok\n";
PHP
)

"${WORDPRESS_COMPOSE[@]}" exec -T --user www-data wordpress wp eval "$php_script"
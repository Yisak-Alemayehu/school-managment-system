<?php
/**
 * Response Helpers
 * Urjiberi School Management ERP
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

/**
 * Send JSON response
 */
function json_response(array $data, int $statusCode = 200): never {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Check if request is AJAX
 */
function is_ajax_request(): bool {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Check if request is POST
 */
function is_post(): bool {
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
}

/**
 * Check if request is GET
 */
function is_get(): bool {
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'GET';
}

/**
 * Render a template with data
 */
function render(string $template, array $data = [], string $layout = 'layout'): void {
    // Extract data to local scope
    extract($data);

    // Capture template content
    ob_start();
    $templateFile = TEMPLATES_PATH . '/' . $template . '.php';
    if (!file_exists($templateFile)) {
        // Try module templates
        $templateFile = MODULES_PATH . '/' . str_replace('.', '/', $template) . '.php';
    }

    if (file_exists($templateFile)) {
        include $templateFile;
    } else {
        echo "Template not found: $template";
    }
    $content = ob_get_clean();

    // Render within layout
    if ($layout) {
        $layoutFile = TEMPLATES_PATH . '/' . $layout . '.php';
        if (file_exists($layoutFile)) {
            include $layoutFile;
        } else {
            echo $content;
        }
    } else {
        echo $content;
    }
}

/**
 * Render partial template
 */
function partial(string $name, array $data = []): void {
    extract($data);
    $file = TEMPLATES_PATH . '/partials/' . $name . '.php';
    if (file_exists($file)) {
        include $file;
    }
}

/**
 * Render module view
 */
function module_view(string $module, string $view, array $data = [], string $layout = 'layout'): void {
    extract($data);

    ob_start();
    $viewFile = MODULES_PATH . '/' . $module . '/views/' . $view . '.php';
    if (file_exists($viewFile)) {
        include $viewFile;
    } else {
        echo "View not found: $module/$view";
    }
    $content = ob_get_clean();

    if ($layout) {
        $page_title = $data['page_title'] ?? ucfirst($module);
        include TEMPLATES_PATH . '/' . $layout . '.php';
    } else {
        echo $content;
    }
}

<?php
require_once __DIR__ . '/utils/env_loader.php';

try {
    // Carregar o .env
    loadEnv(__DIR__ . '/.env');

    // Configurações do Webhook
    $secret = getenv('WEBHOOK_SECRET'); // Segredo do Webhook
    $repo_dir = getenv('REPO_DIR');     // Caminho do repositório
    $branch = getenv('REPO_BRANCH') ?: 'main'; // Branch (default: main)
    $log_file = getenv('WEBHOOK_LOG_FILE') ?: __DIR__ . '/webhook.log'; // Arquivo de log

    // Validar configurações obrigatórias
    if (!$secret || !$repo_dir) {
        throw new Exception('WEBHOOK_SECRET e REPO_DIR são obrigatórios no .env');
    }

    // Lê o payload do Webhook
    $payload = file_get_contents('php://input');
    $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

    // Valida a assinatura
    $hash = 'sha256=' . hash_hmac('sha256', $payload, $secret);
    if (!hash_equals($hash, $signature)) {
        http_response_code(403);
        throw new Exception('Assinatura inválida');
    }

    // Executa o comando git pull
    $cmd = sprintf('cd %s && git pull origin %s 2>&1', escapeshellarg($repo_dir), escapeshellarg($branch));
    exec($cmd, $output, $return_var);

    // Log detalhado
    $log_message = sprintf(
        "[%s] Git pull executado: %s\nSaída: %s\n",
        date('Y-m-d H:i:s'),
        $return_var === 0 ? 'Sucesso' : 'Erro',
        implode("\n", $output)
    );
    file_put_contents($log_file, $log_message, FILE_APPEND);

    // Retorno HTTP
    if ($return_var !== 0) {
        http_response_code(500);
        echo "Erro ao executar git pull: " . implode("\n", $output);
        exit;
    }

    echo "Git pull executado com sucesso!";
} catch (Exception $e) {
    // Log de erro
    file_put_contents(
        $log_file,
        sprintf("[%s] Erro: %s\n", date('Y-m-d H:i:s'), $e->getMessage()),
        FILE_APPEND
    );

    // Retorna erro HTTP
    http_response_code(500);
    echo $e->getMessage();
    exit;
}

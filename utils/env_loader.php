<?php
/**
 * Carrega o arquivo .env e popula as variáveis de ambiente.
 *
 * @param string $file Caminho para o arquivo .env
 * @throws Exception Se o arquivo não for encontrado ou não puder ser carregado.
 */
function loadEnv($file = '.env') {
    if (!file_exists($file)) {
        throw new Exception(".env file not found: $file");
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // Ignora comentários
        }

        list($key, $value) = explode('=', $line, 2);
        putenv(trim($key) . '=' . trim($value)); // Define a variável no ambiente
    }
}

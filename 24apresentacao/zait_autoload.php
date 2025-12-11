<?php
/** ============================================================================
 *  ZaitTiny Framework - Autoload Recursivo
 *  ----------------------------------------------------------------------------
 *  Autor......: Prof. Dr. Cleber S. Oliveira
 *  Local......: /autoload.php
 *  Propósito..: Carregar automaticamente QUALQUER classe dentro da pasta /app,
 *               percorrendo todas as subpastas recursivamente.
 * ============================================================================ */

function zait_autoload(string $className): void {
    if (strpos($className, 'app\\') !== 0) {
        return;
    }
    $classBaseName = basename(str_replace('\\', '/', $className));
    $extensions = ['.php', '.class.php'];
    $appDir = __DIR__ . '/app';
    if (!is_dir($appDir)) {
        die("Erro fatal: diretório 'app/' não encontrado no projeto.");
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($appDir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        foreach ($extensions as $ext) {
            if ($file->getFilename() === $classBaseName . $ext) {
                require_once $file->getPathname();
                return;
            }
        }
    }
}
spl_autoload_register('zait_autoload');
<?php
require_once __DIR__ . '/../vendor/autoload.php';

echo '<pre>';

$dirs = [
    'sys_get_temp_dir()' => sys_get_temp_dir(),
    'upload_tmp_dir'     => ini_get('upload_tmp_dir') ?: sys_get_temp_dir(),
    'C:\xampp\tmp'       => 'C:\xampp\tmp',
];

foreach ($dirs as $label => $dir) {
    echo "=== Testing in: $label ($dir) ===" . PHP_EOL;

    $zipPath = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . 'test_zip_' . uniqid() . '.zip';
    $created = false;

    try {
        $zip = new ZipArchive();
        $res = $zip->open($zipPath, ZipArchive::CREATE);
        if ($res === true) {
            $zip->addFromString('_rels/.rels', '<hello/>');
            $zip->close();
            $created = true;
            echo "  Created ZIP: $zipPath" . PHP_EOL;
        } else {
            echo "  ZipArchive::open() returned: $res" . PHP_EOL;
        }
    } catch (Throwable $e) {
        echo '  Create ZIP ERROR: ' . get_class($e) . ': ' . $e->getMessage() . PHP_EOL;
    }

    if ($created) {
        try {
            $result = \PhpOffice\PhpSpreadsheet\Shared\File::fileExists('zip://' . $zipPath . '#_rels/.rels');
            echo "  fileExists: " . var_export($result, true) . PHP_EOL;
        } catch (Throwable $e) {
            echo '  fileExists ERROR: ' . get_class($e) . ': ' . $e->getMessage() . PHP_EOL;
            echo '  at: ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL;
        }

        try {
            \PhpOffice\PhpSpreadsheet\Shared\File::assertFile($zipPath, '_rels/.rels');
            echo "  assertFile: OK" . PHP_EOL;
        } catch (Throwable $e) {
            echo '  assertFile ERROR: ' . get_class($e) . ': ' . $e->getMessage() . PHP_EOL;
            echo '  at: ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL;
        }

        @unlink($zipPath);
    }
    echo PHP_EOL;
}

echo '</pre>';

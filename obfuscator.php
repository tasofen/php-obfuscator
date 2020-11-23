#!/usr/bin/env php
<?php

$obfuscatorPath = __DIR__.'/yakpro-po/yakpro-po.php';
if (!is_file($obfuscatorPath)) {
    $workDir = getcwd();
    exec('git clone https://github.com/pk-fr/yakpro-po '.__DIR__.'/yakpro-po');
    chdir(__DIR__.'/yakpro-po');
    exec('git clone https://github.com/nikic/PHP-Parser.git');
    chdir($workDir);
}

if (!is_file($obfuscatorPath)) {
    exit("Yakpro is not installer");
}


function getFiles($path) {
    if (is_file($path)) {
        return [$path];
    }

    $path = rtrim($path, '/');

    $res = [];

    $files = scandir($path);
    
    foreach($files as $file) {
        if ($file == '.' || $file == '..') {
            continue;
        }

        $filePath = $path.'/'.$file;

        if (is_file($filePath) && strtolower(substr($filePath, -4)) == '.php') {
            $res[] = $filePath;
        } else if(is_dir($filePath)) {
            $res = array_merge($res, getFiles($filePath));
        }
    }

    return $res;
}


$files = [];
$use = false;

foreach($argv as $item) {
    if ($item == '-i') {
        $use = true;
        continue;
    }

    if ($use) {
        $path = getcwd().'/'.$item;
        $files = array_merge($files, getFiles($path));
    }
}

$files = array_unique($files);

if (empty($files)) {
    exit('Error path');
}


$params = [
    // '--no-strip-indentation',
    // '--no-shuffle-statements',
    // '--no-obfuscate-string-literal',
    // '--no-obfuscate-loop-statement',
    // '--no-obfuscate-if-statement',
    '--no-obfuscate-constant-name',
    '--no-obfuscate-variable-name',
    '--no-obfuscate-function-name',
    '--no-obfuscate-class_constant-name',
    '--no-obfuscate-class-name',
    '--no-obfuscate-interface-name',
    '--no-obfuscate-trait-name',
    '--no-obfuscate-property-name',
    '--no-obfuscate-method-name',
    '--no-obfuscate-namespace-name',
    // '--no-obfuscate-label-name',
];

$params = implode(' ', $params);
$tmpFile = '/tmp/'.sha1(uniqid());

foreach($files as $file) {
    $cmd = __DIR__."/yakpro-po/yakpro-po.php {$params} {$file} -o {$tmpFile}";
    exec($cmd);
    $data = file_get_contents($tmpFile);
    $pos = strpos($data, '*/');
    $data = '<?php '.substr($data, $pos+2);
    file_put_contents($file, $data, LOCK_EX);
}

unlink($tmpFile);
#!/usr/bin/env php
<?php

$obfuscatorPath = __DIR__.'/yakpro-po/yakpro-po.php';
if (!is_file($obfuscatorPath)) {
    $workDir = getcwd();
    chdir(__DIR__);
    exec('git clone https://github.com/pk-fr/yakpro-po');
    chdir(__DIR__.'/yakpro-po');
    exec('git clone https://github.com/nikic/PHP-Parser.git');
    chdir($workDir);
}

if (!is_file($obfuscatorPath)) {
    exit("Yakpro is not installer\n");
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
    exit("Error path\n");
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
    '--scramble-mode hexa',
    '--scramble-length 32',
    // '--no-obfuscate-label-name',
];


$gotoTags = [];
$params = implode(' ', $params);
$tmpFile = '/tmp/'.sha1(uniqid());

foreach($files as $file) {
    $cmd = __DIR__."/yakpro-po/yakpro-po.php {$params} {$file} -o {$tmpFile}";
    exec($cmd);
    $data = file_get_contents($tmpFile);
    $pos = strpos($data, '*/');
    $data = '<?php '.substr($data, $pos+2);

    $matches = [];
    $pattern = '#goto\s+([0-9a-z_]+);#i';
    preg_match_all($pattern, $data, $matches);

    if ($matches) {
        $matches = array_unique($matches[1]);
        foreach ($matches as $tag) {
            $pos1 = strpos($data, $tag.':');
            $pos2 = strpos($data, $tag.':', $pos1+1);

            if ($pos1 && $pos2 || isset($gotoTags[$tag])) {
                exit("Error: dublicate tag\n");
            } else {
                $gotoTags[$tag] = true;
            }
        }
    }


    file_put_contents($file, $data, LOCK_EX);
}

unlink($tmpFile);
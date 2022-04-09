#!/usr/local/bin/php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Clemente\Phisp\Parser\Parser;
use Clemente\Phisp\Parser\Token;

$stdin = fopen("php://stdin", "r");
print_cursor();

while (($data = fgets($stdin)) !== false) {
    echo rep($data) . PHP_EOL;
    print_cursor();
}

function read(string $command): Token {
    return (new Parser($command))->nextNode();
}

function evaluation(Token $command): Token {
    return $command;
}

function print_code(Token $command): string {
    return (string)$command;
}

function rep(string $command): string {
    return print_code(evaluation(read($command)));
}

function print_cursor(): void {
    echo "user> ";
}

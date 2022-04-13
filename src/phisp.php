#!/usr/bin/php
<?php declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Clemente\Phisp\Parser\Parser;
use Clemente\Phisp\Parser\ParserException;
use Clemente\Phisp\Parser\Token;
use Clemente\Phisp\Evaluation\Evaluation;
use Clemente\Phisp\Evaluation\RuntimeException;

$stdin = fopen("php://stdin", "r");
print_cursor();
$evaluation = new Evaluation();

while (($data = fgets($stdin)) !== false) {
	echo rep($data, $evaluation) . PHP_EOL;
	print_cursor();
}

function read(string $command): Token {
	try {
		return (new Parser($command))->nextNode();
	} catch (ParserException $exception) {
		return $exception->toNop();
	}
}

function evaluation(Token $token, Evaluation $evaluation): Token {
	try {
		return $evaluation->evaluate($token);
	} catch (RuntimeException $exception) {
		return $exception->toNop();
	}
}

function print_code(Token $command): string {
	return (string)$command;
}

function rep(string $command, Evaluation $evaluation): string {
	return print_code(evaluation(read($command), $evaluation));
}

function print_cursor(): void {
	echo "user> ";
}

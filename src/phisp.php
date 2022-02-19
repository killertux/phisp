#!/Users/bruno.clemente/.phpbrew/php/php-8.1.3/bin/php
<?php

$stdin = fopen("php://stdin", "r");
print_cursor();

while (($data = fgets($stdin)) !== false) {
    echo rep($data);
    print_cursor();
}

function read(string $command): string
{
    return $command;
}

function evaluation(string $command): string
{
    return $command;
}

function print_code(string $command): string
{
    return $command;
}

function rep(string $command): string
{
    return print_code(evaluation(read($command)));
}

function print_cursor(): void
{
    echo "user> ";
}

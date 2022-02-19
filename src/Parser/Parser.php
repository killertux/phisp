<?php

namespace Clemente\Phisp\Parser;

class Parser
{

    private int $position = -1;

    public function __construct(
        private string $code
    ) {
    }

    public function next_node(): Token
    {
        $this->position++;
        $char = this->code[$this->position];
        return match ($char) {
            '(' => $this->parse_list(),
            default => throw new \Exception("Cannot pasrse $char at location"),
        };
    }
}

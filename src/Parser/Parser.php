<?php

namespace Clemente\Phisp\Parser;

class Parser
{

    private int $position;
    private Location $location;

    public function __construct(
        private string $code
    ) {
        $this->location = new Location(1, 0);
        $this->position = -1;
    }

    public function nextNode(): ?Token {
        $char = $this->nextValidChar();
        if ($char === '(') {
            return $this->parseList();
        } elseif (is_numeric($char)) {
            return $this->parseNumber();
        } elseif ($char === '-' && is_numeric($this->peekNext())) {
            return $this->parseNumber();
        } elseif ($char === null) {
            return null;
        } else {
            throw new \Exception("Cannot parse $char at location $this->location");
        }
    }

    private function parseList(): Token {
        $location = clone $this->location;
        $nodes = [];
        while (($char = $this->peekNextValid()) !== ')') {
            if ($char === null) {
                throw new \Exception("Unclosed list at $this->location");
            }
            $nodes[] = $this->nextNode();
        }
		assert($this->nextValidChar() === ')', 'A list should always be closed here');
		var_dump($this->location);
        return new Token(TokenType::LIST, $location, $nodes);
    }

    private function parseNumber(): Token {
        $location = clone $this->location;
        $number = $this->currentChar();
        while (is_numeric($this->peekNext())) {
            $number .= $this->nextChar();
        }
        return new Token(TokenType::NUMBER, $location, (float)$number);
    }

    private function nextChar(): ?string {
        if ($this->position + 1 !== strlen($this->code) - 1) {
            $this->position++;
            $next_char = $this->code[$this->position];
            if ($next_char == "/n") {
                $this->location->column = 1;
                $this->location->row++;
            } else {
                $this->location->column++;
            }
            return $next_char;
        }
        return null;
    }

	private function nextValidChar(): ?string {
		$next = $this->nextChar();
		if (ctype_space($next)) {
			return $this->nextValidChar();
		}
		return $next;
	}

    private function currentChar(): string {
        return $this->code[$this->position];
    }

    private function peekNext(int $position = null): ?string {
        $position ??= $this->position;
        if ($position + 1 !== strlen($this->code) - 1) {
            return $this->code[$position + 1];
        }
        return null;
    }

    private function peekNextValid(int $position = null): ?string {
		$position ??= $this->position;
        $next = $this->peekNext($position);
        if (ctype_space($next)) {
            return $this->peekNextValid($position + 1);
        }
        return $next;
    }
}

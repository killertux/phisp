<?php declare(strict_types=1);

namespace Clemente\Phisp\Parser;

enum TokenType: string
{
	case LIST = 'list';
	case VECTOR = 'vector';
	case HASHMAP = 'hasmap';
	case NUMBER = 'number';
	case SYMBOL = 'symbol';
	case KEYWORD = 'keyword';
	case BOOL = 'bool';
	case NIL = 'nil';
	case STRING = 'string';
	case NOP = 'nop';
	case FUNCTION = 'function';
}

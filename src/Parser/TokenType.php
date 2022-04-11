<?php declare(strict_types=1);

namespace Clemente\Phisp\Parser;

enum TokenType
{
	case LIST;
	case VECTOR;
	case HASHMAP;
	case NUMBER;
	case SYMBOL;
	case KEYWORD;
	case BOOL;
	case NIL;
	case STRING;
	case NOP;
}

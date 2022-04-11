<?php

namespace Clemente\Phisp\Parser;

enum TokenType
{
	case LIST;
	case NUMBER;
	case SYMBOL;
	case BOOL;
	case NIL;
	case STRING;
}

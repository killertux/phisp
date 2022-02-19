<?php

namespace Clemente\Phisp\Parser;

class Location
{
    public function __construct(
        public int $row,
        public int $column,
    ) {
    }
}

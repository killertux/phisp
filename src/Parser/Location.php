<?php declare(strict_types=1);

namespace Clemente\Phisp\Parser;

class Location
{
	public function __construct(
		public int $row,
		public int $column,
	) {
	}

	public function __toString(): string {
		return "$this->row:$this->column";
	}
}

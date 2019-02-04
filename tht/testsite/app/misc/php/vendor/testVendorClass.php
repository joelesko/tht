<?php

namespace Abc;

class VendorClass {
	
	var $ALL_CAP_FIELD = 123;

	function takeArray ($ary) {
		return array_shift($ary);
	}

	function returnArray () {
		return ['a', 'b', 'c'];
	}

	function takeMap ($ary) {
		return $ary['red'];
	}

	function returnMap () {
		return [ 'id' => 123, 'color' => 'Red' ];
	}

	function returnObject() {
		return new VendorSubClass ();
	}

	function returnRecords () {

		return [
			[ 'id' => 123, 'color' => 'Red'   ],
			[ 'id' => 124, 'color' => 'Green' ],
			[ 'id' => 125, 'color' => 'Blue'  ],
		];
	}

	function ALL_CAP_METHOD() {
		return 'FOO';
	}

}

class VendorSubClass {

	function callMe() {
		return 'abc';
	}

}

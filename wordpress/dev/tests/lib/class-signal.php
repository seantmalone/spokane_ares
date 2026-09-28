<?php
/**
 * DEV ONLY: the one exception the PHP test framework throws. A failed or
 * skipped assertion, and every wp_redirect() and wp_die() while a test runs,
 * becomes a Signal, so a save handler's redirect-then-exit or wp_die() ends
 * the handler instead of the request.
 *
 * @package spokares-dev
 */

namespace Spokares\DevTests;

defined( 'ABSPATH' ) || exit;

/**
 * Kinds: 'fail', 'skip', 'redirect' (message = location, data.status) and
 * 'die' (message = the wp_die() text, data.status = its HTTP status).
 */
class Signal extends \Exception {

	/**
	 * What happened: fail, skip, redirect or die.
	 *
	 * @var string
	 */
	public $kind;

	/**
	 * Extra data (status for redirect and die).
	 *
	 * @var array
	 */
	public $data;

	/**
	 * Where in a *-test.php file the signal came from ("file.php:12"), if any.
	 *
	 * @var string
	 */
	public $where = '';

	/**
	 * Constructor.
	 *
	 * @param string $kind    fail, skip, redirect or die.
	 * @param string $message Message, location or wp_die() text.
	 * @param array  $data    Extra data.
	 */
	public function __construct( string $kind, string $message, array $data = array() ) {
		parent::__construct( $message );
		$this->kind  = $kind;
		$this->data  = $data;
		$this->where = test_frame( $this->getTrace() );
	}
}

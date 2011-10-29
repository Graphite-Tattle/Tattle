<?php
/**
 * Prints pagination links for fRecordSet or other paginated records
 * 
 * @copyright  Copyright (c) 2010-2011 Will Bond
 * @author     Will Bond [wb] <will@flourishlib.com>
 * @license    http://flourishlib.com/license
 * 
 * @package    Flourish
 * @link       http://flourishlib.com/fActiveRecord
 *
 * @version 1.0.0b
 * @changes 1.0.0b    Added the `prev_disabled` and `next_disabled` pieces [wb, 2011-09-06]
 */
class fPagination
{
	// The following constants allow for nice looking callbacks to static methods
	const defineTemplate     = 'fPagination::defineTemplate';
	const extend             = 'fPagination::extend';
	const printRecordSetInfo = 'fPagination::printRecordSetInfo';
	const reset              = 'fPagination::reset';
	const showRecordSetLinks = 'fPagination::showRecordSetLinks';
	
	
	/**
	 * The available filters to use in templates
	 * 
	 * @var array
	 */
	static private $filters = array(
		'inflect',
		'lower',
		'url_encode',
		'humanize'
	);
	
	/**
	 * The available templates to use for a paginator
	 * 
	 * @var array
	 */
	static private $templates = array(
		'default' => array(
			'type'   => 'without_first_last',
			'size'   => 4,
			'pieces' => array(
				'info'          => '<div class="paginator_info">Page {{ page }} of {{ total_records }} items</div>',
				'start'         => '<div class="paginator_list"><ul>',
				'prev'          => '<li class="prev"><a href="{{ url }}">Prev</a></li>',
				'prev_disabled' => '',
				'page'          => '<li class="page {{ first }} {{ last }} {{ current }}"><a href="{{ url }}">{{ page }}</a></li>',
				'next'          => '<li class="next"><a href="{{ url }}">Next</a></li>',
				'next_disabled' => '',
				'end'           => '</ul></div>'
			)
		)
	);
	
	
	/**
	 * Defines a new template to use with the paginator
	 * 
	 * The `$pieces` array must contain the following array keys:
	 * 
	 *  - `info`:          the template to use when calling the `printInfo()` method
	 *  - `start`:         the template to start the page list with
	 *  - `prev`:          the template for the previous link
	 *  - `prev_disabled`: the template for the previous link, when disabled
	 *  - `page`:          the template for a single page link
	 *  - `separator`:     the template for the separator to use when the type is `with_first_last`
	 *  - `next`:          the template for the next link
	 *  - `next_disabled`: the template for the next link, when disabled
	 *  - `end`:           the template to end the page list with
	 * 
	 * There are various pre-defined variables available for use in the template
	 * pieces. These variables are printed by using the syntax `{{ variable }}`.
	 * 
	 * The `info`, `start` and `end` pieces may use the following variables:
	 * 
	 *  - `page`:          the page of records being shown
	 *  - `total_pages`:   the total number of pages of records
	 *  - `first_record`:  the record number of the first record being shown
	 *  - `last_record`:   the record number of the last record being shown
	 *  - `total_records`: the total number of records being paginated
	 * 
	 * The `prev` and `next` pieces may use the following variables:
	 * 
	 *  - `page`: the page number of the page of results being linked to
	 *  - `url`:  the URL of the page being linked to
	 * 
	 * The `page` piece may use the following variables:
	 * 
	 *  - `page`:    the page number of the page being linked to
	 *  - `url`:     the URL of the page being linked to
	 *  - `first`:   the string "first" if the link is to the first page
	 *  - `last`:    the string "last" if the link is to the last page
	 *  - `current`: the string "current" if the link is to the current page
	 * 
	 * The `separator` piece does not have access to any pre-defined variables.
	 * 
	 * In addition to the pre-defined variables, it is possible to add any other
	 * variables to be used in any of the pieces by calling the instance method
	 * ::set().
	 * 
	 * It is possible to use variable filters on a variable to modify it. The
	 * most common variable to filter would be `name`. To filter a variable,
	 * add a `|` and the filter name after the variable name, in the form
	 * `{{ variable|filter }}`. The following filters are available:
	 * 
	 *  - `inflect`:    if the total number of records is not 1, pluralize the variable - this only works for nouns
	 *  - `lower`:      converts the contents of the variable to lower case
	 *  - `url_encode`: encode the value for inclusion in a URL
	 *  - `humanize`:   converts a `underscore_notation` or `CamelCase` string to a string with spaces between words and in `Title Caps`
	 * 
	 * Filters can be combined, in which case they are list one after the other
	 * in the form `{{ variable|filter_1|filter_2 }}`.
	 * 
	 * @param string  $name    The name of the template
	 * @param string  $type    The type of pagination: `without_first_last` or `with_first_last` - `with_first_last` always includes links to the first and last pages
	 * @param integer $size    The number of pages to show on either side of the current page
	 * @param array   $pieces  The chunks of HTML to create the paginator from - see method description for details
	 * @return void
	 */
	static public function defineTemplate($name, $type, $size, $pieces)
	{
		$valid_types = array('without_first_last', 'with_first_last');
		if (!in_array($type, $valid_types)) {
			throw new fProgrammerException(
				'The type specified, %1$s, is invalid. Must be one of: %2$s.',
				$type,
				join(', ', $valid_types)
			);
		}
		
		if (!preg_match('#^\d+$#D', $size)) {
			throw new fProgrammerException(
				'The size specified, %1$s, is not a positive integer',
				$size
			);
		}
		
		if ($type == 'with_first_last' && $size < 3) {
			throw new fProgrammerException(
				'The size specified, %1$s, is less than %2$s, which is the minimum size for the type %3$s',
				$size,
				3,
				'with_first_last'
			);
		}
		if ($type == 'without_first_last' && $size < 1) {
			throw new fProgrammerException(
				'The size specified, %1$s, is less than %2$s, which is the minimum size for the type %3$s',
				$size,
				1,
				'without_first_last'
			);
		}
		
		$required_pieces = array('info', 'start', 'prev', 'prev_disabled', 'page');
		if ($type == 'with_first_last') {
			$required_pieces[] = 'separator';
		}
		$required_pieces = array_merge($required_pieces, array('next', 'next_disabled', 'end'));
		if (array_keys($pieces) != $required_pieces) {
			throw new fProgrammerException(
				'The pieces specified, %1$s, do not correspond to the pieces required by the type %2$s: %3$s.',
				join(' ', array_keys($pieces)),
				$type,
				join(' ', $required_pieces) 
			);
		}
		
		self::$templates[$name] = array(
			'type'   => $type,
			'size'   => $size,
			'pieces' => $pieces
		);
	}
	
	
	/**
	 * Adds the methods `printInfo()` and `showLinks()` to fRecordSet
	 * 
	 * @return void
	 */
	static public function extend()
	{
		fORM::registerRecordSetMethod('printInfo', self::printRecordSetInfo);
		fORM::registerRecordSetMethod('showLinks', self::showRecordSetLinks);
	}


	/**
	 * Overlays user data over info from the record set
	 *
	 * @param array        $data   The user data
	 * @param string|array $class  The class or classes present in the record set
	 * @return array  The merged data
	 */
	static private function extendRecordSetInfo($data, $class)
	{
		if (is_array($class)) {
			$record_name = array_map(array('fORM', 'getRecordName'), $class);
		} else {
			$record_name = fORM::getRecordName($class);
		}
		return array_merge(
			array(
				'class' => $class,
				'record_name' => $record_name
			),
			$data
		);
	}
	
	
	/**
	 * Handles the `printInfo()` method for fRecordSet
	 * 
	 * @internal
	 * 
	 * @param fRecordSet   $object       The record set
	 * @param string|array $class        The class(es) contained in the record set
	 * @param array        &$records     The records
	 * @param string       $method_name  The method that was called
	 * @param array        $parameters   The parameters passed to the method
	 * @return void
	 */
	static public function printRecordSetInfo($object, $class, &$records, $method_name, $parameters)
	{
		$template = count($parameters) < 1 ? 'default' : $parameters[0];
		$data     = count($parameters) < 2 ? array() : $parameters[1];
		$data     = self::extendRecordSetInfo($data, $class);
		self::printTemplatedInfo($template, $data, $object->getPage(), $object->getLimit(), $object->count(TRUE));
	}
	
	
	/**
	 * Prints the info for the displayed records
	 * 
	 * @param string  $template       The template to use
	 * @param array   $data           The extra data to make available to the template
	 * @param integer $page           The page of records being displayed
	 * @param integer $per_page       The number of records being displayed on each page
	 * @param integer $total_records  The total number of records
	 * @return void
	 */
	static private function printTemplatedInfo($template, $data, $page, $per_page, $total_records)
	{
		$total_pages = ceil($total_records/$per_page);
		
		self::printPiece(
			$template,
			'info',
			array_merge(
				array(
					'page'          => $page,
					'total_pages'   => $total_pages,
					'first_record'  => (($page - 1) * $per_page) + 1,
					'last_record'   => min($page * $per_page, $total_records),
					'total_records' => $total_records
				),
				$data
			)
		);
	}
	
	
	/**
	 * Resets the configuration of the class
	 * 
	 * @internal
	 * 
	 * @return void
	 */
	static public function reset()
	{
		self::$filters = array(
			'inflect',
			'lower',
			'url_encode',
			'humanize'
		);
		self::$templates = array(
			'default' => array(
				'type'   => 'without_first_last',
				'size'   => 4,
				'pieces' => array(
					'info'          => '<div class="paginator_info">Page {{ page }} of {{ total_records }} items</div>',
					'start'         => '<div class="paginator_list"><ul>',
					'prev'          => '<li class="prev"><a href="{{ url }}">Prev</a></li>',
					'prev_disabled' => '',
					'page'          => '<li class="page {{ first }} {{ last }} {{ current }}"><a href="{{ url }}">{{ page }}</a></li>',
					'next'          => '<li class="next"><a href="{{ url }}">Next</a></li>',
					'next_disabled' => '',
					'end'           => '</ul></div>'
				)
			)
		);
	}
	
	
	/**
	 * Handles the `showLinks()` method for fRecordSet
	 * 
	 * @internal
	 * 
	 * @param fRecordSet   $object       The record set
	 * @param string|array $class        The class(es) contained in the record set
	 * @param array        &$records     The records
	 * @param string       $method_name  The method that was called
	 * @param array        $parameters   The parameters passed to the method
	 * @return boolean  If the links were shown
	 */
	static public function showRecordSetLinks($object, $class, &$records, $method_name, $parameters)
	{
		$template = count($parameters) < 1 ? 'default' : $parameters[0];
		$data     = count($parameters) < 2 ? array() : $parameters[1];
		$data     = self::extendRecordSetInfo($data, $class);
		return self::showTemplatedLinks($template, $data, $object->getPage(), $object->getLimit(), $object->count(TRUE));
	}
	
	
	/**
	 * Prints the links for a set of records
	 * 
	 * @param string  $template       The template to use
	 * @param array   $data           The extra data to make available to the template
	 * @param integer $page           The page of records being displayed
	 * @param integer $per_page       The number of records being displayed on each page
	 * @param integer $total_records  The total number of records
	 * @return void
	 */
	static private function showTemplatedLinks($template, $data, $page, $per_page, $total_records)
	{
		if ($total_records <= $per_page) {
			return FALSE;
		}
		
		$total_pages = ceil($total_records/$per_page);
		
		self::printPiece(
			$template,
			'start',
			array_merge(
				array(
					'page'          => $page,
					'total_pages'   => $total_pages,
					'first_record'  => (($page - 1) * $per_page) + 1,
					'last_record'   => min($page * $per_page, $total_records),
					'total_records' => $total_records
				),
				$data
			)
		);
		if ($page > 1) {
			self::printPiece(
				$template,
				'prev',
				array_merge(
					array(
						'page' => $page - 1,
						'url'  => fURL::replaceInQueryString('page', $page - 1)
					),
					$data
				)
			);
		} else {
			self::printPiece(
				$template,
				'prev_disabled',
				$data
			);
		}
		
		if (self::$templates[$template]['type'] == 'without_first_last') {
			$start_page = max(1, $page - self::$templates[$template]['size']);
			$end_page   = min($total_pages, $page + self::$templates[$template]['size']);			
		
		} else {
			$start_separator = TRUE;
			$start_page      = $page - (self::$templates[$template]['size'] - 2);
			if ($start_page <= 2) {
				$start_separator = FALSE;
				$start_page = 1;
			}
			$end_separator = TRUE;
			$end_page      = $page + (self::$templates[$template]['size'] - 2);
			if ($end_page >= $total_pages - 1) {
				$end_separator = FALSE;
				$end_page = $total_pages;
			}
		}
		
		if (self::$templates[$template]['type'] == 'with_first_last' && $start_separator) {
			self::printPiece(
				$template,
				'page',
				array_merge(
					array(
						'page'    => 1,
						'url'     => fURL::replaceInQueryString('page', 1),
						'first'   => 'first',
						'last'    => '',
						'current' => ''
					),
					$data
				)
			);
			self::printPiece(
				$template,
				'separator',
				$data
			);
		}
		for ($loop_page = $start_page; $loop_page <= $end_page; $loop_page++) {
			self::printPiece(
				$template,
				'page',
				array_merge(
					array(
						'page'    => $loop_page,
						'url'     => fURL::replaceInQueryString('page', $loop_page),
						'first'   => ($loop_page == 1) ? 'first' : '',
						'last'    => ($loop_page == $total_pages) ? 'last' : '',
						'current' => ($loop_page == $page) ? 'current' : ''
					),
					$data
				)
			);
		}
		if (self::$templates[$template]['type'] == 'with_first_last' && $end_separator) {
			self::printPiece(
				$template,
				'separator',
				$data
			);
			self::printPiece(
				$template,
				'page',
				array_merge(
					array(
						'page'    => $total_pages,
						'url'     => fURL::replaceInQueryString('page', $total_pages),
						'first'   => '',
						'last'    => 'last',
						'current' => ''
					),
					$data
				)
			);
		}
		
		if ($page < $total_pages) {
			self::printPiece(
				$template,
				'next',
				array_merge(
					array(
						'page' => $page + 1,
						'url'  => fURL::replaceInQueryString('page', $page + 1)
					),
					$data
				)
			);
		} else {
			self::printPiece(
				$template,
				'next_disabled',
				$data
			);
		}
		self::printPiece(
			$template,
			'end',
			array_merge(
				array(
					'page'          => $page,
					'total_pages'   => $total_pages,
					'first_record'  => (($page - 1) * $per_page) + 1,
					'last_record'   => min($page * $per_page, $total_records),
					'total_records' => $total_records
				),
				$data
			)
		);
		
		return TRUE;
	}
	
	
	/**
	 * Prints out a piece of a template
	 * 
	 * @param string $template  The name of the template to print
	 * @param string $piece     The piece of the template to print
	 * @param array  $data      The data to replace the variables with
	 * @return void
	 */
	static private function printPiece($template, $name, $data)
	{
		if (!isset(self::$templates[$template]['pieces'][$name])) {
			throw new fProgrammerException(
				'The template piece, %s, was not specified when defining the %s template',
				$name,
				$template
			);
		}
		$piece = self::$templates[$template]['pieces'][$name];
		preg_match_all('#\{\{ (\w+)((?:\|\w+)+)? \}\}#', $piece, $matches, PREG_SET_ORDER);
		foreach ($matches as $match) {
			$variable = $match[1];
			$value    = (!isset($data[$variable])) ? NULL : $data[$variable];
			if (isset($match[2])) {
				$filters  = array_slice(explode('|', $match[2]), 1);
				foreach ($filters as $filter) {
					if (!in_array($filter, self::$filters)) {
						throw new fProgrammerException(
							'The filter specified, %1$s, is invalid. Must be one of: %2$s.',
							$filter,
							join(', ', self::$filters)
						);
					}
					if (!strlen($value)) {
						continue;
					}
					if ($filter == 'inflect') {
						$value = fGrammar::inflectOnQuantity($data['total_records'], $value);
					} elseif ($filter == 'lower') {
						$value = fUTF8::lower($value);
					} elseif ($filter == 'url_encode') {
						$value = urlencode($value);
					} elseif ($filter == 'humanize') {
						$value = fGrammar::humanize($value);
					}
				}
			}
			$piece = preg_replace('#' . preg_quote($match[0], '#') . '#', fHTML::encode($value), $piece, 1);
		}
		echo $piece;
	}
	
	
	/**
	 * Extra data for the templates
	 * 
	 * @var array
	 */
	private $data;
	
	/**
	 * The page number
	 * 
	 * @var integer
	 */
	private $page;
	
	/**
	 * The number of records per page
	 * 
	 * @var integer
	 */
	private $per_page;
	
	/**
	 * The total number of records
	 * 
	 * @var integer
	 */
	private $total_records;
	
	
	/**
	 * Accepts the record information necessary for printing pagination
	 * 
	 * @throws fValidationException   When the `$page` is less than 1 or not an integer
	 * @throws fNoRemainingException  When there are not records for the specified `$page` and `$page` is greater than 1
	 * 
	 * @param  integer    $records   The total number of records
	 * @param  integer    $per_page  The number of records per page
	 * @param  integer    $page      The page number
	 * @param  fRecordSet :$records  The records to create the paginator for
	 * @return fPaginator
	 */
	public function __construct($records, $per_page=NULL, $page=NULL)
	{
		if ($records instanceof fRecordSet) {
			$this->total_records = $records->count(TRUE);
			$this->per_page      = $per_page === NULL ? $records->getLimit() : $per_page;
			$this->page          = $page === NULL ? $records->getPage() : $page;
		
		} else {
			$this->total_records = $records;
			
			if ($per_page === NULL) {
				throw new fProgrammerException(
					'No value was specified for the parameter %s',
					'$per_page'
				);
			}
			$this->per_page = $per_page;
			
			if ($page === NULL) {
				throw new fProgrammerException(
					'No value was specified for the parameter %s',
					'$page'
				);
			}
			$this->page = $page;
		}
		
		if (!preg_match('#^\d+$#D', $this->page) || $this->page < 1) {
			throw new fValidationException(
				'The page specified, %1$s, is not a whole number, or less than one',
				$this->page
			);
		}
		
		if ($this->page > 1 && ($this->per_page * ($this->page - 1)) + 1 > $this->total_records) {
			throw new fNoRemainingException(
				'There are no remaining records to display',
				$this->page
			);
		}
		
		$this->data = array();
	}
	
	
	/**
	 * Prints which records are showing on the current page
	 * 
	 * @param  string $template  The template to use
	 * @return void
	 */
	public function printInfo($template='default')
	{
		self::printTemplatedInfo($template, $this->data, $this->page, $this->per_page, $this->total_records);
	}
	
	
	/**
	 * Sets data to be available to the templates
	 * 
	 * @param string $key    The key to set
	 * @param mixed  $value  The value to set
	 * @param array  :$data  An associative array of keys and values
	 * @return void
	 */
	public function set($key, $value=NULL)
	{
		if (is_array($key)) {
			$this->data = array_merge($this->data, $key);
		} else {
			$this->data[$key] = $value;
		}
	}
	
	
	/**
	 * Shows links to other pages when more than one page of records exists
	 * 
	 * @param  string $template  The template to use
	 * @return boolean  If link were printed
	 */
	public function showLinks($template='default')
	{
		return self::showTemplatedLinks($template, $this->data, $this->page, $this->per_page, $this->total_records);
	}
}


/**
 * Copyright (c) 2010-2011 Will Bond <will@flourishlib.com>
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
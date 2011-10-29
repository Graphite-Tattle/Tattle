<?php
/**
 * An exception that should probably not be handled by the display code, fCore::enableExceptionHandler() is recommended
 * 
 * @copyright  Copyright (c) 2007-2011 Will Bond
 * @author     Will Bond [wb] <will@flourishlib.com>
 * @license    http://flourishlib.com/license
 * 
 * @package    Flourish
 * @link       http://flourishlib.com/fUnexpectedException
 * 
 * @version    1.0.0b2
 * @changes    1.0.0b2  Updated ::printMessage() to use an ASCII dash to prevent encoding issues when an output encoding is not specified [wb, 2011-05-09]
 * @changes    1.0.0b   The initial implementation [wb, 2007-06-14]
 */
class fUnexpectedException extends fException
{
	/**
	 * Prints out a generic error message inside of a `div` with the class being `'exception {exception_class_name}'`
	 * 
	 * @return void
	 */
	public function printMessage()
	{
		echo '<div class="exception ' . $this->getCSSClass() . '"><p>';
		echo self::compose(
			'It appears an error has occurred - we apologize for the inconvenience. The problem may be resolved if you try again.'
		);
		echo '</p></div>';
	}
}



/**
 * Copyright (c) 2007-2011 Will Bond <will@flourishlib.com>
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
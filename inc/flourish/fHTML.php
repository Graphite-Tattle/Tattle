<?php
/**
 * Provides HTML-related methods
 * 
 * This class is implemented to use the UTF-8 character encoding. Please see
 * http://flourishlib.com/docs/UTF-8 for more information.
 * 
 * @copyright  Copyright (c) 2007-2010 Will Bond
 * @author     Will Bond [wb] <will@flourishlib.com>
 * @license    http://flourishlib.com/license
 * 
 * @package    Flourish
 * @link       http://flourishlib.com/fHTML
 * 
 * @version    1.0.0b8
 * @changes    1.0.0b8  Changed ::encode() and ::prepare() to handle arrays of strings [wb, 2010-05-19]
 * @changes    1.0.0b7  Fixed a bug where some conditional comments were causing the regex in ::prepare() to break [wb, 2009-11-04]
 * @changes    1.0.0b6  Updated ::showChecked() to require strict equality if one parameter is `NULL` [wb, 2009-06-02]
 * @changes    1.0.0b5  Fixed ::prepare() so it does not encode multi-line HTML comments [wb, 2009-05-09]
 * @changes    1.0.0b4  Added methods ::printOption() and ::showChecked() that were in fCRUD [wb, 2009-05-08]
 * @changes    1.0.0b3  Fixed a bug where ::makeLinks() would double-link some URLs [wb, 2009-01-08]
 * @changes    1.0.0b2  Fixed a bug where ::makeLinks() would create links out of URLs in HTML tags [wb, 2008-12-05]
 * @changes    1.0.0b   The initial implementation [wb, 2007-09-25]
 */
class fHTML
{
	// The following constants allow for nice looking callbacks to static methods
	const containsBlockLevelHTML = 'fHTML::containsBlockLevelHTML';
	const convertNewlines        = 'fHTML::convertNewlines';
	const decode                 = 'fHTML::decode';
	const encode                 = 'fHTML::encode';
	const makeLinks              = 'fHTML::makeLinks';
	const prepare                = 'fHTML::prepare';
	const printOption            = 'fHTML::printOption';
	const sendHeader             = 'fHTML::sendHeader';
	const show                   = 'fHTML::show';
	const showChecked            = 'fHTML::showChecked';
	
	
	/**
	 * Checks a string of HTML for block level elements
	 * 
	 * @param  string $content  The HTML content to check
	 * @return boolean  If the content contains a block level tag
	 */
	static public function containsBlockLevelHTML($content)
	{
		static $inline_tags = '<a><abbr><acronym><b><big><br><button><cite><code><del><dfn><em><font><i><img><input><ins><kbd><label><q><s><samp><select><small><span><strike><strong><sub><sup><textarea><tt><u><var>';
		return strip_tags($content, $inline_tags) != $content;
	}
	
	
	/**
	 * Converts newlines into `br` tags as long as there aren't any block-level HTML tags present
	 * 
	 * @param  string $content  The content to display
	 * @return void
	 */
	static public function convertNewlines($content)
	{
		static $inline_tags_minus_br = '<a><abbr><acronym><b><big><button><cite><code><del><dfn><em><font><i><img><input><ins><kbd><label><q><s><samp><select><small><span><strike><strong><sub><sup><textarea><tt><u><var>';
		return (strip_tags($content, $inline_tags_minus_br) != $content) ? $content : nl2br($content);
	}
	
	
	/**
	 * Converts all HTML entities to normal characters, using UTF-8
	 * 
	 * @param  string $content  The content to decode
	 * @return string  The decoded content
	 */
	static public function decode($content)
	{
		return html_entity_decode($content, ENT_QUOTES, 'UTF-8');
	}
	
	
	/**
	 * Converts all special characters to entites, using UTF-8.
	 * 
	 * @param  string|array $content  The content to encode
	 * @return string  The encoded content
	 */
	static public function encode($content)
	{
		if (is_array($content)) {
			return array_map(array('fHTML', 'encode'), $content);
		}
		return htmlentities($content, ENT_QUOTES, 'UTF-8');
	}
	
	
	/**
	 * Takes a block of text and converts all URLs into HTML links
	 * 
	 * @param  string  $content           The content to parse for links
	 * @param  integer $link_text_length  If non-zero, all link text will be truncated to this many characters
	 * @return string  The content with all URLs converted to HTML link
	 */
	static public function makeLinks($content, $link_text_length=0)
	{
		// Find all a tags with contents, individual HTML tags and HTML comments
		$reg_exp = "/<\s*a(?:\s+[\w:]+(?:\s*=\s*(?:\"[^\"]*?\"|'[^']*?'|[^'\">\s]+))?)*\s*>.*?<\s*\/\s*a\s*>|<\s*\/?\s*[\w:]+(?:\s+[\w:]+(?:\s*=\s*(?:\"[^\"]*?\"|'[^']*?'|[^'\">\s]+))?)*\s*\/?\s*>|<\!--.*?-->/s";
		preg_match_all($reg_exp, $content, $html_matches, PREG_SET_ORDER);
		
		// Find all text
		$text_matches = preg_split($reg_exp, $content);
		
		// For each chunk of text and create the links
		foreach($text_matches as $key => $text) {
			preg_match_all(
				'~
				  \b([a-z]{3,}://[a-z0-9%\$\-_.+!*;/?:@=&\'\#,]+[a-z0-9\$\-_+!*;/?:@=&\'\#,])\b                           | # Fully URLs
				  \b(www\.(?:[a-z0-9\-]+\.)+[a-z]{2,}(?:/[a-z0-9%\$\-_.+!*;/?:@=&\'\#,]+[a-z0-9\$\-_+!*;/?:@=&\'\#,])?)\b | # www. domains
				  \b([a-z0-9\\.+\'_\\-]+@(?:[a-z0-9\\-]+\.)+[a-z]{2,})\b                                                    # email addresses
				 ~ix',
				$text,
				$matches,
				PREG_SET_ORDER
			);
			
			// For each match we find the first occurence, replace it and then
			// start from the end of that finding the next occurence. This
			// prevents double linking of matches for http://www.example.com and
			// www.example.com
			$last_pos = 0;
			foreach ($matches as $match) {
				$match_pos = strpos($text, $match[0], $last_pos);
				$length    = strlen($match[0]);
				$prefix    = '';
				
				if (!empty($match[3])) {
					$prefix = 'mailto:';
				} elseif (!empty($match[2])) {
					$prefix = 'http://';
				}
				
				$replacement  = '<a href="' . $prefix . $match[0] . '">';
				$replacement .= ($link_text_length && strlen($match[0]) > $link_text_length) ? substr($match[0], 0, $link_text_length) . "…" : $match[0];
				$replacement .= '</a>';
				
				$text = substr_replace(
					$text,
					$replacement,
					$match_pos,
					$length
				);
				
				$last_pos = $match_pos + strlen($replacement);	
			}
			
			$text_matches[$key] = $text;
		}
		
		// Merge the text and html back together
		for ($i = 0; $i < sizeof($html_matches); $i++) {
			$text_matches[$i] .= $html_matches[$i][0];
		}
		
		return implode($text_matches);
	}
	
	
	/**
	 * Prepares content for display in UTF-8 encoded HTML - allows HTML tags
	 * 
	 * @param  string|array $content  The content to prepare
	 * @return string  The encoded html
	 */
	static public function prepare($content)
	{
		if (is_array($content)) {
			return array_map(array('fHTML', 'prepare'), $content);
		}
		
		// Find all html tags, entities and comments
		$reg_exp = "/<\s*\/?\s*[\w:]+(?:\s+[\w:]+(?:\s*=\s*(?:\"[^\"]*?\"|'[^']*?'|[^'\">\s]+))?)*\s*\/?\s*>|&(?:#\d+|\w+);|<\!--.*?-->/s";
		preg_match_all($reg_exp, $content, $html_matches, PREG_SET_ORDER);
		
		// Find all text
		$text_matches = preg_split($reg_exp, $content);
		
		// For each chunk of text, make sure it is converted to entities
		foreach($text_matches as $key => $value) {
			$text_matches[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
		}
		
		// Merge the text and html back together
		for ($i = 0; $i < sizeof($html_matches); $i++) {
			$text_matches[$i] .= $html_matches[$i][0];
		}
		
		return implode($text_matches);
	}
	
	
	/**
	 * Prints an `option` tag with the provided value, using the selected value to determine if the option should be marked as selected
	 * 
	 * @param  string $text            The text to display in the option tag
	 * @param  string $value           The value for the option
	 * @param  string $selected_value  If the value is the same as this, the option will be marked as selected
	 * @return void
	 */
	static public function printOption($text, $value, $selected_value=NULL)
	{
		$selected = FALSE;
		if ($value == $selected_value || (is_array($selected_value) && in_array($value, $selected_value))) {
			$selected = TRUE;
		}
		
		echo '<option value="' . fHTML::encode($value) . '"';
		if ($selected) {
			echo ' selected="selected"';
		}
		echo '>' . fHTML::prepare($text) . '</option>';
	}
	
	
	/**
	 * Sets the proper Content-Type header for a UTF-8 HTML (or pseudo-XHTML) page
	 * 
	 * @return void
	 */
	static public function sendHeader()
	{
		header('Content-Type: text/html; charset=utf-8');
	}
	
	
	/**
	 * Prints a `p` (or `div` if the content has block-level HTML) tag with the contents and the class specified - will not print if no content
	 * 
	 * @param  string $content    The content to display
	 * @param  string $css_class  The CSS class to apply
	 * @return boolean  If the content was shown
	 */
	static public function show($content, $css_class='')
	{
		if ((!is_string($content) && !is_object($content) && !is_numeric($content)) || !strlen(trim($content))) {
			return FALSE;
		}
		
		$class = ($css_class) ? ' class="' . $css_class . '"' : '';
		if (self::containsBlockLevelHTML($content)) {
			echo '<div' . $class . '>' . self::prepare($content) . '</div>';
		} else {
			echo '<p' . $class . '>' . self::prepare($content) . '</p>';
		}
		
		return TRUE;
	}
	
	
	/**
	 * Prints a `checked="checked"` HTML input attribute if `$value` equals `$checked_value`, or if `$value` is in `$checked_value`
	 * 
	 * Please note that if either `$value` or `$checked_value` is `NULL`, a
	 * strict comparison will be performed, whereas normally a non-strict
	 * comparison is made. Thus `0` and `FALSE` will cause the checked
	 * attribute to be printed, but `0` and `NULL` will not.
	 * 
	 * @param  string       $value          The value for the current HTML input tag
	 * @param  string|array $checked_value  The value (or array of values) that has been checked
	 * @return boolean  If the checked attribute was printed
	 */
	static public function showChecked($value, $checked_value)
	{
		$checked  = FALSE;
		
		$one_null = $value === NULL || $checked_value === NULL;
		$equal    = ($one_null) ? $value === $checked_value : $value == $checked_value;
		$in_array = is_array($checked_value) && in_array($value, $checked_value, $one_null ? TRUE : FALSE);
		
		if ($equal || $in_array) {
			$checked = TRUE;
		}
		
		if ($checked) {
			echo ' checked="checked"';
			return TRUE;
		}
		
		return FALSE;
	}
	
	
	/**
	 * Forces use as a static class
	 * 
	 * @return fHTML
	 */
	private function __construct() { }
}



/**
 * Copyright (c) 2007-2010 Will Bond <will@flourishlib.com>
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
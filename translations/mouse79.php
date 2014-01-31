<?php 
/*
 * @desc This prorgram is a translation of the Mouse interpreter written in C.
 * This project is originally
 * @author Eric Binnion
*/

error_reporting(E_ALL);	// Turn on warning/error reporting if not already.

// Test that a file name has been passed to the interpreter.
if( count($argv) != 2 ) {
	echo "\nYou must pass a single file name to this interpreter!!\n\n";
	return;
}

// Get all program code and remove all new line characters.
$program = trim( file_get_contents($argv[1]) );
if( $program === false ) {
	echo "\nThere was an error opening the file!!\n\n";
	return;
}

$chpos = 0; // Maintains a global pointer to next character within program
$ch = '';	// Global variable holding current character from program file

// This array contains the entire program read in from $argv[1]
$program = str_split( $program );
// Used as an associatve array to store and retrive A - Z
$memory = array();
// Used for main stack
$stack = array();
// Used as a stack for loops
$loop = array();

/*
	@desc Returns next character in program array
	@param none
	@return none
*/
function getNextChar() {
	global $program, $chpos, $ch;

	if( $chpos >= count($program) ) {
		echo "\n\nIndex is out of bounds of program!!!\n\n";
		exit;
	}

	$ch = $program[$chpos];
	$chpos += 1;
}

/*
	@desc Given a character, will increment chpos until next character ==
		  @param $char
	@param $char
	@return none
*/
function skipTo( $left, $right ) {
	global $ch;
	getNextChar();

	$nested = 0;

	while ( $nested >= 0 ) {
		if( $ch == $left )
			$nested++;
		else if( $ch == $right ) {
			$nested--;	
			getNextChar();
		}
		getNextChar();
	}
}

// Loop until reaching end of file, signified by '$'
do {
	global $ch;
	getNextChar();

	if ( $ch >= '0' && $ch <= '9' ): // Case for all digits
		$temp = 0;
		while ( $ch >= '0' && $ch <= '9' ):
			$temp = 10 * $temp + $ch;
			getNextChar();
		endwhile;
		$stack[] = (int)$temp;
		$chpos--;
	elseif( ctype_alpha($ch) ):	// Case for all characters [A-Za-z]
		$stack[] = $ch;
	elseif( $ch == '?' ):
		// Get input from command line, remove new lines, and push onto stack.
		$char = trim( fgets( fopen ("php://stdin","r") ) );
		$stack[] = $char;
	elseif( $ch == '!' ):
		// Get value off of stack and print to screen
		echo array_pop( $stack );
	elseif( $ch == '+' ):
		$stack[] = array_pop( $stack ) + array_pop( $stack );
	elseif( $ch == '-' ):
		$temp = array_pop( $stack );
		$stack[] = array_pop( $stack ) - $temp;
	elseif( $ch == '*' ):
		$stack[] = array_pop( $stack ) * array_pop( $stack );
	elseif( $ch == '/' ):
		$temp = array_pop( $stack );
		$stack[] = array_pop( $stack ) / $temp;
	elseif( $ch == '.' ):
		// Get value on memory array at current value on stack
		$stack[] = $memory[array_pop( $stack )];
	elseif( $ch == '=' ):
		// Pulls value off of stack and stores it into a temporary variable. 
		// Then adds it to memory array at position specified by popping 
		// from stack again
		$temp = array_pop( $stack );
		$memory[array_pop( $stack )] = $temp; 
	elseif( $ch == '"' ):
		// Print until reaching another "
		do {
			getNextChar();
			if ( $ch == '!' ):
				echo "\n";
			elseif( $ch != '"' ):
				echo $ch;
			endif;
		} while( $ch != '"' );
	elseif( $ch == '[' ):
		// Pops character off of stack. If greater than 0, will run code within []
		// If <= 0, will skip []
		if( array_pop( $stack ) <= 0 ):
			skipTo('[', ']');
		endif;
	elseif( $ch == '(' ):
		// Signifies beginning of loop. Push current chpos onto loop
		// stack so that we can move to beginning of loop after hitting end of loop.
		$loop[] = $chpos;
	elseif( $ch == '^' ):
		// Pops character off of stack. If greater than 0, will allow execution 
		// of code within loop betwee n ^ and ). If <= 0, will skip to end of loop.
		if( array_pop( $stack ) <= 0 ):
			array_pop($loop);
			skipTo('(',')');
		endif;
	elseif( $ch == ')' ):
		// Returns to beginning of current loop by retrieving loop beginning position
		// from top of loop stack.
		$chpos = end($loop); // Return data at end of $loop
	endif; 
} while( $ch != '$' );
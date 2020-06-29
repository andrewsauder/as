<?php

namespace framework\helpers;


class tools {

	/**
	 * Create URL/key safe value of a string
	 *
	 * @param $unsafeString
	 *
	 * @return string
	 */
	public static function sid( $unsafeString ) {

		$unsafeString = str_replace( '/', '-', strtolower( $unsafeString ) );
		preg_match_all( "/[a-z0-9-]+/", str_replace( ' ', '-', strtolower( $unsafeString ) ), $matches );

		return str_replace( '--', '-', implode( '', $matches[ 0 ] ) );
	}


	/**
	 * @param          $script
	 * @param  string  $dataString
	 *
	 * @return bool
	 */
	public static function createCron( $script, $dataString = '' ) {

		$bat = $_SESSION[ AS_APP ][ 'environment' ][ 'phppath' ] . ' -f "' . $_SESSION[ AS_APP ][ 'environment' ][ 'cronpath' ] . $script . '.php" ' . $_SESSION[ AS_APP ][ 'environment' ][ 'cli_server_name' ] . ' ' . $dataString;
		$bat .= "\n exit";

		file_put_contents( $_SESSION[ AS_APP ][ 'environment' ][ 'tmppath' ] . $script . '.bat', $bat );

		pclose( popen( 'start /B ' . $_SESSION[ AS_APP ][ 'environment' ][ 'tmppath' ] . $script . '.bat 2>&1 >nul', "r" ) );

		return true;

	}


	public static function easyCURL( $params ) {

		//$params = [
		//	'url' => 'string',
		//	'fields' => 'array',
		//	'header' => 'array',
		//	'authentication' => [ 'user'=>'string', 'password'=>'string'],
		//	'complexResponse'=>false
		//];

		//$fieldCount = 0;
		//$fields_string = '';
		//foreach($params['fields'] as $key=> $value) {
		//	$fields_string .= $key . '=' . urlencode($value) . '&';
		//	$fieldCount++;
		//}
		//rtrim($fields_string, '&');
		if( !isset( $params[ 'fields' ] ) ) {
			$params[ 'fields' ] = [];
		}

		if( is_array( $params[ 'fields' ] ) && count( $params[ 'fields' ] ) == 0 ) {
			$fields_string = '';
		}
		elseif( is_array( $params[ 'fields' ] ) ) {
			$fields_string = http_build_query( $params[ 'fields' ] );
		}
		elseif( is_string( $params[ 'fields' ] ) ) {
			$fields_string = $params[ 'fields' ];
		}

		//open connection
		$ch = curl_init();

		//set the url, number of POST vars, POST data
		curl_setopt( $ch, CURLOPT_URL, $params[ 'url' ] );
		curl_setopt( $ch, CURLOPT_VERBOSE, 1 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1 );

		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );

		if( isset( $params[ 'method' ] ) ) {
			if( strtolower( $params[ 'method' ] ) == 'post' ) {
				curl_setopt( $ch, CURLOPT_POST, 1 );
				curl_setopt( $ch, CURLOPT_POSTFIELDS, $fields_string );
			}
			elseif( strtolower( $params[ 'method' ] ) == 'get' ) {
				if( strlen( $fields_string ) > 0 ) {
					curl_setopt( $ch, CURLOPT_URL, $params[ 'url' ] . '?' . $fields_string );
				}
			}
			elseif( strtolower( $params[ 'method' ] ) == 'put' ) {
				curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'PUT' );
				curl_setopt( $ch, CURLOPT_POSTFIELDS, $fields_string );
				curl_setopt( $ch, CURLOPT_NOBODY, false );
			}
			elseif( strtolower( $params[ 'method' ] ) == 'delete' ) {
				curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'DELETE' );
				if( strlen( $fields_string ) > 0 ) {
					curl_setopt( $ch, CURLOPT_POSTFIELDS, $fields_string );
				}
			}
		}
		//assume post for legacy
		else {
			curl_setopt( $ch, CURLOPT_POST, 1 );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $fields_string );
		}


		if( isset( $params[ 'header' ] ) ) {
			curl_setopt( $ch, CURLOPT_HTTPHEADER, $params[ 'header' ] );
			curl_setopt( $ch, CURLINFO_HEADER_OUT, true );
		}
		else {
			curl_setopt( $ch, CURLOPT_HEADER, false );
		}

		if( isset( $params[ 'authentication' ] ) ) {
			if( !isset( $params[ 'authentication' ][ 'basic' ] ) || $params[ 'authentication' ][ 'basic' ] != true ) {
				curl_setopt( $ch, CURLOPT_HTTPAUTH, CURLAUTH_NTLM );
			}
			else {
				curl_setopt( $ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
			}
			curl_setopt( $ch, CURLOPT_USERPWD, $params[ 'authentication' ][ 'user' ] . ':' . $params[ 'authentication' ][ 'password' ] );
		}

		if( isset( $params[ 'useragent' ] ) ) {
			curl_setopt( $ch, CURLOPT_USERAGENT, $params[ 'useragent' ] );
		}

		//execute post
		$result = curl_exec( $ch );

		//capture any errors from curl
		$curl_errno = curl_errno( $ch );

		$httpCode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

		if( $_SESSION[ AS_APP ][ 'testing' ] ) {
			if( $curl_errno ) {
				error_log( 'easyCURL error ' . $curl_errno . ' (http status ' . $httpCode . ') on url ' . $params[ 'url' ] );
			}
		}

		//close connection
		curl_close( $ch );

		if( isset( $params[ 'complexResponse' ] ) && $params[ 'complexResponse' ] ) {

			return [
				'result'    => $result,
				'httpCode'  => $httpCode,
				'curlError' => $curl_errno
			];

		}

		return $result;

	}


	/**
	 * Get HTTP status code description from the status code
	 *
	 * Provide 404 and function will return "Not Found"
	 *
	 * @param  int  $code  Provide the status code to get the description
	 *
	 * @return string
	 */
	public static function httpStatusCodeText( int $code = 0 ) {

		if( $code == 0 ) {
			$code = http_response_code();
		}

		switch( $code ) {
			case 100:
				$text = 'Continue';
				break;
			case 101:
				$text = 'Switching Protocols';
				break;
			case 200:
				$text = 'OK';
				break;
			case 201:
				$text = 'Created';
				break;
			case 202:
				$text = 'Accepted';
				break;
			case 203:
				$text = 'Non-Authoritative Information';
				break;
			case 204:
				$text = 'No Content';
				break;
			case 205:
				$text = 'Reset Content';
				break;
			case 206:
				$text = 'Partial Content';
				break;
			case 300:
				$text = 'Multiple Choices';
				break;
			case 301:
				$text = 'Moved Permanently';
				break;
			case 302:
				$text = 'Moved Temporarily';
				break;
			case 303:
				$text = 'See Other';
				break;
			case 304:
				$text = 'Not Modified';
				break;
			case 305:
				$text = 'Use Proxy';
				break;
			case 400:
				$text = 'Bad Request';
				break;
			case 401:
				$text = 'Unauthorized';
				break;
			case 402:
				$text = 'Payment Required';
				break;
			case 403:
				$text = 'Forbidden';
				break;
			case 404:
				$text = 'Not Found';
				break;
			case 405:
				$text = 'Method Not Allowed';
				break;
			case 406:
				$text = 'Not Acceptable';
				break;
			case 407:
				$text = 'Proxy Authentication Required';
				break;
			case 408:
				$text = 'Request Time-out';
				break;
			case 409:
				$text = 'Conflict';
				break;
			case 410:
				$text = 'Gone';
				break;
			case 411:
				$text = 'Length Required';
				break;
			case 412:
				$text = 'Precondition Failed';
				break;
			case 413:
				$text = 'Request Entity Too Large';
				break;
			case 414:
				$text = 'Request-URI Too Large';
				break;
			case 415:
				$text = 'Unsupported Media Type';
				break;
			case 500:
				$text = 'Internal Server Error';
				break;
			case 501:
				$text = 'Not Implemented';
				break;
			case 502:
				$text = 'Bad Gateway';
				break;
			case 503:
				$text = 'Service Unavailable';
				break;
			case 504:
				$text = 'Gateway Time-out';
				break;
			case 505:
				$text = 'HTTP Version not supported';
				break;
			default:
				$text = 'Unknown http status code "' . htmlentities( $code ) . '"';
				break;
		}

		return $text;
	}


	/**
	 * Create a CSV string from a numerical array of associative arrays
	 * Provide array [ ['name'=>'Joe', 'grade'=>'A'], ['name'=>'Jane', 'grade'=>'B']]
	 * and method will return "name,grade\nJoe,A\nJane,B"
	 *
	 * @param          $array
	 * @param  bool    $header_row  Optional; Default=true; If true, the first row of the string will be the array key
	 *                              names
	 * @param  string  $col_sep     Optional; Default=,; If provided, this string will be used to separate values
	 *                              instead of the default comma
	 * @param  string  $row_sep     Optional; Default=\n; If provided, row separation will use provided string instead
	 *                              of new line character
	 * @param  string  $qut         Optional; Default="; If provided, values that must be put in quotes in the CSV
	 *                              output will be wrapped by the provided value
	 *
	 * @return bool|string
	 */
	public static function convertArrayToCSV( $array, $header_row = true, $col_sep = ",", $row_sep = "\n", $qut = '"' ) {

		if( !is_array( $array ) or !is_array( $array[ 0 ] ) ) {
			error_log( 'Empty array provided' );

			return '';
		}

		$output  = '';
		$headers = [];

		//Header row.

		foreach( $array[ 0 ] as $key => $val ) {
			//Escaping quotes.
			$key = str_replace( $qut, "$qut$qut", $key );
			if( $header_row ) {
				$output .= "$col_sep$qut$key$qut";
			}
			$headers[] = $key;
		}

		if( $header_row ) {
			$output = substr( $output, 1 ) . $row_sep;
		}

		//Data rows.
		foreach( $array as $key => $val ) {
			$tmp = '';
			//foreach ($val as $cell_key => $cell_val)
			foreach( $headers as $ik ) {
				$thiscellval = isset( $val[ $ik ] ) ? $val[ $ik ] : '';
				$cell_val    = str_replace( $qut, "$qut$qut", $thiscellval );
				if( is_numeric( $cell_val ) || empty( $cell_val ) ) {
					$tmp .= "$col_sep$cell_val";
				}
				else {
					$tmp .= "$col_sep$qut$cell_val$qut";
				}
			}
			$output .= substr( $tmp, 1 ) . $row_sep;
		}

		return trim( $output );
	}


	//getRandomString requires either PHP7 OR include_once(AS__PATH.'/libs/random_compat/random.php');


	/**
	 * Create a random string of characters
	 *
	 * @param  int   $length   Optional; Default=8;
	 * @param  bool  $upper    Optional; Default=true; If true, will include upper case letters
	 * @param  bool  $lower    Optional; Default=true; If true, will include lower case letters
	 * @param  bool  $numbers  Optional; Default=true; If true, will include numbers
	 * @param  bool  $symbols  Optional; Default=true; If true, will include symbols
	 *
	 * @return string
	 */
	public static function getRandomString( $length = 8, $upper = true, $lower = true, $numbers = true, $symbols = false ) {

		$alphabet  = '';
		$randomStr = '';

		if( $upper ) {
			$alphabet .= "ABCDEFGHIJKLMNPQRSTUVWXYZ";
		}
		if( $lower ) {
			$alphabet .= "abcdefghijklmnpqrstuvwxyz";
		}
		if( $numbers ) {
			$alphabet .= "123456789";
		}
		if( $symbols ) {
			$alphabet .= '{}()[]#:;^!|&,.?_`~@$%/\=+-*';
		}

		$alphamax = strlen( $alphabet ) - 1;

		for( $i = 0; $i < $length; $i++ ) {
			try {
				$randomStr .= $alphabet[ random_int( 0, $alphamax ) ];
			}
			catch( Exception $e ) {
				$randomStr .= $alphabet[ rand( 0, $alphamax ) ];
			}

		}

		return $randomStr;
	}


	/**
	 * Read file system
	 *
	 * @param  string  $path                     Full path of directory to get files and directories from
	 * @param  bool    $returnSimple             [optional] Default=false; Set to true for flat list of full file paths
	 * @param  mixed   $fileFilter               [optional] Default=null (no filter); Pass a string or string[] of
	 *                                           three characters to get only files that use that extension (rather
	 *                                           whose last three characters of extension matches)
	 * @param  mixed   $dirFilter                [optional] Default=null (no filter); Pass a string or string[] of a
	 *                                           name of a directory to only return directories with that name (files
	 *                                           and subdirectories are not included unless $returnAncestorsOnFilter is
	 *                                           set to true)
	 * @param  bool    $returnAncestorsOnFilter  [optional] Default=false; Set to true to include the files and
	 *                                           subdirectories of directories that match the $dirFilter in the
	 *                                           response
	 * @param  bool    $stripLongFilePath        [optional] Default=false; Set to true to remove part of the full file
	 *                                           path in the response. $stripPath MUST BE PROVIDED if this is true.
	 * @param  string  $stripPath                [optional] Default = ''; If $stripLongFilePath is true, you must
	 *                                           provide a string that will be removed from the full file path in the
	 *                                           response
	 *
	 * @return mixed[]
	 */
	public static function ls( $path, $returnSimple = false, $fileFilter = null, $dirFilter = null, $returnAncestorsOnFilter = false, $stripLongFilePath = false, $stripPath = '' ) {

		$dir = [];

		if( substr( $path, -1 ) != '/' ) {
			$path = $path . '/';
		}

		if( $dirFilter !== null ) {
			foreach( $dirFilter as $dirFilterIndex => $df ) {
				$dirFilter[ $dirFilterIndex ] = strtolower( $df );
			}
		}
		if( $fileFilter !== null ) {
			if( is_string( $fileFilter ) ) {
				$fileFilter = [ strtolower( $fileFilter ) ];
			}
			foreach( $fileFilter as $fileFilterIndex => $ff ) {
				$fileFilter[ $fileFilterIndex ] = strtolower( $ff );
			}
		}

		if( !is_dir( $path ) ) {
			return [];
		}

		if( $handle = opendir( $path ) ) {
			while( false !== ( $entry = readdir( $handle ) ) ) {
				if( $entry != "." && $entry != ".." && $entry != '$RECYCLE.BIN' && $entry != '.quarantine' && $entry != '.tmb' && $entry != 'Thumbs.db' ) {

					$isDIR = is_dir( $path . $entry );

					$files = null;

					if( $isDIR ) {
						$filetype = 'dir';
						if( $dirFilter === null || in_array( strtolower( $entry ), $dirFilter ) ) {
							$add = true;
						}
						else {
							$add = false;
						}

						if( ( $add && !$returnSimple ) || $returnAncestorsOnFilter ) {
							$files = self::ls( $path . $entry, $returnSimple, $fileFilter, $dirFilter, $returnAncestorsOnFilter, $stripLongFilePath, $path );
						}
					}
					else {
						$filetype = 'file';
						if( $fileFilter === null || in_array( strtolower( substr( $entry, -3 ) ), $fileFilter ) ) {
							$add = true;
						}
						else {
							$add = false;
						}
					}

					if( $add || $files !== null ) {
						if( $returnSimple ) {
							if( $add ) {
								$dir[] = $entry;
							}
							if( $files != null ) {
								$dir = array_merge( $dir, $files );
							}
						}
						else {

							$filePathFull = $path . $entry;

							if( $stripLongFilePath ) {
								if( $stripPath == '' ) {
									$stripPath = $path;
								}
								$filePathFull = str_replace( $stripPath, '', $filePathFull );
							}

							$dir[] = [
								'name'  => $entry,
								'type'  => $filetype,
								'files' => $files,
								'path'  => $filePathFull
							];
						}
					}

				}
			}
		}

		return $dir;
	}


	/**
	 * @param          $src
	 * @param  string  $format  Optional. Default=array; Can only be "array" or "object"
	 *
	 * @return bool|false|mixed|string
	 */
	public static function getXMLAsArray( $src, $format = 'array' ) {

		$xml  = simplexml_load_file( $src );
		$json = json_encode( $xml );
		if( $format == 'json' ) {
			return $json;
		}
		elseif( $format == 'array' ) {
			return json_decode( $json, true );
		}
		elseif( $format == 'object' ) {
			return json_decode( $json, false );
		}
		else {
			return false;
		}
	}

}
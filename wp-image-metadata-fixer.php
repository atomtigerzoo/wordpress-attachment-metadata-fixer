<?php

/**
 * Wordpress attachment metadata fixer
 * 
 * This script fixes missing image sizes in Wordpress' wp_postmeta database 
 * table by adding them to the meta data. It does not create new or changed 
 * image file, nor does it delete image files.
 * 
 * It best used on a copy/dump of the wp_postmeta table.
 * 
 * Please - I mean it - make backups before using this script! I am not 
 * responsible for any errors, failures, missing data or what else. Use this 
 * script at your own risk. Read the code. Know what you are doing. Never use 
 * it on live data! Never ever!
 * 
 * See the code for more instructions and configurations.
 * 
 * @author Henning Stein, www.atomtigerzoo.com
 * @version 0.2.0
 */


// Enable if you want some error reporting
#error_reporting(E_ALL);
#ini_set('display_errors', '1');


/**
 * Uncomment to run fixer
 */
$imgfix = new FixImageSizes();
$imgfix->repair();


class FixImageSizes {
	
	/**
	 * Test-Run only setting
	 * Leave it set as 'true' if you want to test before manipulating the 
	 * database. When you are ready set it to 'false' [Without the quotes ;)]
	 * 
	 * @var bool
	 */
	var $test = true;
	
	
	/**
	 * Set the following values to login into your database
	 */
	
	/**
	 * Database host
	 * @var type 
	 */
	var $db_host = 'localhost';
	
	/**
	 * Database name
	 * Please don't use the live database. Create a dump and then use a separate
	 * database for this script!
	 * 
	 * @var type 
	 */
	var $db_database = 'image-fix';
	
	/**
	 * Database user
	 * @var type 
	 */
	var $db_user = 'your-database-username';
	
	/**
	 * Database password
	 * @var type 
	 */
	var $db_pass = 'your-database-password';
	
	
	/**
	 * Set the sizes you want to have checked and added.
	 * 
	 * For every entry in Wordpress (and the Wordpress-Backend settings) use
	 * the following layout - replace the capitalised words with your values:
	 * 
	 *		'CUSTOM_NAME' => array(
	 *			'width' => WIDTH_IN_PIXELS,
	 *			'height' => HEIGHT_IN_PIXELS
	 *		),
	 * 
	 * See below for some examples.
	 * 
	 * @var type 
	 */
	var $image_sizes = array(
		// Standard Wordpress sizes
		'thumbnail' => array( // use the sizes from your Wordpress Backend > Media
			'width' => 150,
			'height' => 150
		),
		'medium' => array( // use the sizes from your Wordpress Backend > Media
			'width' => 300,
			'height' => 300
		),
		'large' => array( // use the sizes from your Wordpress Backend > Media
			'width' => 300,
			'height' => 300
		),
		// Custom sizes
		'author_photo' => array(
			'width' => 80,
			'height' => 80
		),
		'gallery' => array(
			'width' => 200,
			'height' => 250
		),
		'newsticker' => array(
			'width' => 300,
			'height' => 350
		)
	);
	
	
	
	/**
	 * 
	 * + + + + + + STOP EDITING HERE + + + + + + 
	 * 
	 */
	
	
	/**
	 * Counter for missing sizes
	 * @var int
	 */
	var $missing_sizes_counter;
	
	
	/**
	 * Database connection holder
	 * @var \PDO
	 */
	var $db_connection;
	
	
	/**
	 * Connect to database
	 */
	public function __construct() {
		echo '<h2>Fix Image Fixes</h2><hr>';
		
		if($this->test):
			echo '<h3>Test run.</h3><hr>';
		endif;
		
		$this->db_connect();
	}
	
	
	/**
	 * Disconnect from database
	 */
	public function __destruct() {
		$this->db_connection = null;
	}
	
	
	/**
	 * Connect to your database
	 */
	public function db_connect() {
		try {
			$dbh = new PDO('mysql:host=' . $this->db_host . ';dbname=' . $this->db_database, $this->db_user, $this->db_pass);
			
			// Disable doubled return-values
			$dbh->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
			// Enable errors
			$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

			$this->db_connection = $dbh;
		}
		catch(Exception $ex) {
			die('Database connection failed. [' . $ex->getMessage() . ']');
		}
	}
	
	
	/**
	 * Retrieve attachements from database
	 * 
	 * @param \PDO $dbh
	 * @return \PDO
	 */
	public function db_get_rows() {
		try{
			return $this->db_connection->query('SELECT * FROM wp_postmeta WHERE meta_key = "_wp_attachment_metadata"');
		} catch (Exception $ex) {
			die('Database query failed. [' . $ex->getMessage() . ']');
		}
	}


	/**
	 * Simple matching for returning a mimetype for the given extension.
	 * 
	 * Possible problems:
	 *	Only checks the given string, not the file.
	 *	Only uses png, jpg and gif.
	 * 
	 * @param string $extension
	 * @return string
	 */
	public function file_extension_mimetype($extension = null) {
		switch(strtolower($extension)):
			case 'png':
				return 'image/png';
			case 'gif':
				return 'image/gif';
			case 'jpg':
			case 'jpeg':
			default:
				return 'image/jpeg';
		endswitch;
	}


	/**
	 * Return file details
	 * 
	 * @param string $string
	 * @return array
	 */
	public function file_details($string = null) {
		$details = pathinfo($string);
		$details['mimetype'] = $this->file_extension_mimetype($details['extension']);
		
		return $details;
	}
	
	
	/**
	 * Set sizes
	 * 
	 * @param string $file_name
	 * @param string $file_mimetype
	 * @param string $file_extension
	 * @param int $width
	 * @param int $height
	 * @return array
	 */
	public function set_meta_size($file_name, $file_mimetype, $file_extension, $width, $height) {
		return array(
			"file" => $file_name . '-' . $width . 'x' . $height . '.' . $file_extension,
			"width" => (int)$width,
			"height" => (int)$height,
			"mime-type" => $file_mimetype
		);
	}
	
	
	/**
	 * Check for existing/missing sizes and add according
	 * 
	 * @param array $meta_value
	 * @param array $filedetails
	 * @return array
	 */
	public function check_and_add_sizes($meta_value, $filedetails) {
		foreach($this->image_sizes as $custom_name => $size):
			if(isset($meta_value['sizes'][$custom_name])):
				// Size exists. Next.
				break;
			endif;

			$this->missing_sizes_counter++;

			// Add missing sizes to array
			$meta_value['sizes'][$custom_name] = $this->set_meta_size(
					$filedetails['filename'], 
					$filedetails['mimetype'], 
					$filedetails['extension'], 
					$size['width'], 
					$size['height']
			);

			if($this->test):
				echo $custom_name . ' will be added to sizes.<br>';
			endif;
		endforeach;
		
		return $meta_value;
	}
	
	
	/**
	 * Update row in database
	 * 
	 * @param array $meta_value
	 * @param bigint $update_id
	 */
	public function db_update_row($meta_value, $update_id) {
		if($this->test):
			echo '<br>Test-Run! No database query executed.';
			return;
		endif;
		
		// Save to DB
		$db_query = $this->db_connection->prepare("UPDATE wp_postmeta SET meta_value = :save_data WHERE meta_id = :update_id AND meta_key = '_wp_attachment_metadata'");

		$db_executed_query = $db_query->execute(array(
			':save_data' => serialize($meta_value),
			':update_id' => $update_id
		));

		if(!$db_executed_query):
			echo 'Update of ID ' . $update_id . ' failed. [' . $db_executed_query->getMessage() . ']';
			return false;
		endif;
		
		echo 'Added and saved <span style="color: red;">' . $this->missing_sizes_counter . '</span> sizes to row-ID ' . $update_id;
		return true;
	}
	
	
	/**
	 * 
	 * @return type
	 */
	public function repair() {
		// Get rows from DB
		$db_rows = $this->db_get_rows();

		// Counter for tests
		$count = 1;

		// Fix entries
		foreach($db_rows as $row):
			if($this->test && $count > 100) {
				return;
			}

			// Unserialize 'meta_value'
			$meta_value = unserialize($row['meta_value']);

			// File details
			$filedetails = $this->file_details($meta_value['file']);

			// Missing sizes counter
			$this->missing_sizes_counter = 0;

			// Check and add sizes
			$meta_value = $this->check_and_add_sizes($meta_value, $filedetails);

			// Output
			if($this->missing_sizes_counter === 0):
				echo '<span style="color: #bbb;">' . $filedetails['filename'] . ' already had all sizes.</span>';
			else:
				$this->db_update_row($meta_value, $row['meta_id']);
			endif;

			$count++;

			echo '<br><hr>';
		endforeach;
	}


}

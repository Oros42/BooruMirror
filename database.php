<?php
if (!version_compare(phpversion(), '5.3.0', '>='))
	die("This software requires PHP version 5.3.0 at least, yours is ".phpversion());

if (!class_exists('SQLite3'))
	die("This software requires the SQLite3 PHP extension, and it can't be found on this system!");

/**
* 
*/
class Database
{
	private $db;
	private $fields=array(
			'page_url', 
			'file_id', 
			'img_url', 
			'img_name', 
			'posted', 
			'author', 
			'author_url',
			'width',
			'height',
			'source', 
			'rating', 
			'score', 
			'tags', 
			);
	
	function __construct($booru)
	{
		if(is_dir(__DIR__.'/mirror/'.$booru)) {
		$this->db = new SQLite3(__DIR__.'/mirror/'.$booru.'/database.db');
		$this->db->busyTimeout(15000);
		// initial DB
		$this->db->querySingle('CREATE TABLE IF NOT EXISTS database (
			id INTEGER UNIQUE PRIMARY KEY AUTOINCREMENT,
			page_url TEXT UNIQUE,
			file_id TEXT DEFAULT NULL,
			img_url TEXT DEFAULT NULL,
			img_name TEXT DEFAULT NULL,
			posted TEXT DEFAULT NULL,
			author TEXT DEFAULT NULL,
			author_url TEXT DEFAULT NULL,
			width INT(10) DEFAULT NULL,
			height INT(10) DEFAULT NULL,
			source TEXT DEFAULT NULL,
			rating TEXT DEFAULT NULL,
			score INT(10) DEFAULT NULL,
			tags TEXT DEFAULT NULL,
			created_at INT
			)'
		);			
		}
	}

	private function query($sql){
		$res = $this->db->query($sql);
		$out = array();
		if(!empty($res)){
			while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
				$out[] = $row;
			}
		}
		return $out;
	}

	public function insert($item) {
		$list_fields="";
		$list_values="";
		foreach ($this->fields as $field) {
			if(isset($item[$field])){
				$list_fields.=$field.", ";
				$list_values.="'".str_replace("'", "&quot;", $item[$field])."', ";
			}
		}
		$this->db->exec('INSERT OR IGNORE INTO database (id, '.$list_fields.'created_at) VALUES (NULL, '.$list_values.time().');');
	}

	// fetch items for 1 page
	public function listByPage($page = 1) {
		$nb = 20;
		$begin = ((int)$page - 1) * $nb;
		return $this->query('SELECT * FROM database ORDER BY id DESC LIMIT '.(int)$begin.','.(int)$nb.';');
	}

	// full database output
	public function fullDump() {
		return $this->query('SELECT * FROM database ORDER BY id DESC;');
	}


	public function get_last_insert() {
		$r=$this->query('SELECT * FROM database ORDER BY id DESC LIMIT 1;');
		if(!empty($r)){
			return $r[0];
		}else{
			return array();
		}
	}

	public function get_nb_post(){
		$r=$this->query('SELECT count(id) as `c` FROM database;');
		return $r[0]['c'];
	}

	public function get_by_id($id) {
		$r=$this->query('SELECT * FROM database WHERE file_id='.((int) $id).';');
		if(!empty($r)){
			return $r[0];
		}else{
			return array();
		}
	}
}
?>
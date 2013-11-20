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
	// If you add new fields, the database will be updated automatically ;-)
	private $fields=array(
			'id'=>'INTEGER UNIQUE PRIMARY KEY AUTOINCREMENT',
			'page_url'=>'TEXT UNIQUE', 
			'file_id'=>'TEXT DEFAULT NULL',
			'img_url'=>'TEXT DEFAULT NULL',
			'img_name'=>'TEXT DEFAULT NULL',
			'posted'=>'TEXT DEFAULT NULL',
			'author'=>'TEXT DEFAULT NULL',
			'author_url'=>'TEXT DEFAULT NULL',
			'width'=>'INT(10) DEFAULT NULL',
			'height'=>'INT(10) DEFAULT NULL',
			'source'=>'TEXT DEFAULT NULL',
			'rating'=>'TEXT DEFAULT NULL',
			'score'=>'INT(10) DEFAULT NULL',
			'tags'=>'TEXT DEFAULT NULL',
			'file_size'=>'INT(10) DEFAULT NULL',
			'checksum'=>'TEXT DEFAULT NULL',
			'checksum_sha1'=>'TEXT DEFAULT NULL',
			'created_at'=>'INT'
			);
	public $version='';
	
	function __construct($booru)
	{
		if(is_dir(__DIR__.'/mirror/'.$booru)) {
			$this->version=md5_file(__FILE__);
			$this->db = new SQLite3(__DIR__.'/mirror/'.$booru.'/database.db');
			$this->db->busyTimeout(15000);
			// initial DB
			$r='CREATE TABLE IF NOT EXISTS database (';
			foreach ($this->fields as $name => $type) {
				$r.=' '.$name.' '.$type.',';
			}
			$r=substr($r, 0, -1).');';
			$this->db->querySingle($r);
			$this->db->querySingle('CREATE TABLE IF NOT EXISTS infos (id INTEGER UNIQUE PRIMARY KEY AUTOINCREMENT, name TEXT UNIQUE, value TEXT DEFAULT NULL);');
			$r=$this->query("SELECT value FROM infos WHERE name='version'");
			if(empty($r)){
				$this->check_field();
				$this->db->exec("INSERT OR IGNORE INTO infos (id, name, value) VALUES (NULL, 'version', '".$this->version."');");
			}elseif($r[0]['value']!=$this->version){
				$this->check_field();
				$this->db->exec("UPDATE infos SET value='".$this->version."' WHERE name='version';");
			}
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

	private function check_field() {
		// check if the database structure have changed
		$r=$this->query('PRAGMA table_info(database);');
		$fields_in_db=array();
		foreach ($r as $value) {
			$fields_in_db[$value['name']]=$value;
		}
		foreach ($this->fields as $name => $type) {
			if(!isset($fields_in_db[$name])){
				$this->db->exec("ALTER TABLE database ADD COLUMN $name $type;");
			}
		}		
	}

	public function insert($item) {
		if(!empty($item['file_id']) && !empty($item['img_name'])){
			$list_fields="";
			$list_values="";
			foreach ($this->fields as $field=>$type) {
				if(isset($item[$field])){
					$list_fields.=$field.", ";
					$list_values.="'".htmlspecialchars($item[$field])."', ";
				}
			}
			$this->db->exec('INSERT OR IGNORE INTO database (id, '.$list_fields.'created_at) VALUES (NULL, '.$list_values.time().');');
		}
	}

	// fetch items for 1 page
	public function list_by_page($page = 1) {
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

	public function get_by_tag($tags, $page = 1) {
		$nb = 20;
		$begin = ((int)$page - 1) * $nb;
		$tags=explode(' ', $tags);
		if(!empty($tags)){
			$q='';
			foreach ($tags as $tag) {
				if(!empty($tag)){
					$q.=" AND tags LIKE '%". htmlspecialchars($tag)."%'";
				}
			}
			if($q!=''){
				return $this->query("SELECT * FROM database WHERE ".substr($q, 5)." ORDER BY id DESC LIMIT ".(int)$begin.','.(int)$nb.';');
			}else{
				return array();
			}		
		}else{
			return array();
		}
	}

	public function get_nb_post_by_tag($tags){
		$tags=explode(' ', $tags);
		if(!empty($tags)){
			$q='';
			foreach ($tags as $tag) {
				if(!empty($tag)){
					$q.=" AND tags LIKE '%". htmlspecialchars($tag)."%'";
				}
			}
			if($q!=''){
				$r=$this->query("SELECT count(id) as `c` FROM database WHERE ".substr($q, 5).";");
				return $r[0]['c'];
			}else{
				return 0;
			}		
		}else{
			return 0;
		}		
	}
}
?>
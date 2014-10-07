<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2014 Aur�lien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

if( !defined('_INC_CLASS_WADB_SQLITE') ) {

define('_INC_CLASS_WADB_SQLITE', true);

class Wadb_sqlite {

	/**
	 * Type de base de donn�es
	 *
	 * @var string
	 * @access private
	 */
	var $engine = 'sqlite';
	
	/**
	 * Connexion � la base de donn�es
	 * 
	 * @var resource
	 * @access private
	 */
	var $link;
	
	/**
	 * Nom de la base de donn�es
	 * 
	 * @var string
	 * @access public
	 */
	var $dbname = '';
	
	/**
	 * Options de connexion
	 * 
	 * @var array
	 * @access private
	 */
	var $options = array();
	
	/**
	 * Code d'erreur
	 * 
	 * @var integer
	 * @access public
	 */
	var $errno = 0;
	
	/**
	 * Message d'erreur
	 * 
	 * @var string
	 * @access public
	 */
	var $error = '';
	
	/**
	 * Derni�re requ�te SQL ex�cut�e (en cas d'erreur seulement)
	 * 
	 * @var string
	 * @access public
	 */
	var $lastQuery = '';
	
	/**
	 * Nombre de requ�tes SQL ex�cut�es depuis le d�but de la connexion
	 * 
	 * @var integer
	 * @access public
	 */
	var $queries = 0;
	
	/**
	 * Dur�e totale d'ex�cution des requ�tes SQL
	 * 
	 * @var integer
	 * @access public
	 */
	var $sqltime = 0;
	
	/**
	 * Version de la librairie SQLite
	 * 
	 * @var string
	 * @access public
	 */
	var $libVersion = '';
	
	/**
	 * "Constantes" de la classe
	 */
	var $SQL_INSERT = 1;
	var $SQL_UPDATE = 2;
	var $SQL_DELETE = 3;
	
	/**
	 * Constructeur de classe
	 * 
	 * @param string $sqlite_db Nom de la base de donn�es
	 * @param array  $options  Options de connexion/utilisation
	 * 
	 * @access public
	 */
	function Wadb_sqlite($sqlite_db, $options = null)
	{
		if( $sqlite_db != ':memory:' ) {
			if( file_exists($sqlite_db) ) {
				if( !is_readable($sqlite_db) ) {
					trigger_error("SQLite database isn't readable!", E_USER_WARNING);
				}
			}
			else if( !is_writable(dirname($sqlite_db)) ) {
				trigger_error(dirname($sqlite_db) . " isn't writable. Cannot create "
					. basename($sqlite_db) . " database", E_USER_WARNING);
			}
		}
		
		$connect = 'sqlite_open';
		$this->dbname = $sqlite_db;
		
		if( is_array($options) ) {
			$this->options = array_merge($this->options, $options);
		}
		
		if( !empty($options['persistent']) ) {
			$connect = 'sqlite_popen';
		}
		
		if( !($this->link = $connect($sqlite_db, 0666, $this->error)) ) {
			$this->errno = -1;
			throw new SQLException($this->error, $this->errno);
		}
		
		sqlite_exec($this->link, 'PRAGMA short_column_names = 1');
		sqlite_exec($this->link, 'PRAGMA case_sensitive_like = 0');
		
		ini_set('sqlite.assoc_case', '0');
		$this->libVersion = sqlite_libversion();
		
//		if( !empty($this->options['charset']) ) {
//			$this->encoding($this->options['charset']);
//		}
	}
	
	/**
	 * Connexion � la base de donn�es
	 * 
	 * @param array $infos    Informations de connexion
	 * @param array $options  Options de connexion/utilisation
	 * 
	 * @access public
	 * @return boolean
	 */
	function connect($infos = null, $options = null)
	{
		if( is_array($options) ) {
			$this->options = array_merge($this->options, $options);
		}
		
		return true;
	}
	
	/**
	 * @access public
	 * @return boolean
	 */
	function isConnected()
	{
		return !is_null($this->link);
	}
	
	/**
	 * Renvoie le jeu de caract�res courant utilis�.
	 * Si l'argument $encoding est fourni, il est utilis� pour d�finir
	 * le nouveau jeu de caract�res de la connexion en cours
	 * 
	 * @param string $encoding
	 * 
	 * @access public
	 * @return string
	 */
	function encoding($encoding = null)
	{
		if( !is_null($encoding) ) {
			trigger_error("Setting encoding isn't supported by SQLite", E_USER_WARNING);
		}
		
		return sqlite_libencoding();
	}
	
	/**
	 * Ex�cute une requ�te sur la base de donn�es
	 * 
	 * @param string $query
	 * 
	 * @access public
	 * @return mixed
	 */
	function query($query)
	{
		$curtime = array_sum(explode(' ', microtime()));
		$result  = sqlite_query($this->link, $query);
		$endtime = array_sum(explode(' ', microtime()));
		
		$this->sqltime += ($endtime - $curtime);
		$this->queries++;
		
		if( !$result ) {
			$this->errno = sqlite_last_error($this->link);
			$this->error = sqlite_error_string($this->errno);
			$this->lastQuery = $query;
			throw new SQLException($this->error, $this->errno);
			
			$this->rollBack();
		}
		else {
			$this->errno = 0;
			$this->error = '';
			$this->lastQuery = '';
			
			if( !is_bool($result) ) {// on a r�ceptionn� une ressource ou un objet
				$result = new WadbResult_sqlite($this->link, $result);
			}
		}
		
		return $result;
	}
	
	/**
	 * Construit une requ�te de type INSERT ou UPDATE � partir des diverses donn�es fournies
	 * 
	 * @param string $type      Type de requ�te (peut valoir INSERT ou UPDATE)
	 * @param string $table     Table sur laquelle effectuer la requ�te
	 * @param array  $data      Tableau des donn�es � ins�rer. Le tableau a la structure suivante:
	 *                          array(column_name => column_value[, column_name => column_value])
	 * @param array $sql_where  Cha�ne de condition
	 * 
	 * @access public
	 * @return mixed
	 */
	function build($type, $table, $data, $sql_where = null)
	{
		$fields = $values = array();
		
		foreach( $data as $field => $value ) {
			if( is_null($value) ) {
				$value = 'NULL';
			}
			else if( is_bool($value) ) {
				$value = intval($value);
			}
			else if( !is_int($value) && !is_float($value) ) {
				$value = '\'' . $this->escape($value) . '\'';
			}
			
			array_push($fields, $this->quote($field));
			array_push($values, $value);
		}
		
		if( $type == $this->SQL_INSERT ) {
			$query = sprintf('INSERT INTO %s (%s) VALUES(%s)', $table, implode(', ', $fields), implode(', ', $values));
		}
		else if( $type == $this->SQL_UPDATE ) {
			
			$query = 'UPDATE ' . $table . ' SET ';
			for( $i = 0, $m = count($fields); $i < $m; $i++ ) {
				$query .= $fields[$i] . ' = ' . $values[$i] . ', ';
			}
			
			$query = substr($query, 0, -2);
			
			if( is_array($sql_where) && count($sql_where) > 0 ) {
				$query .= ' WHERE ';
				foreach( $sql_where as $field => $value ) {
					if( is_null($value) ) {
						$value = 'NULL';
					}
					else if( is_bool($value) ) {
						$value = intval($value);
					}
					else if( !is_int($value) && !is_float($value) ) {
						$value = '\'' . $this->escape($value) . '\'';
					}
					
					$query .= sprintf('%s = %s AND ', $this->quote($field), $value);
				}
				
				$query = substr($query, 0, -5);
			}
		}
		
		return $this->query($query);
	}
	
	/**
	 * Prot�ge un nom de base, de table ou de colonne en pr�vision de son utilisation
	 * dans une requ�te
	 * 
	 * @param string $name
	 * 
	 * @access public
	 * @return string
	 */
	function quote($name)
	{
		return '[' . $name . ']';
	}
	
	/**
	 * @param mixed $tables  Nom de table ou tableau de noms de table
	 * 
	 * @access public
	 * @return void
	 */
	function vacuum($tables)
	{
		if( !is_array($tables) ) {
			$tables = array($tables); 
		}
		
		foreach( $tables as $tablename ) {
			sqlite_exec($this->link, 'VACUUM ' . $tablename);
		}
	}
	
	/**
	 * D�marre le mode transactionnel
	 * 
	 * @access public
	 * @return boolean
	 */
	function beginTransaction()
	{
		return sqlite_exec($this->link, 'BEGIN');
	}
	
	/**
	 * Envoie une commande COMMIT � la base de donn�es pour validation de la
	 * transaction courante
	 * 
	 * @access public
	 * @return boolean
	 */
	function commit()
	{
		if( !($result = sqlite_exec($this->link, 'COMMIT')) )
		{
			sqlite_exec($this->link, 'ROLLBACK');
		}
		
		return $result;
	}
	
	/**
	 * Envoie une commande ROLLBACK � la base de donn�es pour annulation de la
	 * transaction courante
	 * 
	 * @access public
	 * @return boolean
	 */
	function rollBack()
	{
		return sqlite_exec($this->link, 'ROLLBACK');
	}
	
	/**
	 * Renvoie le nombre de lignes affect�es par la derni�re requ�te DML
	 * 
	 * @access public
	 * @return boolean
	 */
	function affectedRows()
	{
		return sqlite_changes($this->link);
	}
	
	/**
	 * Retourne l'identifiant g�n�r� automatiquement par la derni�re requ�te
	 * INSERT sur la base de donn�es
	 * 
	 * @access public
	 * @return integer
	 */
	function lastInsertId()
	{
		return sqlite_last_insert_rowid($this->link);
	}
	
	/**
	 * �chappe une cha�ne en pr�vision de son insertion dans une requ�te sur
	 * la base de donn�es
	 * 
	 * @param string $string
	 * 
	 * @access public
	 * @return string
	 */
	function escape($string)
	{
		return sqlite_escape_string($string);
	}
	
	/**
	 * V�rifie l'�tat de la connexion courante et effectue si besoin une reconnexion
	 * 
	 * @access public
	 * @return boolean
	 */
	function ping()
	{
		return true;
	}
	
	/**
	 * Ferme la connexion � la base de donn�es
	 * 
	 * @access public
	 * @return boolean
	 */
	function close()
	{
		if( !is_null($this->link) ) {
			@$this->rollBack();
			$result = sqlite_close($this->link);
			$this->link = null;
			
			return $result;
		}
		else {
			return true;
		}
	}
	
	/**
	 * Destructeur de classe
	 * 
	 * @access public
	 * @return void
	 */
	function __destruct()
	{
		$this->close();
	}
	
	/**
	 * Initialise un objet WadbBackup_{self::$engine}
	 *
	 * @access public
	 * @return object
	 */
	function initBackup()
	{
		return new WadbBackup_sqlite($this);
	}
}

class WadbResult_sqlite {
	
	/**
	 * Connexion � la base de donn�es
	 * 
	 * @var resource
	 * @access private
	 */
	var $link;
	
	/**
	 * Ressource de r�sultat de requ�te
	 * 
	 * @var resource
	 * @access private
	 */
	var $result;
	
	/**
	 * Mode de r�cup�ration des donn�es
	 * 
	 * @var integer
	 * @access private
	 */
	var $fetchMode;
	
	/**
	 * "Constantes" de la classe
	 */
	var $SQL_FETCH_NUM   = SQLITE_NUM;
	var $SQL_FETCH_ASSOC = SQLITE_ASSOC;
	var $SQL_FETCH_BOTH  = SQLITE_BOTH;
	
	/**
	 * Constructeur de classe
	 * 
	 * @param resource $link    Ressource de connexion � la base de donn�es
	 * @param resource $result  Ressource de r�sultat de requ�te
	 * 
	 * @access public
	 */
	function WadbResult_sqlite($link, $result)
	{
		$this->link   = $link;
		$this->result = $result;
		$this->fetchMode = SQLITE_BOTH;
	}
	
	/**
	 * Renvoie la ligne suivante dans le jeu de r�sultat
	 * 
	 * @param integer $mode  Mode de r�cup�ration des donn�es
	 * 
	 * @access public
	 * @return array
	 */
	function fetch($mode = null)
	{
		if( is_null($mode) ) {
			$mode = $this->fetchMode;
		}
		
		return sqlite_fetch_array($this->result, $mode);
	}
	
	/**
	 * Renvoie sous forme d'objet la ligne suivante dans le jeu de r�sultat
	 * 
	 * @access public
	 * @return object
	 */
	function fetchObject()
	{
		return sqlite_fetch_object($this->result);
	}
	
	/**
	 * Renvoie un tableau de toutes les lignes du jeu de r�sultat
	 * 
	 * @param integer $mode  Mode de r�cup�ration des donn�es
	 * 
	 * @access public
	 * @return array
	 */
	function fetchAll($mode = null)
	{
		if( is_null($mode) ) {
			$mode = $this->fetchMode;
		}
		
		return sqlite_fetch_all($this->result, $mode);
	}
	
	/**
	 * Retourne le contenu de la colonne pour l'index ou le nom donn�
	 * � l'index suivant dans le jeu de r�sultat.
	 * 
	 * @param mixed $column  Index ou nom de la colonne
	 * 
	 * @access public
	 * @return string
	 */
	function column($column)
	{
		$row = sqlite_fetch_array($this->result);
		
		return (is_array($row) && isset($row[$column])) ? $row[$column] : false;
	}
	
	/**
	 * Configure le mode de r�cup�ration par d�faut
	 * 
	 * @param integer $mode  Mode de r�cup�ration des donn�es
	 * 
	 * @access public
	 * @return boolean
	 */
	function setFetchMode($mode)
	{
		if( in_array($mode, array(SQLITE_NUM, SQLITE_ASSOC, SQLITE_BOTH)) ) {
			$this->fetchMode = $mode;
			return true;
		}
		else {
			trigger_error("Invalid fetch mode", E_USER_WARNING);
			return false;
		}
	}
	
	/**
	 * Lib�re la m�moire allou�e
	 * 
	 * @access public
	 * @return void
	 */
	function free()
	{
		if( !is_null($this->result) ) {
			$this->result = null;
		}
	}
	
	/**
	 * Destructeur de classe
	 * 
	 * @access public
	 * @return void
	 */
	function __destruct()
	{
		$this->free();
	}
}

class WadbBackup_sqlite {
	
	/**
	 * Connexion � la base de donn�es
	 * 
	 * @var object
	 * @access private
	 */
	var $db = null;
	
	/**
	 * Fin de ligne
	 * 
	 * @var boolean
	 * @access public
	 */
	var $eol = "\n";
	
	/**
	 * Constructeur de classe
	 * 
	 * @param object $db  Connexion � la base de donn�es
	 * 
	 * @access public
	 */
	function WadbBackup_sqlite($db)
	{
		$this->db = $db;
	}
	
	/**
	 * G�n�ration de l'en-t�te du fichier de sauvegarde
	 * 
	 * @param string $toolname  Nom de l'outil utilis� pour g�n�rer la sauvegarde
	 * 
	 * @access public
	 * @return string
	 */
	function header($toolname = '')
	{
		$host = function_exists('php_uname') ? @php_uname('n') : null;
		if( empty($host) ) {
			$host = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'Unknown Host';
		}
		
		$contents  = '-- ' . $this->eol;
		$contents .= "-- $toolname SQLite Dump" . $this->eol;
		$contents .= '-- ' . $this->eol;
		$contents .= "-- Host       : " . $host . $this->eol;
		$contents .= "-- SQLite lib : " . $this->db->libVersion . $this->eol;
		$contents .= "-- Database   : " . basename($this->db->dbname) . $this->eol;
		$contents .= '-- Date       : ' . date(DATE_RFC2822) . $this->eol;
		$contents .= '-- ' . $this->eol;
		$contents .= $this->eol;
		
		return $contents;
	}
	
	/**
	 * Retourne la liste des tables pr�sentes dans la base de donn�es consid�r�e
	 * 
	 * @access public
	 * @return array
	 */
	function get_tables()
	{
		$result = $this->db->query("SELECT tbl_name FROM sqlite_master WHERE type = 'table'");
		$tables = array();
		
		while( $row = $result->fetch() ) {
			$tables[$row['tbl_name']] = '';
		}
		
		return $tables;
	}
	
	/**
	 * Utilisable pour l'ajout de requ�te suppl�mentaires (s�quences, configurations diverses, etc)
	 * 
	 * @param boolean $drop_option
	 * 
	 * @access public
	 * @return string
	 */
	function get_other_queries($drop_option)
	{
		return '';
	}
	
	/**
	 * Retourne la structure d'une table de la base de donn�es sous forme de requ�te SQL de type DDL
	 * 
	 * @param array   $tabledata    Informations sur la table (provenant de self::get_tables())
	 * @param boolean $drop_option  Ajouter une requ�te de suppression conditionnelle de table
	 * 
	 * @access public
	 * @return string
	 */
	function get_table_structure($tabledata, $drop_option)
	{
		$contents  = '-- ' . $this->eol;
		$contents .= '-- Structure de la table ' . $tabledata['name'] . ' ' . $this->eol;
		$contents .= '-- ' . $this->eol;
		
		if( $drop_option ) {
			$contents .= 'DROP TABLE IF EXISTS ' . $this->db->quote($tabledata['name']) . ';' . $this->eol;
		}
		
		$sql = "SELECT sql, type
			FROM sqlite_master
			WHERE tbl_name = '$tabledata[name]'
				AND sql IS NOT NULL";
		$result = $this->db->query($sql);
		
		$indexes = '';
		while( $row = $result->fetch() ) {
			if( $row['type'] == 'table' ) {
				$create_table = str_replace(',', ',' . $this->eol, $row['sql']) . ';' . $this->eol;
			}
			else {
				$indexes .= $row['sql'] . ';' . $this->eol;
			}
		}
		
		$contents .= $create_table . $indexes;
		
		return $contents;
	}
	
	/**
	 * Retourne les donn�es d'une table de la base de donn�es sous forme de requ�tes SQL de type DML
	 * 
	 * @param string $tablename  Nom de la table � consid�rer
	 * 
	 * @access public
	 * @return string
	 */
	function get_table_data($tablename)
	{
		$contents = '';
		
		$result = $this->db->query('SELECT * FROM ' . $this->db->quote($tablename));
		$result->setFetchMode(SQLITE_ASSOC);
		
		if( $row = $result->fetch() ) {
			$contents  = $this->eol;
			$contents .= '-- ' . $this->eol;
			$contents .= '-- Contenu de la table ' . $tablename . ' ' . $this->eol;
			$contents .= '-- ' . $this->eol;
			
			$fields = array();
			for( $j = 0, $n = sqlite_num_fields($result->result); $j < $n; $j++ ) {
				array_push($fields, $this->db->quote(sqlite_field_name($result->result, $j)));
			}
			
			$fields = implode(', ', $fields);
			
			do {
				$contents .= sprintf("INSERT INTO %s (%s) VALUES", $this->db->quote($tablename), $fields);
				
				foreach( $row as $key => $value ) {
					if( is_null($value) ) {
						$row[$key] = 'NULL';
					}
					else {
						$row[$key] = '\'' . $this->db->escape($value) . '\'';
					}
				}
				
				$contents .= '(' . implode(', ', $row) . ');' . $this->eol;
			}
			while( $row = $result->fetch() );
		}
		
		return $contents;
	}
}

}

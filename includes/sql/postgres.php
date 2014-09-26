<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2014 Aur�lien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

if( !defined('_INC_CLASS_WADB_POSTGRES') ) {

define('_INC_CLASS_WADB_POSTGRES', true);

class Wadb_postgres {

	/**
	 * Type de base de donn�es
	 *
	 * @var string
	 * @access private
	 */
	var $engine = 'postgres';
	
	/**
	 * Connexion � la base de donn�es
	 * 
	 * @var resource
	 * @access private
	 */
	var $link;
	
	/**
	 * H�te de la base de donn�es
	 * 
	 * @var string
	 * @access public
	 */
	var $host = '';
	
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
	 * Version du serveur
	 * 
	 * @var string
	 * @access public
	 */
	var $serverVersion = '';
	
	/**
	 * Version du client
	 * 
	 * @var string
	 * @access public
	 */
	var $clientVersion = '';
	
	/**
	 * Nombre de lignes affect�es par la derni�re requ�te DML
	 * 
	 * @var integer
	 * @access private
	 */
	var $_affectedRows = 0;
	
	/**
	 * "Constantes" de la classe
	 */
	var $SQL_INSERT = 1;
	var $SQL_UPDATE = 2;
	var $SQL_DELETE = 3;
	
	/**
	 * Constructeur de classe
	 * 
	 * @param string $dbname   Nom de la base de donn�es
	 * @param array  $options  Options de connexion/utilisation
	 * 
	 * @access public
	 */
	function Wadb_postgres($dbname, $options = null)
	{
		$this->dbname = $dbname;
		
		if( is_array($options) ) {
			$this->options = array_merge($this->options, $options);
		}
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
		$connectString = "dbname='$this->dbname' ";
		
		if( is_array($infos) ) {
			foreach( array('host', 'username', 'passwd', 'port') as $info ) {
				if( isset($infos[$info]) ) {
					if( $info == 'username' ) {
						$connectString .= "user='$infos[$info]' ";
					}
					else if( $info == 'passwd' ) {
						$connectString .= "password='$infos[$info]' ";
					}
					else {
						$connectString .= "$info='$infos[$info]' ";
					}
				}
			}
			
			$this->host = $infos['host'] . (!is_null($infos['port']) ? ':'.$infos['port'] : '');
		}
		
		$connect = 'pg_connect';
		
		if( is_array($options) ) {
			$this->options = array_merge($this->options, $options);
		}
		
		if( !empty($this->options['persistent']) ) {
			$connect = 'pg_pconnect';
		}
		
		if( !($this->link = $connect($connectString)) || pg_connection_status($this->link) !== PGSQL_CONNECTION_OK ) {
			$this->error = @$php_errormsg;
			$this->link  = null;
		}
		else {
			$tmp = pg_version($this->link);
			$this->clientVersion = $tmp['client'];
			$this->serverVersion = $tmp['server'];
			
			if( !empty($this->options['charset']) ) {
				$this->encoding($this->options['charset']);
			}
		}
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
		$curEncoding = pg_client_encoding($this->link);
		
		if( !is_null($encoding) ) {
			pg_set_client_encoding($this->link, $encoding);
		}
		
		return $curEncoding;
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
		$result  = pg_query($this->link, $query);
		$endtime = array_sum(explode(' ', microtime()));
		
		$this->sqltime += ($endtime - $curtime);
		$this->lastQuery = $query;
		$this->queries++;
		
		if( !$result ) {
			$this->error = pg_last_error($this->link);
			
			$this->rollBack();
		}
		else {
			$this->error = '';
			
			if( in_array(strtoupper(substr($query, 0, 6)), array('INSERT', 'UPDATE', 'DELETE')) ) {
				$this->_affectedRows = @pg_affected_rows($result);
				$result = true;
			}
			
			if( !is_bool($result) ) {// on a r�ceptionn� une ressource ou un objet
				$result = new WadbResult_postgres($this->link, $result);
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
		return '"' . $name . '"';
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
			pg_query($this->link, 'VACUUM ' . $tablename);
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
		return pg_query($this->link, 'BEGIN');
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
		if( !($result = pg_query($this->link, 'COMMIT')) ) {
			pg_query($this->link, 'ROLLBACK');
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
		return pg_query($this->link, 'ROLLBACK');
	}
	
	/**
	 * Renvoie le nombre de lignes affect�es par la derni�re requ�te DML
	 * 
	 * @access public
	 * @return boolean
	 */
	function affectedRows()
	{
		return $this->_affectedRows;
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
		if( preg_match('/^INSERT\s+INTO\s+([^\s]+)\s+/i', $this->lastQuery, $match) ) {
			$result = pg_query($this->link, "SELECT currval('{$match[1]}_id_seq') AS lastId");
			
			if( is_resource($result) ) {
				return pg_fetch_result($result, 0, 'lastId');
			}
		}
		
		return false;
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
		return pg_escape_string($string);
	}
	
	/**
	 * V�rifie l'�tat de la connexion courante et effectue si besoin une reconnexion
	 * 
	 * @access public
	 * @return boolean
	 */
	function ping()
	{
		return pg_ping($this->link);
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
			$result = pg_close($this->link);
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
		return new WadbBackup_postgres($this);
	}
}

class WadbResult_postgres {
	
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
	var $SQL_FETCH_NUM   = PGSQL_NUM;
	var $SQL_FETCH_ASSOC = PGSQL_ASSOC;
	var $SQL_FETCH_BOTH  = PGSQL_BOTH;
	
	/**
	 * Constructeur de classe
	 * 
	 * @param resource $link    Ressource de connexion � la base de donn�es
	 * @param resource $result  Ressource de r�sultat de requ�te
	 * 
	 * @access public
	 */
	function WadbResult_postgres($link, $result)
	{
		$this->link   = $link;
		$this->result = $result;
		$this->fetchMode = PGSQL_BOTH;
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
		
		return pg_fetch_array($this->result, null, $mode);
	}
	
	/**
	 * Renvoie sous forme d'objet la ligne suivante dans le jeu de r�sultat
	 * 
	 * @access public
	 * @return object
	 */
	function fetchObject()
	{
		return pg_fetch_object($this->result);
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
		
		$rowset = array();
		while( $row = $this->fetch($mode) ) {
			array_push($rowset, $row);
		}
		
		return $rowset;
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
		$row = pg_fetch_array($this->result);
		
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
		if( in_array($mode, array(PGSQL_NUM, PGSQL_ASSOC, PGSQL_BOTH)) ) {
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
		if( !is_null($this->result) && is_resource($this->link) ) {
			pg_free_result($this->result);
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

/**
 * Certaines parties sont bas�es sur phpPgAdmin 2.4.2
 */
class WadbBackup_postgres {
	
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
	function WadbBackup_postgres($db)
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
		$contents  = '-- ' . $this->eol;
		$contents .= "-- $toolname PostgreSQL Dump" . $this->eol;
		$contents .= '-- ' . $this->eol;
		$contents .= "-- Host     : " . $this->db->host . $this->eol;
		$contents .= "-- Server   : " . $this->db->serverVersion . $this->eol;
		$contents .= "-- Database : " . $this->db->dbname . $this->eol;
		$contents .= '-- Date     : ' . date('d/m/Y H:i:s O') . $this->eol;
		$contents .= '-- ' . $this->eol;
		$contents .= $this->eol;
		
		$contents .= sprintf("SET NAMES '%s';%s", $this->db->encoding(), $this->eol);
		$contents .= "SET standard_conforming_strings = off;" . $this->eol;
		$contents .= "SET escape_string_warning = off;" . $this->eol;
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
		$sql = "SELECT tablename 
			FROM pg_tables 
			WHERE tablename NOT LIKE 'pg%' 
			ORDER BY tablename";
		if( !($result = $this->db->query($sql)) ) {
			trigger_error('Impossible d\'obtenir la liste des tables', ERROR);
		}
		
		$tables = array();
		while( $row = $result->fetch() ) {
			$tables[$row['tablename']] = '';
		}
		
		return $tables;
	}
	
	/**
	 * Retourne une cha�ne de requ�te pour la reg�n�ration des s�quences
	 * 
	 * @param boolean $drop_option  Ajouter une requ�te de suppression conditionnelle de s�quence
	 * 
	 * @access public
	 * @return string
	 */
	function get_other_queries($drop_option)
	{
		global $backup_type;
		
		$contents  = '-- ' . $this->eol;
		$contents .= '-- Sequences ' . $this->eol;
		$contents .= '-- ' . $this->eol;
		
		$sql = "SELECT relname
			FROM pg_class
			WHERE NOT relname ~ 'pg_.*' AND relkind ='S'
			ORDER BY relname";
		if( !($result = $this->db->query($sql)) ) {
			trigger_error('Impossible de r�cup�rer les s�quences', ERROR);
		}
		
		$contents = '';
		while( $sequence = $result->column('relname') ) {
			
			$result_seq = $this->db->query('SELECT * FROM ' . $this->db->quote($sequence));
			
			if( $row = $result_seq->fetch() ) {
				if( $drop_option ) {
					$contents .= "DROP SEQUENCE IF EXISTS ".$this->db->quote($sequence).";" . $this->eol;
				}
				
				$contents .= 'CREATE SEQUENCE ' . $this->db->quote($sequence)
					. ' start ' . $row['last_value']
					. ' increment ' . $row['increment_by']
					. ' maxvalue ' . $row['max_value']
					. ' minvalue ' . $row['min_value']
					. ' cache ' . $row['cache_value'] . '; ' . $this->eol;
				
				if( $row['last_value'] > 1 && $backup_type != 1 ) {
					//$contents .= 'SELECT NEXTVAL(\'' . $sequence . '\'); ' . $this->eol;
				}
			}
		}
		
		return $contents . $this->eol;
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
		$contents .= '-- Struture de la table ' . $tabledata['name'] . $this->eol;
		$contents .= '-- ' . $this->eol;
		
		if( $drop_option ) {
			$contents .= 'DROP TABLE IF EXISTS ' . $this->db->quote($tabledata['name']) . ';' . $this->eol;
		}
		
		$sql = "SELECT a.attnum, a.attname AS field, t.typname as type, a.attlen AS length, 
				a.atttypmod as lengthvar, a.attnotnull as notnull 
			FROM pg_class c, pg_attribute a, pg_type t 
			WHERE c.relname = '" . $tabledata['name'] . "' 
				AND a.attnum > 0 
				AND a.attrelid = c.oid 
				AND a.atttypid = t.oid 
			ORDER BY a.attnum";
		if( !($result = $this->db->query($sql)) ) {
			trigger_error('Impossible d\'obtenir le contenu de la table ' . $tabledata['name'], ERROR);
		}
		
		$contents .= 'CREATE TABLE ' . $this->db->quote($tabledata['name']) . ' (' . $this->eol;
		
		while( $row = $result->fetch() ) {
			$sql = "SELECT d.adsrc AS rowdefault 
				FROM pg_attrdef d, pg_class c 
				WHERE (c.relname = '" . $tabledata['name'] . "') 
					AND (c.oid = d.adrelid) 
					AND d.adnum = " . $row['attnum'];
			if( $res = $this->db->query($sql) ) {
				$row['rowdefault'] = $res->column('rowdefault');
			}
			else {
				unset($row['rowdefault']);
			}
			
			if( $row['type'] == 'bpchar' ) {
				// Internally stored as bpchar, but isn't accepted in a CREATE TABLE statement.
				$row['type'] = 'character';
			}
			
			$contents .= ' ' . $this->db->quote($row['field']) . ' ' . $row['type'];
			
			if( preg_match('#char#i', $row['type']) && $row['lengthvar'] > 0 ) {
				$contents .= '(' . ($row['lengthvar'] - 4) . ')';
			}
			else if( preg_match('#numeric#i', $row['type']) ) {
				$contents .= sprintf('(%s,%s)', (($row['lengthvar'] >> 16) & 0xffff), (($row['lengthvar'] - 4) & 0xffff));
			}
			
			if( $row['notnull'] == 't' ) {
				$contents .= ' DEFAULT ' . $row['rowdefault'];
				$contents .= ' NOT NULL';
			}
			
			$contents .= ',' . $this->eol;
		}
		
		//
		// Generate constraint clauses for UNIQUE and PRIMARY KEY constraints
		//
		$sql = "SELECT ic.relname AS index_name, bc.relname AS tab_name, ta.attname AS column_name, 
				i.indisunique AS unique_key, i.indisprimary AS primary_key 
			FROM pg_class bc, pg_class ic, pg_index i, pg_attribute ta, pg_attribute ia 
			WHERE (bc.oid = i.indrelid) 
				AND (ic.oid = i.indexrelid) 
				AND (ia.attrelid = i.indexrelid) 
				AND (ta.attrelid = bc.oid)
				AND (bc.relname = '" . $tabledata['name'] . "') 
				AND (ta.attrelid = i.indrelid) 
				AND (ta.attnum = i.indkey[ia.attnum-1]) 
			ORDER BY index_name, tab_name, column_name";
		if( !($result = $this->db->query($sql)) ) {
			trigger_error('Impossible de r�cup�rer les cl�s primaires et unique de la table ' . $tabledata['name'], ERROR);
		}
		
		$primary_key = $primary_key_name = '';
		$index_rows  = array();
		
		while( $row = $result->fetch() ) {
			if( $row['primary_key'] == 't' ) {
				$primary_key .= ( ( $primary_key != '' ) ? ', ' : '' ) . $row['column_name'];
				$primary_key_name = $row['index_name'];
			}
			else {
				//
				// We have to store this all this info because it is possible to have a multi-column key...
				// we can loop through it again and build the statement
				//
				$index_rows[$row['index_name']]['table']  = $tabledata['name'];
				$index_rows[$row['index_name']]['unique'] = ($row['unique_key'] == 't') ? 'UNIQUE' : '';
				
				if( !isset($index_rows[$row['index_name']]['column_names']) ) {
					$index_rows[$row['index_name']]['column_names'] = array();
				}
				
				$index_rows[$row['index_name']]['column_names'][] = $this->db->quote($row['column_name']);
			}
		}
		$result->free();
		
		if( !empty($primary_key) ) {
			$contents .= sprintf("CONSTRAINT %s PRIMARY KEY (%s),",
				$this->db->quote($primary_key_name), $this->db->quote($primary_key));
			$contents .= $this->eol;
		}
		
		$index_create = '';
		if( count($index_rows) ) {
			foreach( $index_rows as $idx_name => $props ) {
				$props['column_names'] = implode(', ', $props['column_names']);
				
				if( !empty($props['unique']) ) {
					$contents .= sprintf("CONSTRAINT %s UNIQUE (%s),",
						$this->db->quote($idx_name), $props['column_names']);
					$contents .= $this->eol;
				}
				else {
					$index_create .= sprintf("CREATE %s INDEX %s ON %s (%s);", $props['unique'],
						$this->db->quote($idx_name), $this->db->quote($tabledata['name']), $props['column_names']);
					$index_create .= $this->eol;
				}
			}
		}
		
		//
		// Generate constraint clauses for CHECK constraints
		//
/*		$sql = "SELECT rcname as index_name, rcsrc 
			FROM pg_relcheck, pg_class bc 
			WHERE rcrelid = bc.oid 
				AND bc.relname = '" . $tabledata['name'] . "' 
				AND NOT EXISTS (
					SELECT * 
					FROM pg_relcheck as c, pg_inherits as i 
					WHERE i.inhrelid = pg_relcheck.rcrelid 
						AND c.rcname = pg_relcheck.rcname 
						AND c.rcsrc = pg_relcheck.rcsrc 
						AND c.rcrelid = i.inhparent
				)";
		if( !($result = $this->db->query($sql)) ) {
			trigger_error('Impossible de r�cup�rer les clauses de contraintes de la table ' . $tabledata['name'], ERROR);
		}
		
		//
		// Add the constraints to the sql file.
		//
		while( $row = $result->fetch() ) {
			$contents .= 'CONSTRAINT ' . $this->db->quote($row['index_name']) . ' CHECK ' . $row['rcsrc'] . ',' . $this->eol;
		}
		*/
		$len = strlen(',' . $this->eol);
		$contents = substr($contents, 0, -$len);
		$contents .= $this->eol . ');' . $this->eol;
		
		if( !empty($index_create) ) {
			$contents .= $index_create;
		}
		
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
		
		$sql = 'SELECT * FROM ' . $this->db->quote($tablename);
		if( !($result = $this->db->query($sql)) ) {
			trigger_error('Impossible d\'obtenir le contenu de la table ' . $tablename, ERROR);
		}
		
		$result->setFetchMode(PGSQL_ASSOC);
		
		if( $row = $result->fetch() ) {
			$contents  = $this->eol;
			$contents .= '-- ' . $this->eol;
			$contents .= '-- Contenu de la table ' . $tablename . $this->eol;
			$contents .= '-- ' . $this->eol;
			
			$fields = array();
			for( $j = 0, $n = pg_num_fields($result->result); $j < $n; $j++ ) {
				array_push($fields, $this->db->quote(pg_field_name($result->result, $j)));
			}
			
			$fields = implode(', ', $fields);
			
			do {
				$contents .= sprintf("INSERT INTO %s (%s) VALUES", $this->db->quote($tablename), $fields);
				
				foreach( $row as $key => $value ) {
					if( is_null($value) ) {
						$row[$key] = 'NULL';
					}
					else {
						$row[$key] = '\'' . addcslashes($this->db->escape($value), "\r\n") . '\'';
					}
				}
				
				$contents .= '(' . implode(', ', $row) . ');' . $this->eol;
			}
			while( $row = $result->fetch() );
		}
		$result->free();
		
		return $contents;
	}
}

}
?>

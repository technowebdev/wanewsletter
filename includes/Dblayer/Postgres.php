<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2016 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

namespace Wanewsletter\Dblayer;

class Postgres extends Wadb
{
	/**
	 * Type de base de données
	 */
	const ENGINE = 'postgres';

	/**
	 * Version du serveur
	 *
	 * @var string
	 */
	public $serverVersion = '';

	/**
	 * Version du client
	 *
	 * @var string
	 */
	public $clientVersion = '';

	/**
	 * Nombre de lignes affectées par la dernière requète DML
	 *
	 * @var integer
	 */
	protected $_affectedRows = 0;

	/**
	 * Liste de séquences telle que ['dbname.tablename' => 'seqname']
	 *
	 * @var array
	 */
	protected static $seqlist = [];

	public function connect($infos = null, $options = null)
	{
		$infos   = (is_null($infos)) ? $this->infos : $infos;
		$options = (is_null($options)) ? $this->options : $options;

		$connectString = '';

		if (is_array($infos)) {
			foreach (['host', 'username', 'passwd', 'port', 'dbname'] as $info) {
				if (isset($infos[$info])) {
					if ($info == 'username') {
						$connectString .= "user='$infos[$info]' ";
					}
					else if ($info == 'passwd') {
						$connectString .= "password='$infos[$info]' ";
					}
					else {
						if ($info == 'host' && filter_var($infos['host'], FILTER_VALIDATE_IP)) {
							$connectString .= "hostaddr='$infos[host]' ";
							continue;
						}
						$connectString .= "$info='$infos[$info]' ";
					}
				}
			}

			$this->infos = $infos;
		}

		if (is_array($options)) {
			$this->options = array_merge($this->options, $options);
		}

		$connect = 'pg_connect';
		if (!empty($this->options['persistent'])) {
			$connect = 'pg_pconnect';
		}

		if (!empty($this->options['timeout']) && is_int($this->options['timeout'])) {
			$connectString .= sprintf('connect_timeout=%d ', $this->options['timeout']);
		}

		//
		// Options relatives aux protocoles SSL/TLS
		//
		foreach (['sslmode', 'sslcert', 'sslkey', 'sslrootcert'] as $key) {
			if (!empty($this->options[$key])) {
				$connectString .= sprintf("%s='%s' ", $key, $this->options[$key]);
			}
		}

		set_error_handler(function ($errno, $errstr) {
			$this->error = $errstr;
		});
		$this->link = $connect($connectString);
		restore_error_handler();

		if (!$this->link || pg_connection_status($this->link) !== PGSQL_CONNECTION_OK) {
			$this->errno = -1;
			$this->link  = null;

			throw new Exception($this->error, $this->errno);
		}
		else {
			$tmp = pg_version($this->link);
			$this->clientVersion = $tmp['client'];
			$this->serverVersion = $tmp['server'];

			if (!empty($this->options['charset'])) {
				$this->encoding($this->options['charset']);
			}
		}
	}

	public function encoding($encoding = null)
	{
		$curEncoding = pg_client_encoding($this->link);

		if (!is_null($encoding)) {
			pg_set_client_encoding($this->link, $encoding);
		}

		return $curEncoding;
	}

	public function query($query)
	{
		$curtime = array_sum(explode(' ', microtime()));
		$result  = pg_send_query($this->link, $query);
		$endtime = array_sum(explode(' ', microtime()));

		$this->sqltime += ($endtime - $curtime);
		$this->lastQuery = $query;
		$this->queries++;

		if ($result) {
			$result   = pg_get_result($this->link);
			$this->sqlstate = pg_result_error_field($result, PGSQL_DIAG_SQLSTATE);

			if (0 == $this->sqlstate) {
				$this->error = '';

				if (in_array(strtoupper(substr($query, 0, 6)), ['INSERT', 'UPDATE', 'DELETE'])) {
					$this->_affectedRows = pg_affected_rows($result);
					$result = true;
				}
				else {
					$result = new PostgresResult($result);
				}

				return $result;
			}
			else {
				$this->error = pg_result_error_field($result, PGSQL_DIAG_MESSAGE_PRIMARY);

				$this->rollBack();
			}
		}
		else {
			$this->error = 'Unknown error with database';
		}

		throw new Exception($this->error, $this->errno);
	}

	public function quote($name)
	{
		return pg_escape_identifier($this->link, $name);
	}

	public function vacuum($tables)
	{
		if (!is_array($tables)) {
			$tables = [$tables];
		}

		foreach ($tables as $tablename) {
			pg_query($this->link, 'VACUUM ' . $this->quote($tablename));
		}
	}

	public function beginTransaction()
	{
		return pg_query($this->link, 'BEGIN');
	}

	public function commit()
	{
		if (!($result = pg_query($this->link, 'COMMIT'))) {
			pg_query($this->link, 'ROLLBACK');
		}

		return $result;
	}

	public function rollBack()
	{
		return pg_query($this->link, 'ROLLBACK');
	}

	public function affectedRows()
	{
		return $this->_affectedRows;
	}

	public function lastInsertId()
	{
		if (preg_match('/^INSERT\s+INTO\s+([^\s]+)\s+/i', $this->lastQuery, $m)) {
			$tablename = trim($m[1], '"');// Revert éventuel de l'appel à self::quote()
			$key = $this->dbname . '.' . $tablename;

			if (!isset(self::$seqlist[$key]) ) {
				$sql = "SELECT s.relname AS seqname
					FROM pg_class s
						JOIN pg_depend d ON d.objid = s.oid
						JOIN pg_class t ON t.relname = '%s' AND d.refobjid = t.oid
						JOIN pg_namespace n ON n.oid = s.relnamespace
					WHERE s.relkind = 'S' AND n.nspname = 'public'";
				$sql = sprintf($sql, $tablename);
				$result = pg_query($this->link, $sql);

				if ($seqname = pg_fetch_result($result, 0, 'seqname')) {
					self::$seqlist[$key] = $seqname;
				}
			}
			else {
				$seqname = self::$seqlist[$key];
			}

			if ($seqname) {
				$result = pg_query($this->link, "SELECT currval('$seqname') AS lastId");
				return pg_fetch_result($result, 0, 'lastId');
			}
		}

		return false;
	}

	public function escape($string)
	{
		return pg_escape_string($this->link, $string);
	}

	public function ping()
	{
		return pg_ping($this->link);
	}

	public function close()
	{
		if (!is_null($this->link)) {
			@$this->rollBack();
			$result = pg_close($this->link);
			$this->link = null;

			return $result;
		}
		else {
			return true;
		}
	}

	public function initBackup()
	{
		return new PostgresBackup($this);
	}
}

class PostgresResult extends WadbResult
{
	public function fetch($mode = null)
	{
		$modes = [
			self::FETCH_NUM   => PGSQL_NUM,
			self::FETCH_ASSOC => PGSQL_ASSOC,
			self::FETCH_BOTH  => PGSQL_BOTH
		];

		return pg_fetch_array($this->result, null, $this->getFetchMode($modes, $mode));
	}

	public function fetchObject()
	{
		return pg_fetch_object($this->result);
	}

	public function column($column)
	{
		$row = pg_fetch_array($this->result);

		return (is_array($row) && isset($row[$column])) ? $row[$column] : false;
	}

	public function free()
	{
		if (!is_null($this->result)) {
			pg_free_result($this->result);
			$this->result = null;
		}
	}
}

/**
 * Certaines parties sont basées sur phpPgAdmin 2.4.2
 */
class PostgresBackup extends WadbBackup
{
	public function header($toolname = '')
	{
		$contents  = '-- ' . $this->eol;
		$contents .= "-- $toolname PostgreSQL Dump" . $this->eol;
		$contents .= '-- ' . $this->eol;
		$contents .= "-- Host     : " . $this->db->host . $this->eol;
		$contents .= "-- Server   : " . $this->db->serverVersion . $this->eol;
		$contents .= "-- Database : " . $this->db->dbname . $this->eol;
		$contents .= '-- Date     : ' . date(DATE_RFC2822) . $this->eol;
		$contents .= '-- ' . $this->eol;
		$contents .= $this->eol;

		$contents .= sprintf("SET NAMES '%s';%s", $this->db->encoding(), $this->eol);
		$contents .= "SET standard_conforming_strings = off;" . $this->eol;
		$contents .= "SET escape_string_warning = off;" . $this->eol;
		$contents .= $this->eol;

		return $contents;
	}

	public function getTablesList()
	{
		$sql = "SELECT tablename
			FROM pg_tables
			WHERE NOT tablename ~ '^(pg|sql)_'
			ORDER BY tablename";
		$result = $this->db->query($sql);
		$tables = [];

		while ($row = $result->fetch()) {
			$tables[] = $row['tablename'];
		}

		return $tables;
	}

	public function getStructure($tablename, $drop_option)
	{
		$contents  = '';
		$sequences = [];

		$sql = "SELECT a.attname AS fieldname, s.relname AS seqname
			FROM pg_class s
				JOIN pg_depend d ON d.objid = s.oid
				JOIN pg_class t ON t.relname = '%s' AND d.refobjid = t.oid
				JOIN pg_attribute a ON (d.refobjid, d.refobjsubid) = (a.attrelid, a.attnum)
				JOIN pg_namespace n ON n.oid = s.relnamespace
			WHERE s.relkind = 'S' AND n.nspname = 'public'";
		$sql = sprintf($sql, $this->db->escape($tablename));
		$result = $this->db->query($sql);

		while ($row = $result->fetch()) {
			$sql = sprintf('SELECT * FROM %s', $this->db->quote($row['seqname']));
			$result_seq = $this->db->query($sql);

			if ($seq = $result_seq->fetch()) {
				if (!isset($sequences[$tablename])) {
					$sequences[$tablename] = [];
				}
				$sequences[$tablename][$row['fieldname']] = $seq;
			}
		}

		$contents .= '--' . $this->eol;
		$contents .= '-- Structure de la table ' . $tablename . $this->eol;
		$contents .= '--' . $this->eol;

		if ($drop_option) {
			$contents .= sprintf("DROP TABLE IF EXISTS %s;%s", $this->db->quote($tablename), $this->eol);
		}

		if (isset($sequences[$tablename])) {
			$contents .= $this->eol;

			foreach ($sequences[$tablename] as $seq) {
				// Création de la séquence
				$contents .= sprintf("CREATE SEQUENCE %s start %d increment %d maxvalue %d minvalue %d cache %d;%s",
					$this->db->quote($seq['sequence_name']),
					$seq['start_value'],
					$seq['increment_by'],
					$seq['max_value'],
					$seq['min_value'],
					$seq['cache_value'],
					$this->eol
				);

				// Initialisation à sa valeur courante
				$last_value = $seq['last_value'];
				if ($seq['is_called'] == 't') {
					$last_value++;
				}

				$contents .= sprintf("SELECT setval('%s', %d, false);%s",
					$seq['sequence_name'],
					$last_value,
					$this->eol
				);
			}

			$contents .= $this->eol;
		}

		$sql = "SELECT a.attnum, a.attname AS field, t.typname as type, a.attlen AS length,
				a.atttypmod as lengthvar, a.attnotnull as notnull
			FROM pg_class c, pg_attribute a, pg_type t
			WHERE c.relname = '%s'
				AND a.attnum > 0
				AND a.attrelid = c.oid
				AND a.atttypid = t.oid
			ORDER BY a.attnum";
		$sql = sprintf($sql, $this->db->escape($tablename));
		$result = $this->db->query($sql);

		$contents .= sprintf("CREATE TABLE %s (%s", $this->db->quote($tablename), $this->eol);

		while ($row = $result->fetch()) {
			if ($row['notnull'] == 't') {
				$sql = "SELECT d.adsrc AS rowdefault
					FROM pg_attrdef d, pg_class c
					WHERE (c.relname = '%s')
						AND (c.oid = d.adrelid)
						AND d.adnum = %d";
				$sql = sprintf($sql, $this->db->escape($tablename), $row['attnum']);
				$res = $this->db->query($sql);
				$row['rowdefault'] = $res->column('rowdefault');
			}

			if ($row['type'] == 'bpchar') {
				// Internally stored as bpchar, but isn't accepted in a CREATE TABLE statement.
				$row['type'] = 'character';
			}

			$contents .= ' ' . $this->db->quote($row['field']) . ' ' . $row['type'];

			if (preg_match('#char#i', $row['type']) && $row['lengthvar'] > 0) {
				$contents .= '(' . ($row['lengthvar'] - 4) . ')';
			}
			else if (preg_match('#numeric#i', $row['type'])) {
				$contents .= sprintf('(%s,%s)',
					(($row['lengthvar'] >> 16) & 0xffff),
					(($row['lengthvar'] - 4) & 0xffff)
				);
			}

			if ($row['notnull'] == 't') {
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
				AND (bc.relname = '%s')
				AND (ta.attrelid = i.indrelid)
				AND (ta.attnum = i.indkey[ia.attnum-1])
			ORDER BY index_name, tab_name, column_name";
		$sql = sprintf($sql, $this->db->escape($tablename));
		$result = $this->db->query($sql);

		$primary_key_name = '';
		$primary_key_fields = [];
		$index_rows  = [];

		while ($row = $result->fetch()) {
			if ($row['primary_key'] == 't') {
				$primary_key_fields[] = $row['column_name'];
				$primary_key_name = $row['index_name'];
			}
			else {
				//
				// We have to store this all this info because it is possible to have a multi-column key...
				// we can loop through it again and build the statement
				//
				$index_rows[$row['index_name']]['table']  = $tablename;
				$index_rows[$row['index_name']]['unique'] = ($row['unique_key'] == 't') ? 'UNIQUE' : '';

				if (!isset($index_rows[$row['index_name']]['column_names'])) {
					$index_rows[$row['index_name']]['column_names'] = [];
				}

				$index_rows[$row['index_name']]['column_names'][] = $row['column_name'];
			}
		}
		$result->free();

		if (!empty($primary_key_name)) {
			$primary_key_fields = array_map([$this->db, 'quote'], $primary_key_fields);
			$contents .= sprintf("CONSTRAINT %s PRIMARY KEY (%s),%s",
				$this->db->quote($primary_key_name),
				implode(', ', $primary_key_fields),
				$this->eol
			);
		}

		$index_create = '';

		if (count($index_rows) > 0) {
			foreach ($index_rows as $idx_name => $props) {
				$props['column_names'] = array_map([$this->db, 'quote'], $props['column_names']);
				$props['column_names'] = implode(', ', $props['column_names']);

				if (!empty($props['unique'])) {
					$contents .= sprintf("CONSTRAINT %s UNIQUE (%s),%s",
						$this->db->quote($idx_name),
						$props['column_names'],
						$this->eol
					);
				}
				else {
					$index_create .= sprintf("CREATE %s INDEX %s ON %s (%s);%s",
						$props['unique'],
						$this->db->quote($idx_name),
						$this->db->quote($tablename),
						$props['column_names'],
						$this->eol
					);
				}
			}
		}

		//
		// Generate constraint clauses for CHECK constraints
		//
/*		$sql = "SELECT rcname as index_name, rcsrc
			FROM pg_relcheck, pg_class bc
			WHERE rcrelid = bc.oid
				AND bc.relname = '%s'
				AND NOT EXISTS (
					SELECT *
					FROM pg_relcheck as c, pg_inherits as i
					WHERE i.inhrelid = pg_relcheck.rcrelid
						AND c.rcname = pg_relcheck.rcname
						AND c.rcsrc = pg_relcheck.rcsrc
						AND c.rcrelid = i.inhparent
			)";
		$sql = sprintf($sql, $this->db->escape($tablename));
		$result = $this->db->query($sql);

		//
		// Add the constraints to the sql file.
		//
		while ($row = $result->fetch()) {
			$contents .= sprintf("CONSTRAINT %s CHECK %s,%s",
				$this->db->quote($row['index_name']),
				$row['rcsrc'],
				$this->eol
			);
		}*/

		$len = strlen(',' . $this->eol);
		$contents = substr($contents, 0, -$len);
		$contents .= $this->eol . ');' . $this->eol;

		if (!empty($index_create)) {
			$contents .= $index_create;
		}

		if (isset($sequences[$tablename])) {
			// Rattachement des séquences sur les champs liés
			foreach ($sequences[$tablename] as $field => $seq) {
				$contents .= sprintf("ALTER SEQUENCE %s OWNED BY %s.%s;%s",
					$this->db->quote($seq['sequence_name']),
					$this->db->quote($tablename),
					$this->db->quote($field),
					$this->eol
				);
			}
		}

		return $contents . $this->eol;
	}
}
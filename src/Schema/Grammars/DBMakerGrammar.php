<?php

/**
 * Created by syscom.
 * User: syscom
 * Date: 30/08/2019
 * Time: 15:50
 */
namespace DBMaker\ODBC\Schema\Grammars;

use RuntimeException;
use Illuminate\Support\Fluent;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Grammars\Grammar as Grammar;
use Illuminate\Database\Schema\Blueprint as Blueprint;

class DBMakerGrammar extends Grammar {
	/**
	 * The possible column modifiers.
	 *
	 * @var array
	 */
	protected $modifiers = [ 
			'Nullable',
			'Default',
			'After',
			'Before',
			'Increment' 
	];
	
	/**
	 * The possible column serials.
	 *
	 * @var array
	 */
	protected $serials = [ 
			'serial',
			'bigserial' 
	];
	
	/**
	 * Compile a rename column command.
	 *
	 * @param \Illuminate\Database\Schema\Blueprint $blueprint        	
	 * @param \Illuminate\Support\Fluent $command        	
	 * @param \Illuminate\Database\Connection $connection        	
	 * @return array
	 */
	public function compileRenameColumn(Blueprint $blueprint,
											Fluent $command,Connection $connection) {
		$from = $this->wrapTable($blueprint);
		return "ALTER TABLE {$from} MODIFY ("
				.$this->wrapTable($command->from)."NAME TO "
				.$this->wrapTable($command->to).")";
	}
	
	/**
	 * Compile a change column command into a seritypeEnumes of SQL statements.
	 *
	 * @param \Illuminate\Database\Schema\Blueprint $blueprint        	
	 * @param \Illuminate\Support\Fluent $command        	
	 * @param \Illuminate\Database\Connection $connection        	
	 * @return array
	 *
	 * @throws \RuntimeException
	 */
	public function compileChange(Blueprint $blueprint,
									 Fluent $command,Connection $connection) {
		$columns = $blueprint->getColumns();
		return collect($columns)->map(function($column)use($blueprint) {
			$columnNmae = $this->wrap($column);
			$columnType = $this->getType($column);
			$columnModifiers = $this->addModifiers("",$blueprint,$column);
			;
			return "ALTER TABLE ".$this->wrapTable($blueprint)
					." MODIFY ( ".$columnNmae." TO "
					.$columnNmae." ".$columnType." ".$columnModifiers.")";
		})->all();
	}
	
	/**
	 * Compile a rename table name command.
	 *
	 * @param \Illuminate\Database\Schema\Blueprint $blueprint        	
	 * @param \Illuminate\Support\Fluent $command        	
	 * @return string
	 */
	public function compileRename(Blueprint $blueprint,Fluent $command) {
		return "ALTER TABLE ".$this->wrapTable($blueprint)
				." RENAME TO ".$this->wrapTable($command->to)."";
	}
	
	/**
	 * Compile a rename index command.
	 *
	 * @param \Illuminate\Database\Schema\Blueprint $blueprint        	
	 * @param \Illuminate\Support\Fluent $command        	
	 * @return string
	 */
	public function compileRenameIndex(Blueprint $blueprint,Fluent $command) {
		return sprintf('ALTER INDEX %s ON %s RENAME TO %s',
				$this->wrap($command->from),
				$this->wrapTable($blueprint),$this->wrap($command->to));
	}
	
	/**
	 * Compile the SQL needed to drop all tables.
	 *
	 * @param array $tables        	
	 * @return string
	 */
	public function compileDropAllTables($tables) {
		return 'drop table '.$tables;
	}
	
	/**
	 * Compile the SQL needed to drop all views.
	 *
	 * @param array $views        	
	 * @return string
	 */
	public function compileDropAllViews($views) {
		return 'drop view '.$views;
	}
	
	/**
	 * Compile the SQL needed to retrieve all view names.
	 *
	 * @return string
	 */
	public function compileGetAllViews() {
		return 'select TABLE_NAME from systable where TABLE_TYPE = \'VIEW\'';
	}
	
	/**
	 * Compile the SQL needed to retrieve all table names.
	 *
	 * @return string
	 */
	public function compileGetAllTables() {
		return 'select TABLE_NAME from SYSTABLE';
	}
	
	/**
	 * Compile a drop table command.
	 *
	 * @param \Illuminate\Database\Schema\Blueprint $blueprint        	
	 * @param \Illuminate\Support\Fluent $command        	
	 * @return string
	 */
	public function compileDrop(Blueprint $blueprint,Fluent $command) {
		return "drop TABLE ".$this->wrapTable($blueprint)."";
	}
	
	/**
	 * Compile alter table commands for adding columns.
	 *
	 * @param \Illuminate\Database\Schema\Blueprint $blueprint        	
	 * @param \Illuminate\Support\Fluent $command        	
	 * @return array
	 */
	public function compileAdd(Blueprint $blueprint,Fluent $command) {
		$columns = $this->prefixArray('add column',$this->getColumns($blueprint));		
		return collect($columns)->map(function($column)use($blueprint) {
			return 'alter table '.$this->wrapTable($blueprint).' '.$column;
		})->all();
	}
	
	/**
	 * Compile a drop column command.
	 *
	 * @param \Illuminate\Database\Schema\Blueprint $blueprint        	
	 * @param \Illuminate\Support\Fluent $command        	
	 * @return string
	 */
	public function compileDropColumn(Blueprint $blueprint,Fluent $command) {
		$columns = $this->prefixArray('drop',$this->wrapArray($command->columns));
		return collect($columns)->map(function($column)use($blueprint) {
			return 'alter table '.$this->wrapTable($blueprint).' '.$column;
		})->all();	
	}
	
	/**
	 * Compile the query to determine the list of tables.
	 *
	 * @return string
	 */
	public function compileTableExists() {
		return "select * from SYSTABLE where TABLE_NAME = ?";
	}
	
	/**
	 * Compile the query to get all column name
	 *
	 * @param
	 *        	string
	 * @return string
	 */
	public function compileGetAllColumns($table) {
		return "select COLUMN_NAME from SYSCOLUMN where TABLE_NAME = '".$table."'";
	}
	
	/**
	 * Compile a drop table (if exists) command.
	 *
	 * @param \Illuminate\Database\Schema\Blueprint $blueprint        	
	 * @param \Illuminate\Support\Fluent $command        	
	 * @return string
	 */
	public function compileDropIfExists(Blueprint $blueprint, Fluent $command) {
		return "DROP TABLE IF EXISTS ".$this->wrapTable($blueprint)."";
	}
	
	/**
	 * Compile a drop primary key command.
	 *
	 * @param \Illuminate\Database\Schema\Blueprint $blueprint        	
	 * @param \Illuminate\Support\Fluent $command        	
	 * @return string
	 */
	public function compileDropPrimary(Blueprint $blueprint,Fluent $command) {
		return 'alter table '.$this->wrapTable($blueprint).' drop primary key';
	}
	
	/**
	 * Compile a drop unique key command.
	 *
	 * @param \Illuminate\Database\Schema\Blueprint $blueprint        	
	 * @param \Illuminate\Support\Fluent $command        	
	 * @return string
	 */
	public function compileDropUnique(Blueprint $blueprint,Fluent $command) {
		$index = $this->wrap($command->index);
		return "DROP INDEX {$index} FROM {$this->wrapTable($blueprint)}";
	}
	
	/**
	 * Compile a drop index command.
	 *
	 * @param \Illuminate\Database\Schema\Blueprint $blueprint        	
	 * @param \Illuminate\Support\Fluent $command        	
	 * @return string
	 */
	public function compileDropIndex(Blueprint $blueprint,Fluent $command) {
		$index = $this->wrap($command->index);
		return "DROP INDEX {$index} FROM {$this->wrapTable($blueprint)}";
	}
	
	/**
	 * Compile a primary key command.
	 *
	 * @param \Illuminate\Database\Schema\Blueprint $blueprint        	
	 * @param \Illuminate\Support\Fluent $command        	
	 * @return string
	 */
	public function compilePrimary(Blueprint $blueprint,Fluent $command) {
		$command->name(null);
		return $this->compileKey($blueprint,$command,'primary key');
	}
	
	/**
	 * Compile a unique key command.
	 *
	 * @param \Illuminate\Database\Schema\Blueprint $blueprint        	
	 * @param \Illuminate\Support\Fluent $command        	
	 * @return string
	 */
	public function compileUnique(Blueprint $blueprint,Fluent $command) {
		return $this->compileKey($blueprint,$command,'unique');
	}
	
	/**
	 * Compile a plain index key command.
	 *
	 * @param \Illuminate\Database\Schema\Blueprint $blueprint        	
	 * @param \Illuminate\Support\Fluent $command        	
	 * @return string
	 */
	public function compileIndex(Blueprint $blueprint,Fluent $command) {
		return $this->compileKey($blueprint,$command,'index');
	}
	
	/**
	 * Compile an index creation command.
	 *
	 * @param \Illuminate\Database\Schema\Blueprint $blueprint        	
	 * @param \Illuminate\Support\Fluent $command        	
	 * @param string $type        	
	 * @return string
	 */
	protected function compileKey(Blueprint $blueprint,Fluent $command,$type) {
		if ($type == 'index') {
			return sprintf('create index %s on %s  (%s)',
					$this->wrap($command->index),
					$this->wrapTable($blueprint),
					$this->columnize($command->columns));
		} else if ($type == 'unique') {
			return sprintf('create unique index %s on %s  (%s)',
					$this->wrap($command->index), 
					$this->wrapTable($blueprint), 
					$this->columnize($command->columns));
		} else {
			return sprintf('alter table %s add %s (%s)',
					$this->wrapTable($blueprint),
					$type,
					$this->columnize($command->columns));
		}
	}
	
	/**
	 * Compile a drop foreign key command.
	 *
	 * @param \Illuminate\Database\Schema\Blueprint $blueprint        	
	 * @param \Illuminate\Support\Fluent $command        	
	 * @return string
	 */
	public function compileDropForeign(Blueprint $blueprint,Fluent $command) {
		$index = $this->wrap($command->index);
		
		return "alter table {$this->wrapTable($blueprint)} drop foreign key {$index}";
	}
	
	/**
	 * Compile a create table command.
	 *
	 * @param \Illuminate\Database\Schema\Blueprint $blueprint        	
	 * @param \Illuminate\Support\Fluent $command        	
	 * @param \Illuminate\Database\Connection $connection        	
	 * @return string
	 */
	public function compileCreate(Blueprint $blueprint,
									 Fluent $command,Connection $connection) {
		return $sql = $this->compileCreateTable($blueprint,$command,$connection);
	}
	
	/**
	 * Create the main create table clause.
	 *
	 * @param \Illuminate\Database\Schema\Blueprint $blueprint        	
	 * @param \Illuminate\Support\Fluent $command        	
	 * @param \Illuminate\Database\Connection $connection        	
	 * @return string
	 */
	protected function compileCreateTable($blueprint,$command,$connection) {
		return sprintf('%s table %s (%s)',
				$blueprint->temporary ? 'create temporary' : 'create', 
				$this->wrapTable($blueprint),
				implode(', ',$this->getColumns($blueprint)));
	}
	
	/**
	 * Get the SQL for a default column modifier.
	 *
	 * @param \Illuminate\Database\Schema\Blueprint $blueprint        	
	 * @param \Illuminate\Support\Fluent $column        	
	 * @return string|null
	 */
	protected function modifyDefault(Blueprint $blueprint,Fluent $column) {
		if (! is_null($column->default)) {
			return ' default '.$this->getDefaultValue($column->default);
		}
	}
	
	/**
	 * Format a value so that it can be used in "default" clauses.
	 *
	 * @param mixed $value        	
	 * @return string
	 */
	protected function getDefaultValue($value) {
		if($value instanceof Expression) {
			return $value;
		}	
		if(is_bool($value)) {
			return (int)$value;
		}else if(is_int($value)||is_float($value)) {
			return $value;
		}else{
			return "'$value'";
		}
	}
	
	/**
	 * Get the SQL for an auto-increment column modifier.
	 *
	 * @param \Illuminate\Database\Schema\Blueprint $blueprint        	
	 * @param \Illuminate\Support\Fluent $column        	
	 * @return string|null
	 */
	protected function modifyIncrement(Blueprint $blueprint,Fluent $column) {
		if(in_array($column->type,$this->serials) && $column->autoIncrement) {
			return ' primary key';
		}
	}
	
	/**
	 * Create the column definition for a string type.
	 *
	 * @param Illuminate\Database\Schema\ColumnDefinition $column        	
	 * @return string
	 */
	protected function typeString(Fluent $column) {
		return "varchar({$column->length})";
	}
	
	/**
	 * Create the column definition for a big integer type.
	 *
	 * @param Illuminate\Database\Schema\ColumnDefinition $column        	
	 * @return string
	 */
	protected function typeBigInteger(Fluent $column) {
		return 'bigint';
	}
	
	/**
	 * Create the column definition for an integer type.
	 *
	 * @param Illuminate\Database\Schema\ColumnDefinition $column        	
	 * @return string
	 */
	protected function typeInteger(Fluent $column) {
		return 'int';
	}
	
	/**
	 * Create the column definition for an serial type.
	 *
	 * @param Illuminate\Database\Schema\ColumnDefinition $column        	
	 * @return string
	 */
	protected function typeSerial(Fluent $column) {
		return 'serial';
	}
	
	/**
	 * Create the column definition for an bigserial type.
	 *
	 * @param Illuminate\Database\Schema\ColumnDefinition $column        	
	 * @return string
	 */
	protected function typeBigserial(Fluent $column) {
		return 'bigserial';
	}
	
	/**
	 * Create the column definition for a binary type.
	 *
	 * @param Illuminate\Database\Schema\ColumnDefinition $column        	
	 * @return string
	 */
	protected function typeBinary(Fluent $column) {
		return 'blob';
	}
	
	/**
	 * Create the column definition for a boolean type.
	 *
	 * @param Illuminate\Database\Schema\ColumnDefinition $column        	
	 * @return string
	 */
	protected function typeBoolean(Fluent $column) {
		return $this->typeSmallInteger($column);
	}
	
	/**
	 * Create the column definition for a char type.
	 *
	 * @param Illuminate\Database\Schema\ColumnDefinition $column        	
	 * @return string
	 */
	protected function typeChar(Fluent $column) {
		return "char({$column->length})";
	}
	
	/**
	 * Create the column definition for a date type.
	 *
	 * @param Illuminate\Database\Schema\ColumnDefinition $column        	
	 * @return string
	 */
	protected function typeDate(Fluent $column) {
		return 'date';
	}
	
	/**
	 * Create the column definition for a date-time type.
	 *
	 * @param Illuminate\Database\Schema\ColumnDefinition $column        	
	 * @return string
	 */
	protected function typeDateTime(Fluent $column) {
		return $this->typeTimestamp($column);
	}
	
	/**
	 * Create the column definition for a decimal type.
	 *
	 * @param Illuminate\Database\Schema\ColumnDefinition $column        	
	 * @return string
	 */
	protected function typeDecimal(Fluent $column) {
		return "decimal({$column->total},{$column->places})";
	}
	
	/**
	 * Create the column definition for a double type.
	 *
	 * @param Illuminate\Database\Schema\ColumnDefinition $column        	
	 * @return string
	 */
	protected function typeDouble(Fluent $column) {
		return "Double";
	}
	
	/**
	 * Create the column definition for an enumeration type.
	 *
	 * @param Illuminate\Database\Schema\ColumnDefinition $column        	
	 * @return string
	 */
	protected function typeEnum(Fluent $column) {
		return sprintf('varchar(255) check ("%s" in (%s))',
				$column->name,$this->quoteString($column->allowed));
	}
	
	/**
	 * Create the column definition for a float type.
	 *
	 * @param Illuminate\Database\Schema\ColumnDefinition $column        	
	 * @return string
	 */
	protected function typeFloat(Fluent $column) {
		if ($column->total) {
			return "Float({$column->total})";
		}	
		return 'Float';
	}
	
	/**
	 * Create the column definition for a json type.
	 *
	 * @param Illuminate\Database\Schema\ColumnDefinition $column        	
	 * @return string
	 */
	protected function typeJson(Fluent $column) {
		return 'JSONCOLS';
	}
	
	/**
	 * Create the column definition for a jsonb type.
	 *
	 * @param Illuminate\Database\Schema\ColumnDefinition $column        	
	 * @return string
	 */
	protected function typeJsonb(Fluent $column) {
		return 'JSONCOLS';
	}
	
	/**
	 * Create the column definition for a long text type.
	 *
	 * @param Illuminate\Database\Schema\ColumnDefinition $column        	
	 * @return string
	 */
	protected function typeLongText(Fluent $column) {
		return 'long varchar';
	}
	
	/**
	 * Create the column definition for a medium integer type.
	 *
	 * @param Illuminate\Database\Schema\ColumnDefinition $column        	
	 * @return string
	 */
	protected function typeMediumInteger(Fluent $column) {
		return 'integer';
	}
	
	/**
	 * Create the column definition for a medium text type.
	 *
	 * @param Illuminate\Database\Schema\ColumnDefinition $column        	
	 * @return string
	 */
	protected function typeMediumText(Fluent $column) {
		return 'long varchar';
	}
	
	/**
	 * Create the column definition for a timestamp type.
	 *
	 * @param Illuminate\Database\Schema\ColumnDefinition $column        	
	 * @return string
	 */
	protected function typeTimestamp(Fluent $column) {
		return $column->useCurrent ? "TIMESTAMP default CURRENT_TIMESTAMP" : "TIMESTAMP";
	}
	
	/**
	 * Create the column definition for a small integer type.
	 *
	 * @param Illuminate\Database\Schema\ColumnDefinition $column        	
	 * @return string
	 */
	protected function typeSmallInteger(Fluent $column) {
		return 'smallint';
	}
	
	/**
	 * Create the column definition for a tiny integer type.
	 *
	 * @param Illuminate\Database\Schema\ColumnDefinition $column        	
	 * @return string
	 */
	protected function typeTinyInteger(Fluent $column) {
		return $this->typeSmallInteger($column);
	}
	
	/**
	 * Create the column definition for a text type.
	 *
	 * @param Illuminate\Database\Schema\ColumnDefinition $column        	
	 * @return string
	 */
	protected function typeText(Fluent $column) {
		return 'long varchar';
	}
	
	/**
	 * Create the column definition for a time type.
	 *
	 * @param Illuminate\Database\Schema\ColumnDefinition $column        	
	 * @return string
	 */
	protected function typeTime(Fluent $column) {
		return 'time';
	}
	
	/**
	 * Get the SQL for a nullable column modifier.
	 *
	 * @param \Illuminate\Database\Schema\Blueprint $blueprint        	
	 * @param \Illuminate\Support\Fluent $column        	
	 * @return string|null
	 */
	protected function modifyNullable(Blueprint $blueprint,Fluent $column) {
		if(in_array($column->type,array(
				"serial",
				"bigserial",
				"json",
				"jsonb" 
		)))
			return '';
		else
			return $column->nullable ? '' : ' not null';
	}
	
	/**
	 * Get the SQL for an "after" column modifier.
	 *
	 * @param \Illuminate\Database\Schema\Blueprint $blueprint        	
	 * @param \Illuminate\Support\Fluent $column        	
	 * @return string|null
	 */
	protected function modifyAfter(Blueprint $blueprint,Fluent $column) {
		if(! is_null($column->after)) {
			return ' after '.$this->wrap($column->after);
		}
	}
	
	/**
	 * Get the SQL for a "before" column modifier.
	 *
	 * @param \Illuminate\Database\Schema\Blueprint $blueprint        	
	 * @param \Illuminate\Support\Fluent $column        	
	 * @return string|null
	 */
	protected function modifyBefore(Blueprint $blueprint,Fluent $column) {
		if(! is_null($column->before)) {
			return ' before '.$this->wrap($column->before);
		}
	}
	
	/**
	 * Compile the command to enable foreign key constraints.
	 *
	 * @return string
	 */
	public function compileEnableForeignKeyConstraints() {
		return "CALL SETSYSTEMOPTION('FKCHK','1');";
	}
	
	/**
	 * Compile the command to disable foreign key constraints.
	 *
	 * @return string
	 */
	public function compileDisableForeignKeyConstraints() {
		return "CALL SETSYSTEMOPTION('FKCHK','0');";
	}
	
	/**
	 * Create the column definition for a date-time (with time zone) type.
	 *
	 * @param \Illuminate\Support\Fluent $column        	
	 * @return string
	 */
	protected function typeDateTimeTz(Fluent $column) {
		return $this->typeDateTime($column);
	}
	
	/**
	 * Create the column definition for a time (with time zone) type.
	 *
	 * @param \Illuminate\Support\Fluent $column        	
	 * @return string
	 */
	protected function typeTimeTz(Fluent $column) {
		return $this->typeTime($column);
	}
	
	/**
	 * Create the column definition for a timestamp (with time zone) type.
	 *
	 * @param \Illuminate\Support\Fluent $column        	
	 * @return string
	 */
	protected function typeTimestampTz(Fluent $column) {
		return $this->typeTimestamp($column);
	}
	
	/**
	 * Create the column definition for a uuid type.
	 *
	 * @param \Illuminate\Support\Fluent $column        	
	 * @return string
	 */
	protected function typeUuid(Fluent $column) {
		return 'char(36)';
	}
	
	/**
	 * Create the column definition for an IP4 or IPV6 address type.
	 *
	 * @param \Illuminate\Support\Fluent $column        	
	 * @return string
	 */
	protected function typeIpAddress(Fluent $column) {
		return 'varchar(45)';
	}
	
	/**
	 * Create the column definition for a MAC address type.
	 *
	 * @param \Illuminate\Support\Fluent $column        	
	 * @return string
	 */
	protected function typeMacAddress(Fluent $column) {
		return 'varchar(17)';
	}
	
	/**
	 * Create the column definition for a spatial Geometry type.
	 *
	 * @param \Illuminate\Support\Fluent $column        	
	 * @return string
	 */
	public function typeGeometry(Fluent $column) {
		return 'varchar(128)';
	}
	
	/**
	 * Create the column definition for a spatial Point type.
	 *
	 * @param \Illuminate\Support\Fluent $column        	
	 * @return string
	 */
	public function typePoint(Fluent $column) {
		return 'varchar(128)';
	}
	
	/**
	 * Create the column definition for a spatial LineString type.
	 *
	 * @param \Illuminate\Support\Fluent $column        	
	 * @return string
	 */
	public function typeLineString(Fluent $column) {
		return "LONG VARCHAR";
	}
	
	/**
	 * Create the column definition for a spatial Polygon type.
	 *
	 * @param \Illuminate\Support\Fluent $column        	
	 * @return string
	 */
	public function typePolygon(Fluent $column) {
		return "LONG VARCHAR";
	}
	
	/**
	 * Create the column definition for a spatial GeometryCollection type.
	 *
	 * @param \Illuminate\Support\Fluent $column        	
	 * @return string
	 */
	public function typeGeometryCollection(Fluent $column) {
		return "LONG VARCHAR";
	}
	
	/**
	 * Create the column definition for a spatial MultiPoint type.
	 *
	 * @param \Illuminate\Support\Fluent $column        	
	 * @return string
	 */
	public function typeMultiPoint(Fluent $column) {
		return "LONG VARCHAR";
	}
	
	/**
	 * Create the column definition for a spatial MultiLineString type.
	 *
	 * @param \Illuminate\Support\Fluent $column        	
	 * @return string
	 */
	public function typeMultiLineString(Fluent $column) {
		return "LONG VARCHAR";
	}
	
	/**
	 * Create the column definition for a spatial MultiPolygon type.
	 *
	 * @param \Illuminate\Support\Fluent $column        	
	 * @return string
	 */
	public function typeMultiPolygon(Fluent $column) {
		return "LONG VARCHAR";
	}
	
	/**
	 * Create the column definition for a generated, computed column type.
	 *
	 * @param \Illuminate\Support\Fluent $column        	
	 * @return void
	 *
	 * @throws \RuntimeException
	 */
	protected function typeComputed(Fluent $column) {
		throw new RuntimeException(
		    'This database driver requires a type, see the virtualAs / storedAs modifiers.');
	}
}

<?php namespace spitfire\storage\database\drivers\mysqlpdo;

/* 
 * The MIT License
 *
 * Copyright 2016 César de la Cal Bretschneider <cesar@magic3w.com>.
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

class Collection extends \spitfire\storage\database\Collection
{
	
	

	public function repair() {
		$table = $this->getTable();
		$stt = "DESCRIBE $table";
		$fields = $table->getFields();
		//Fetch the DB Fields and create on error.
		try {
			$query = $this->getDb()->execute($stt, Array(), false);
		}
		catch(Exception $e) {
			return $this->create();
		}
		//Loop through the exiting fields
		while (false != ($f = $query->fetch())) {
			try {
				$field = $this->getField($f['Field']);
				unset($fields[$field->getName()]);
			}
			catch(Exception $e) {/*Ignore*/}
		}
		
		foreach($fields as $field) $field->add();
	}
	
	/**
	 * Deletes this record from the database. This method's call cannot be
	 * undone. <b>[NOTICE]</b>Usually Spitfire will cascade all the data
	 * related to this record. So be careful calling it deliberately.
	 * 
	 * @param Model $record Database record to be deleted from the DB.
	 * 
	 */
	public function delete(Model$record) {
		$table = $this->getTable();
		$db    = $table->getDb();
		$key   = $record->getPrimaryData();
		
		$restrictions = Array();
		foreach ($key as $k => $v) {$restrictions[] = sprintf('%s = %s', $k, $db->quote($v));}
		
		$stt = sprintf('DELETE FROM %s WHERE %s',
			$table,
			implode(' AND ', $restrictions)
			);
		$db->execute($stt);
	}

	/**
	 * Modifies this record on high write environments. If two processes modify
	 * this record simultaneously they won't generate unconsistent data.
	 * This function is especially useful for counters i.e. pageviews, clicks,
	 * plays or any kind of transactions.
	 * 
	 * @throws PrivateException If the database couldn't handle the request.
	 * @param Model $record Database record to be modified.
	 * @param string $key
	 * @param int|float|double $diff
	 */
	public function increment(Model$record, $key, $diff = 1) {
		
		$table = $this->getTable();
		$db    = $table->getDb();
		$key   = $record->getPrimaryData();
		
		$restrictions = Array();
		foreach ($key as $k => $v) {$restrictions[] = "$k = $v";}
		
		$stt = sprintf('UPDATE %s SET `%s` = `%s` + %s WHERE %s',
			$table, 
			$key,
			$key,
			$db->quote($diff),
			implode(' AND ', $restrictions)
		);
		
		$db->execute($stt);
	}

	public function insert(Model$record) {
		$data = $record->getData();
		$table = $record->getTable();
		$db = $table->getDb();
		
		$write = Array();
                
		foreach ($data as $value) {
			$write = array_merge($write, $value->dbGetData());
		}
		
		$fields = array_keys($write);
		foreach ($fields as &$field) $field = '`' . $field . '`';
		unset($field);
		
		$quoted = array_map(Array($db, 'quote'), $write);
		
		$stt = sprintf('INSERT INTO %s (%s) VALUES (%s)',
			$table,
			implode(', ', $fields),
			implode(', ', $quoted)
			);
		$db->execute($stt);
		return $db->getConnection()->lastInsertId();
	}

	public function update(Model$record) {
		$data  = $record->getData();
		$table = $record->getTable();
		$db    = $table->getDb();
		$key   = $record->getPrimaryData();
		
		$restrictions = Array();
		foreach ($key as $k => $v) {$restrictions[] = "{$table->getField($k)} = {$db->quote($v)}";}
		
		$write = Array();
                
		foreach ($data as $value) {
			$write = array_merge($write, $value->dbGetData());
		}
		
		$quoted = Array();
		foreach ($write as $f => $v) { $quoted[] = "{$table->getField($f)} = {$db->quote($v)}"; }
		
		$stt = sprintf('UPDATE %s SET %s WHERE %s',
			$table, 
			implode(', ', $quoted),
			implode(' AND ', $restrictions)
		);
		
		$this->getDb()->execute($stt);
		
	}

	public function destroy() {
		$this->getDb()->execute('DROP TABLE ' . $this);
	}

	public function create() {
		
		$table = $this->getTable();
		$definitions = $table->columnDefinitions();
		$foreignkeys = $table->foreignKeyDefinitions();
		$pk = $table->getPrimaryKey();
		
		foreach($pk as &$f) { $f = '`' . $f->getName() .  '`'; }
		
		if (!empty($foreignkeys)) $definitions = array_merge ($definitions, $foreignkeys);
		
		if (!empty($pk)) $definitions[] = 'PRIMARY KEY(' . implode(', ', $pk) . ')';
		
		#Strip empty definitions from the list
		$clean = array_filter($definitions);
		
		$stt = sprintf('CREATE TABLE %s (%s)',
			$this,
			implode(', ', $clean)
			);
		
		return $table->getDb()->execute($stt);
	}

	public function getQueryInstance() {
		
	}

}
<?php

/*
 * MIT License (http://www.opensource.org/licenses/mit-license.php)
 *
 * Copyright (c) 2007, Slava Tretyak (aka restorer)
 * Zame Software Development (http://zame-dev.org)
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
 *
 * Zame CMS
 */

abstract class BaseMigration
{
    protected $_type_mappings = array(
        'integer' => 'int',
        'string' => 'varchar',
        'boolean' => array(
            'tinyint',
            array(
                'size' => 1
            ),
        ),
    );

    protected $_type_defaults = array(
        'varchar' => array(
            'size' => 255
        ),
    );

    protected $_command = null;

    public function __construct($command)
    {
        $this->_command = $command;
    }

    protected function query($sql)
    {
    	$cmd = new SDBCommand($sql);
    	$cmd->execute();
    }

    protected function drop_table($table)
    {
    	$cmd = new SDBCommand("DROP TABLE IF EXISTS @table");
    	$cmd->set('table', $table, SDB::TableName);
    	$cmd->execute();
    }

    protected function drop_column($table, $column)
    {
    	$cmd = new SDBCommand("ALTER TABLE @table DROP @column");
    	$cmd->set('table', $table, SDB::TableName);
    	$cmd->set('column', $column, SDB::FieldName);
    	$cmd->execute();
    }

    protected function add_column($table, $column, $data_type, $options=array())
    {
    	$cmd = new SDBCommand('ALTER TABLE @table ADD @column ');
    	$cmd->set('table', $table, SDB::TableName);
    	$cmd->set('column', $column, SDB::FieldName);

        if (array_key_exists(strtolower($data_type), $this->_type_mappings))
        {
            $data_type = $this->_type_mappings[strtolower($data_type)];

            if (is_array($data_type))
            {
                $options = $options + $data_type[1];
                $data_type = $data_type[0];
            }
        }

        if (array_key_exists(strtolower($data_type), $this->_type_defaults)) {
            $options = $options + $this->_type_defaults[strtolower($data_type)];
        }

        $cmd->command .= strtoupper($data_type);

        if (strtolower($data_type) == 'enum')
        {
            $values = array();

            if (array_key_exists('values', $options)) {
                $values = $options['values'];
            } elseif (array_key_exists('options', $options)) {
                $values = $options['options'];
            }

            if (!is_array($values) || !count($values)) {
                throw new Exception('Invalid enum values');
            }

            foreach ($values as &$val) {
                $val = SDB::quote($val);
            }

            $cmd->command .= '(' . join(',', $values) . ')';
        }
        elseif (array_key_exists('size', $options) && $options['size'] > 0)
        {
            $cmd->command .= '(' . $options['size'] . ')';
        }

        if (array_key_exists('unsigned', $options) && $options['unsigned']) {
        	$cmd->command .= ' UNSIGNED';
        }

        $cmd->command .= ((array_key_exists('null', $options) && $options['null']) ? ' NULL' : ' NOT NULL');
        $cmd->execute();
    }

    abstract function up();
    abstract function down();
}

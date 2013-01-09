<?php

/**
 * Copyright Alvin Alexander, http://www.devdaily.com, 2011. 
 * All Rights Reserved.
 *
 * This program is distributed free of charge under the terms and 
 * conditions of the GNU GPL v3. See our LICENSE file, or
 * http://www.gnu.org/licenses/gpl.html for more information.
 *
 * The MDB2 and Smarty libraries are included under their own
 * licensing terms.
 */

# REQUIREMENT: this file is required for the mapping of database field 
# types to java field types:
# import 'CrudDatabaseTableFieldTypes.inc';

# TODO might need this syntax for the include statement below:
# require_once(dirname(__FILE__) . "/file.php");

class DatabaseTable
{

  # the raw (unmodified) name of the database table in the database
  private $raw_table_name;

  # the possibly-modified name of the database table that you get after
  # the prefix has been removed. ex: 'myapp_projects' becomes 'projects'.
  private $clean_table_name;
  
  # any prefix you have before your table names, like 'myapp_'
  private $tablename_prefix;

  # the name of the database table as converted to a java classname
  # (camelcase rules for a class)
  private $camelcase_table_name;

  # the names of the database table fields as they appear in the database
  private $raw_field_names = array();

  # the name of the database table fields as they convert to camelcase names
  private $camelcase_field_names = array();

  # the database field types
  private $db_field_types = array();

  # the field types as they translate to java (and now scala)
  private $java_field_types = array();
  private $scala_field_types = array();
  private $play_field_types = array();
  
  # an array that tells whether the desired fields are required (not null), or not
  private $field_is_reqd = array();

  function set_tablename_prefix($tablename_prefix)
  {
    $this->tablename_prefix = $tablename_prefix;
  }

  function get_raw_table_name()
  {
    return $this->raw_table_name;
  }
  
  function set_raw_table_name($name)
  {
    $this->raw_table_name = $name;
  }

  function get_clean_table_name()
  {
    $pos = strpos($this->raw_table_name, $this->tablename_prefix);
    if ($pos !== false)
    {
      # the prefix ('myapp_') was found in the raw table name ('myapp_projects')
      $len = strlen($this->tablename_prefix);
      $this->clean_table_name = substr($this->raw_table_name, $len);
    }
    return $this->clean_table_name;
  }

  /**
   * returns the clean (prefix-removed), singular ('ies' -> 'y') table name.
   */
  function get_clean_table_name_singular()
  {
    #------------------------------------------------------------------------
    # TODO - PROBABLY WANT TO ADD A SWITCH SOMEWHERE (APP.CFG) TO LET THE
    #        USER CONTROL THIS BEHAVIOR.
    #------------------------------------------------------------------------
    # TODO - MAY WANT TO TEST THE STRING BEFORE JUST DOING THESE REPLACEMENTS
    #------------------------------------------------------------------------
    # convert strings like 'entities' to 'entity'
    $string = $this->clean_table_name;
    $pattern = '/ies$/';
    $replacement = 'y';
    # convert strings like 'processes' to 'process'
    $string = preg_replace($pattern, $replacement, $string);
    $pattern = '/es$/';
    $replacement = '';
    $string = preg_replace($pattern, $replacement, $string);
    # remove trailing 's' ... in most cases
    $pattern = '/([bcdfghjklmnpqrstvwxyz])s$/';
    $replacement = '$1';
    $string = preg_replace($pattern, $replacement, $string);


    return $string;
  }

  # set the raw database table field names array
  function set_raw_field_names($field_names)
  {
    $this->raw_field_names = $field_names;
  }
  
  # set the raw database table field types
  function set_db_field_types($field_types)
  {
    $this->db_field_types = $field_types;
  }
  
  function get_db_field_types()
  {
    return $this->db_field_types;
  }

  # setter/getter for '$field_is_reqd'
  function set_field_is_reqd($field_is_reqd)
  {
    $this->field_is_reqd = $field_is_reqd;
  }

  function get_field_is_reqd()
  {
    return $this->field_is_reqd;
  }

  
  # returns an array of java field types that corresponds to the database
  # table field types. a database field of 'integer' becomes 'int',
  # a database field of 'text' becomes 'String', etc.
  #
  # this function requires the '$field_types_map' array to be set; it
  # provides the mapping from database field types to java field types.
  function get_java_field_types()
  {
    include 'CrudDatabaseTableFieldTypes.inc';

    $count = 0;
    foreach ($this->db_field_types as $field_type)
    {
      #echo "type = " . $field_type;
      # this should be a simple map lookup (as long as all the key/value
      # pairs are defined).
      $java_field_type = $field_types_map[$field_type];
      if (isset($java_field_type))
      {
        $this->java_field_types[$count] = $java_field_type;
      }
      else
      {
        # couldn't find a corresponding value in the map
        $this->java_field_types[$count] = 'UNKNOWN';
      }
      $count++;
    }
    return $this->java_field_types;
  }
  
  # provides a mapping from database field types to scala field types
  # TODO refactor the code; this is almost the same as the previous function.
  # TODO maybe this function should be more like get_play_field_types, which does the work
  #      of determining whether a field should be an Option or not; i currently handle this
  #      in the template code.
  function get_scala_field_types()
  {
    include 'CrudDatabaseTableFieldTypes.inc';

    $count = 0;
    foreach ($this->db_field_types as $field_type)
    {
      $scala_field_type = $scala_field_types_map[$field_type];
      #echo "SCALA FIELD TYPE: $scala_field_type\n";
      if (isset($scala_field_type)) {
        $this->scala_field_type[$count] = $scala_field_type;
      }
      else {
        # couldn't find a corresponding value in the map
        $this->scala_field_type[$count] = 'UNKNOWN';
      }
      // kludge for the 'id' field
      if ($this->raw_field_names[$count] == 'id') $this->scala_field_type[$count] = 'Long';
      $count++;
    }
    return $this->scala_field_type;
  }

  # return an array of field types for the Scala Play Framework.
  function get_play_field_types()
  {
    include 'CrudDatabaseTableFieldTypes.inc';

    $count = 0;
    foreach ($this->db_field_types as $field_type)
    {
      if ($this->field_is_reqd[$count] == true) {
        $play_field_type = $play_reqd_field_types_map[$field_type];
      } else {
        $play_field_type = $play_optional_field_types_map[$field_type];
      }
      if (isset($play_field_type)) {
        $this->play_field_type[$count] = $play_field_type;
      } else {
        $this->play_field_type[$count] = 'UNKNOWN';
      }
      // kludge for the 'id' field
      if ($this->raw_field_names[$count] == 'id') $this->play_field_type[$count] = 'longNumber';
      $count++;
    }
    return $this->play_field_type;
  }

  
  # convert a plural name to a singular name, as in turning
  # 'users' into 'user'. useful for converting database table
  # names into java class names.
  # TODO - also need to handle 'es' at the end of a table name.
  private function turn_plural_into_singular($string)
  {
    # get the last character from the string
    $l = strlen($string);
    $start = $l -1;
    $last_char = substr($string, $start, 1);

    # if the last char is not an 's', just return the original string
    if ($last_char != 's') return $string;

    # get the last two characters; if they are 'es', remove those and return
    $es_check = substr($string, $start-1, 2);
    if ($es_check == 'es')
    {
      return substr($string, 0, $l-2);
    }

    # otherwise, remove the last character ('s'), and return
    return substr($string, 0, $l-1);
  }
  
  # convert a database table field name to a java class name
  # (foo_bar_baz -> FooBarBaz)
  function get_camelcase_table_name()
  {
    $table_name = $this->turn_plural_into_singular($this->raw_table_name);
    $arr = explode('_', $table_name);
    $l = count($arr);
    for ($i=0; $i<$l; $i++)
    {
      $arr[$i] = ucwords($arr[$i]);
    }
    return implode($arr);
  }
  
  # TODO - this is currently returning "users", and needs to return "user"
  # convert a database table field name to a java object name,
  # i.e., a java instance variable name
  # (foo_bar_baz -> fooBarBaz)
  function get_java_object_name()
  {
    $table_name = $this->turn_plural_into_singular($this->raw_table_name);
    $arr = explode('_', $table_name);
    $l = count($arr);
    for ($i=0; $i<$l; $i++)
    {
      if ($i != 0)
      {
        $arr[$i] = ucwords($arr[$i]);
      }
    }
    return implode($arr);
  }
  
  # returns the array of camelcase field names, i.e., the database table
  # field names converted to java field naming standards
  function get_camelcase_field_names()
  {
    $count = 0;
    foreach ($this->raw_field_names as $raw_field_name)
    {
      # create an array of words from the raw field name
      $words = explode('_', $raw_field_name);
      $length = count($words);
      for ($i=0; $i<$length; $i++)
      {
        if ($i != 0) $words[$i] = ucwords($words[$i]);
      }
      $this->camelcase_field_names[$count] = implode($words);
      $count++;
    }
    return $this->camelcase_field_names;
  }
  
  # returns the table fields as a csv list; this is very useful for Dao
  # SQL INSERT statements.
  # example string for four fields: "id,foo,bar,baz"
  function get_fields_as_insert_stmt_csv_list()
  {
    $s = '';
    $count = 0;
    foreach ($this->raw_field_names as $raw_field_name)
    {
      $l = count($this->raw_field_names);
      if ($count < $l-1)
      {
        $s = $s . $raw_field_name . ',';
      }
      else
      {
        $s = $s . $raw_field_name;
      }
      $count++;
    }
    return $s;
  }

  # returns a csv-string of '?' corresponding to the database table fields;
  # very useful for Dao SQL INSERT statements.
  # example string for four fields: "?,?,?,?"
  function get_prep_stmt_insert_csv_string()
  {
    $s = '';
    $count = 0;
    foreach ($this->raw_field_names as $raw_field_name)
    {
      $l = count($this->raw_field_names);
      if ($count < $l-1)
      {
        $s = $s . '?,';
      }
      else
      {
        $s = $s . '?';
      }
      $count++;
    }
    return $s;
  }

  # returns a string like "id=?,foo=?,bar=?,baz=?", which is very useful for 
  # SQL UPDATE statements, using the Java PreparedStatement syntax.
  function get_fields_as_update_stmt_csv_list()
  {
    $s = '';
    $count = 0;
    foreach ($this->raw_field_names as $raw_field_name)
    {
      $l = count($this->raw_field_names);
      if ($count < $l-1)
      {
        $s = $s . $raw_field_name . '=?,';
      }
      else
      {
        $s = $s . $raw_field_name . '=?';
      }
      $count++;
    }
    return $s;
  }


}

# TODO - move all of this to some unit tests

#$dt = new DatabaseTable();

# --- TEST THE TABLE NAME ---
# setting the raw table name should create camelcase_table_name
#$dt->set_raw_table_name('foo_bar_baz');
#print $dt->get_raw_table_name() . "\n";
#print $dt->get_camelcase_table_name() . "\n";

# --- TEST THE TABLE FIELD NAMES ---
#$t_fields = array('a_foo', 'b_bar');
#$dt->set_raw_field_names($t_fields);

#$fields = $dt->get_camelcase_field_names();
#foreach ($fields as $f)
#{
#  print "$f\n";
#}

# --- TEST THE TABLE FIELD TYPES ---
# setting the db field types should create the java field types
#$types = array('integer', 'text', 'poop');
#$dt->set_db_field_types($types);
#$j_types = $dt->get_java_field_types();
#foreach ($j_types as $jt)
#{
#  print "$jt\n";
#}

#print_r($dt);

?>

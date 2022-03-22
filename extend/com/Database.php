<?php

namespace com;
use think\Db;
use think\Config;
use think\Session;

//Data export model
class Database{
    /**
     * file pointer
     * @var resource
     */
    private $fp;

    /**
     * Backup file information part - volume number, name - file name
     * @var array
     */
    private $file;

    /**
     * Current open file size
     * @var integer
     */
    private $size = 0;

    /**
     * Backup configuration
     * @var integer
     */
    private $config;

    /**
     * Database backup construction method
     * @param array $file backup or restore file information
     * @param array $config backup configuration information
     * @param string $type execution type, export - backup data, import - restore data
     */
    public function __construct($file, $config, $type = 'export'){
        $this->file   = $file;
        $this->config = $config;
    }

    /**
     * Open a volume for writing data
          * @param integer $size the size of the written data
     */
    private function open($size){
        if($this->fp){
            $this->size += $size;
            if($this->size > $this->config['part']){
                $this->config['compress'] ? @gzclose($this->fp) : @fclose($this->fp);
                $this->fp = null;
                $this->file['part']++;
                Session::set('backup_file', $this->file);
                $this->create();
            }
        } else {
            $backuppath = $this->config['path'];
            $filename   = "{$backuppath}{$this->file['name']}-{$this->file['part']}.sql";
            if($this->config['compress']){
                $filename = "{$filename}.gz";
                $this->fp = @gzopen($filename, "a{$this->config['level']}");
            } else {
                $this->fp = @fopen($filename, 'a');
            }
            $this->size = filesize($filename) + $size;
        }
    }

    /**
     * write initial data
     * @return boolean true - write succeeded, false - write failed
     */
    public function create(){
        $sql  = "-- -----------------------------\n";
        $sql .= "-- Think MySQL Data Transfer \n";
        $sql .= "-- \n";
        $sql .= "-- Host     : " . Config::get('database.hostname') . "\n";
        $sql .= "-- Port     : " . Config::get('database.hostport') . "\n";
        $sql .= "-- Database : " . Config::get('database.database') . "\n";
        $sql .= "-- \n";
        $sql .= "-- Part : #{$this->file['part']}\n";
        $sql .= "-- Date : " . date("Y-m-d H:i:s") . "\n";
        $sql .= "-- -----------------------------\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
        return $this->write($sql);
    }

    /**
   * Write SQL statement
     * @param string $sql SQL statement to write
     * @return boolean true - write succeeded, false - write failed!
     */
    private function write($sql){
        $size = strlen($sql);
        
        //Due to compression, the length after compression cannot be calculated. Here, the compression rate is assumed to be 50%.
        //Generally, the compression rate will be higher than 50%;
        $size = $this->config['compress'] ? $size / 2 : $size;
        
        $this->open($size);
        return $this->config['compress'] ? @gzwrite($this->fp, $sql) : @fwrite($this->fp, $sql);
    }

    /**
     * Backup table structure
     * @param string $table table name
     * @param integer $start starting line number
     * @return boolean false - backup failed
     */
    public function backup($table, $start){
        //Create DB object
        $db = Db::connect();

        // backup table structure
        if(0 == $start){
            $result = $db->query("SHOW CREATE TABLE `{$table}`");
            $sql  = "\n";
            $sql .= "-- -----------------------------\n";
            $sql .= "-- Table structure for `{$table}`\n";
            $sql .= "-- -----------------------------\n";
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sql .= trim($result[0]['Create Table']) . ";\n\n";
            if(false === $this->write($sql)){
                return false;
            }
        }

        // total data
        $result = $db->query("SELECT COUNT(*) AS count FROM `{$table}`");
        $count = $result['0']['count'];
            
        // backup table data
        if($count){
            // write data annotation
            if(0 == $start){
                $sql = "-- -----------------------------\n";
                $sql .= "-- Records of `{$table}`\n";
                $sql .= "-- -----------------------------\n";
                $this->write($sql);
            }

            // backup data record
            $result = $db->query("SELECT * FROM `{$table}` LIMIT {$start}, 1000");
            foreach ($result as $row) {
                $row = array_map('addslashes', $row);
                $sql = "INSERT INTO `{$table}` VALUES ('" . str_replace(array("\r","\n"),array('\r','\n'),implode("', '", $row)) . "');\n";
                if(false === $this->write($sql)){
                    return false;
                }
            }

           // there is more data
            if($count > $start + 1000){
                return array($start + 1000, $count);
            }
        }

        // backup next table
        return 0;
    }

    public function import($start){
        // restore data
        $db = Db::connect();

        if($this->config['compress']){
            $gz   = gzopen($this->file[1], 'r');
            $size = 0;
        } else {
            $size = filesize($this->file[1]);
            $gz   = fopen($this->file[1], 'r');
        }
        
        $sql  = '';
        if($start){
            $this->config['compress'] ? gzseek($gz, $start) : fseek($gz, $start);
        }
        
        for($i = 0; $i < 1000; $i++){
            $sql .= $this->config['compress'] ? gzgets($gz) : fgets($gz); 
            if(preg_match('/.*;$/', trim($sql))){
                if(false !== $db->execute($sql)){
                    $start += strlen($sql);
                } else {
                    return false;
                }
                $sql = '';
            } elseif ($this->config['compress'] ? gzeof($gz) : feof($gz)) {
                return 0;
            }
        }

        return array($start, $size);
    }

    /**
     * Destructor method for closing file resources
     */
    public function __destruct(){
        $this->config['compress'] ? @gzclose($this->fp) : @fclose($this->fp);
    }
}
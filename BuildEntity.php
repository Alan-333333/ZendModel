<?php
/**
 * @Author:  huangql
 * @Date:  2018/8/14 上午10:18
 * @Email:  hql@btctrade.com
 * @Desc:  自动生成数据表的Entity类
 * @Useage: php BuildEntiy.php table1 table2 table3 table4
 */

require_once '../../autoload.php';

array_shift($argv);
$tables = $argv;
$isEnabledYafNameSpace = (int)ini_get('yaf.use_namespace');

if ($isEnabledYafNameSpace == 0) {
    $config = new Yaf_Config_Ini('../../../conf/application.ini', 'common');
} else {
    $config = new \Yaf\Config\Ini('../../../conf/application.ini', 'common');
}

$dbConfig = $config->toArray()['database'];
$adapter = \Btctrade\ZendModel\AdapterFactory::create($dbConfig['master'])->getAdapter();
$classEntityFilePath = '../../../application/models/Entity';

foreach ($tables as $table) {
    $sql = "SHOW TABLES LIKE '%" . $table . "%'";
    $resultSet = $adapter->query($sql, $adapter::QUERY_MODE_EXECUTE);
    $tablesLike = array_values($resultSet->toArray()[0]);
    if (!in_array($table, $tablesLike)) {
        echo $table . ' is not exist in this db' . PHP_EOL;
        continue;
    }

    $stringTmp = ucfirst(\Btctrade\ZendModel\Tool::underline2Camel($table));
    $classFileName = $stringTmp . 'Entity.php';
    $className = $stringTmp . 'EntityModel';
    if ($isEnabledYafNameSpace == 0) {
        $className = 'Entity_' . $className;
    }

    file_put_contents("{$classEntityFilePath}/$classFileName", "<?php\n");
    if ($isEnabledYafNameSpace == 1) {
        file_put_contents(
            "{$classEntityFilePath}/$classFileName",
            "namespace Entity;\n\n",
            FILE_APPEND
        );
    }
    file_put_contents(
        "{$classEntityFilePath}/$classFileName",
        "\nuse \Btctrade\ZendModel\AbstractEntity;\n\n",
        FILE_APPEND
    );
    $string = "/**\n";
    $string .= " * 功能:{$table}表字段映射类\n";
    $date = date("Y/m/d");
    $string .= " * @version v1.0  {$date}\n";
    $string .= " */\n\n";
    $string .= "class {$className} extends AbstractEntity\n{\n";
    file_put_contents("{$classEntityFilePath}/$classFileName", $string, FILE_APPEND);
    $sql = "SHOW FULL COLUMNS FROM `" . $table . "`";
    $resultSet = $adapter->query($sql, $adapter::QUERY_MODE_EXECUTE);
    $columnInfo = $resultSet->toArray();
    $methodArray = [];
    foreach ($columnInfo as $column) {
        $varType = "string";
        if (preg_match('/^(\w+)(?:\(([^\)]+)\))?/', $column['Type'], $matches)) {
            $type = strtolower($matches[1]);
            if ($type == 'float' || $type == 'decimal' || $type == 'double' || $type == 'real') {
                $varType = "float";
            } else {
                $pos = stripos($type, 'int');
                if ($pos !== false || $type == 'bit') {
                    $varType = "integer";
                }
                $pos = stripos($type, 'char');
                $lengthStr = '';
                if ($pos !== false) {
                    $lengthStr = ",length=" . $matches[2];
                }
            }
        }

        $varRequired = 'false';
        if ($column['Null'] == 'NO') {
            $varRequired = 'true';
        }
        $string = "    /**\n";
        $string .= "     * {$column['Comment']}\n";
        $string .= "     * @Column({name={$column['Field']},type=\"{$varType}\",required={$varRequired}{$lengthStr}})\n";
        $string .= "     * @var {$varType}\n";
        $string .= "     */\n";
        $varName = \Btctrade\ZendModel\Tool::underline2Camel($column['Field']);
        $string .= "    protected \${$varName};\n\n";
        file_put_contents("{$classEntityFilePath}/$classFileName", $string, FILE_APPEND);


        $mutator = "get" . ucfirst($varName);
        $string = "    public function {$mutator}()\n";
        $string .= "    {\n";
        $string .= "        return \$this->{$varName};\n";
        $string .= "    }\n\n";

        $methodArray[] = $string;
        //file_put_contents("{$classEntityFilePath}/$classFileName", $string, FILE_APPEND);

        $mutator = "set" . ucfirst($varName);
        $string = "    public function {$mutator}(\${$varName})\n";
        $string .= "    {\n";
        $string .= "        \$this->{$varName} = \${$varName};\n";
        $string .= "    }\n\n";
        $methodArray[] = $string;
        //file_put_contents("{$classEntityFilePath}/$classFileName", $string, FILE_APPEND);
    }
    foreach ($methodArray as $methodStr) {
        file_put_contents("{$classEntityFilePath}/$classFileName", $methodStr, FILE_APPEND);
    }

    file_put_contents("{$classEntityFilePath}/$classFileName", "}\n\n", FILE_APPEND);

    echo $table . " gen entity class ok, path is " . realpath($classEntityFilePath . "/" . $classFileName) . "\n";
}

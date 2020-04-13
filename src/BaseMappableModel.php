<?php
namespace Btctrade\ZendModel;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Select;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\TableGateway\Feature\MasterSlaveFeature;

class BaseMappableModel
{
    private $tableName = '';
    private $pkColumn = 'id';
    private $calledClassName = '';
    private $tableGateWay = null;
    private $adapterMaster = null;
    private $adapterSlave = null;
    private $driveConf = [];
    private static $isBeginTransaction = false;
    private static $isEnabledYafNameSpace = 0;
    private static $tmpSlaveAdapter = null;
    private static $isEnableMasterSelect = false;

    public function __construct($tableName = '', $pkColumn = 'id', $yafDBConfNode = 'database')
    {
        $this->calledClassName = get_called_class();
        if (empty($tableName) && empty($this->tableName)) {
            $tableName = str_replace('Model', '', $this->calledClassName);
            $tableName = strtolower(trim(Tool::camel2Underline($tableName), '_'));
        }
        $this->setDriverConf($yafDBConfNode)
            ->setAdapter()
            ->setTableName($tableName)
            ->setPrimaryKeyColumn($pkColumn);
    }

    /**
     * Method  setDriverConf
     * @desc  yaf的application.ini中数据库配合的节点名称
     *
     * @author  huangql <hql@btctrade.com>
     * @param string $yafDBConfNode
     * @throws \Exception
     *
     * @return  $this
     */
    protected function setDriverConf($yafDBConfNode = '')
    {
        self::$isEnabledYafNameSpace = (int)ini_get('yaf.use_namespace');
        if (self::$isEnabledYafNameSpace == 0) {
            $config = \Yaf_Registry::get('config')->$yafDBConfNode->toArray();
        } else {
            $config = \Yaf\Registry::get('config')->$yafDBConfNode->toArray();
        }
        if (empty($config)) {
            throw new \Exception('Database driver config should be set before using');
        }
        $this->driveConf = $config;
        return $this;
    }

    /**
     * Method  setAdapter
     * @desc  选择数据库适配器
     *
     * @author  huangql <hql@btctrade.com>
     * @param $dbName
     *
     * @return  $this
     */
    protected function setAdapter()
    {
        $masterConf = $this->driveConf['master'];
        $slaveConf = $this->driveConf['slave'];
        $this->adapterMaster = AdapterFactory::create($masterConf)->getAdapter();
        if (is_array($slaveConf) && $slaveConf) {
            $this->adapterSlave = AdapterFactory::create($slaveConf)->getAdapter();
        } else {
            $this->adapterSlave = $this->adapterMaster;
        }
        return $this;
    }

    /**
     * Method  getMasterAdapter
     * @desc  获取写库适配器
     *
     * @author  huangql <hql@btctrade.com>
     * @throws \Exception
     *
     * @return  Adapter
     */
    protected function getMasterAdapter()
    {
        if (is_null($this->adapterMaster)) {
            throw new \Exception('Database Master adapter should be init before using');
        }
        return $this->adapterMaster;
    }

    /**
     * Method  getSlaveAdapter
     * @desc  获取读库适配器
     *
     * @author  huangql <hql@btctrade.com>
     * @throws \Exception
     *
     * @return  Adapter
     */
    protected function getSlaveAdapter()
    {
        if (is_null($this->adapterSlave)) {
            throw new \Exception('Database Slave adapter should be init before using');
        }
        return $this->adapterSlave;
    }

    /**
     * Method  setTableName
     * @desc  ......
     *
     * @author  huangql <hql@btctrade.com>
     * @param $tableName
     *
     * @return  $this
     */
    protected function setTableName($tableName)
    {
        $this->tableName = $tableName;
        return $this;
    }

    /**
     * Method  getTableName
     * @desc  ......
     *
     * @author  huangql <hql@btctrade.com>
     *
     * @return  string
     */
    protected function getTableName()
    {
        return $this->tableName;
    }

    /**
     * Method  getPrimaryKeyColumn
     * @desc  ......
     *
     * @author  huangql <hql@btctrade.com>
     *
     * @return  string
     */
    protected function getPrimaryKeyColumn()
    {
        return $this->pkColumn;
    }

    /**
     * Method  setPrimaryKeyColumn
     * @desc  ......
     *
     * @author  huangql <hql@btctrade.com>
     * @param $pkColumn
     *
     * @return  $this
     */
    protected function setPrimaryKeyColumn($pkColumn)
    {
        $this->pkColumn = $pkColumn;
        return $this;
    }

    /**
     * Method  setTableGw
     * @desc  设置数据表网关
     *
     * @author  huangql <hql@btctrade.com>
     * @throws \Exception
     *
     * @return  null|TableGateway
     */
    private function setTableGw()
    {
        $features = [];
        if ($this->getSlaveAdapter()) {
            $features[] = new MasterSlaveFeature($this->getSlaveAdapter());
        }
        $this->tableGateWay = new TableGateway(
            $this->getTableName(),
            $this->getMasterAdapter(),
            $features
        );
        return $this->tableGateWay;
    }

    /**
     * Method  getTableGw
     * @desc  ......
     *
     * @author  huangql <hql@btctrade.com>
     * @throws \Exception
     *
     * @return  TableGateway
     */
    private function getTableGw()
    {
        if (!$this->tableGateWay) {
            return $this->setTableGw();
        }
        return $this->tableGateWay;
    }

    /**
     * Method  getSelect
     * @desc  获取zend db select对象
     *
     * @author  huangql <hql@btctrade.com>
     * @throws \Exception
     *
     * @return  \Zend\Db\Sql\Select
     */
    private function getSelect()
    {
        return $this->getTableGw()->getSql()->select();
    }

    /**
     * Method  getInsert
     * @desc  获取zend db insert对象
     *
     * @author  huangql <hql@btctrade.com>
     * @throws \Exception
     *
     * @return  \Zend\Db\Sql\Insert
     */
    private function getInsert()
    {
        return $this->getTableGw()->getSql()->insert();
    }

    /**
     * Method  getUpdate
     * @desc  获取zend db update对象
     *
     * @author  huangql <hql@btctrade.com>
     * @throws \Exception
     *
     * @return  \Zend\Db\Sql\Update
     */
    private function getUpdate()
    {
        return $this->getTableGw()->getSql()->update();
    }

    /**
     * Method  getDelete
     * @desc  获取zend db delete对象
     *
     * @author  huangql <hql@btctrade.com>
     * @throws \Exception
     *
     * @return  \Zend\Db\Sql\Delete
     */
    private function getDelete()
    {
        return $this->getTableGw()->getSql()->delete();
    }

    /**
     * Method  insert
     * @desc  插入数据
     *
     * @author  huangql <hql@btctrade.com>
     * @param array $values
     * @throws \Exception
     *
     * @return  int
     */
    protected function insert(array $values)
    {
        $values = $this->filterData($values);
        $insert = $this->getInsert();
        $insert->values($values);
        //echo $sqlStr =  $this->getTableGw()->getSql()->buildSqlString($insert);
        $affectedRows = $this->getTableGw()->insertWith($insert);
        $insertId = 0;
        if ($affectedRows) {
            $insertId = $this->getTableGw()->getLastInsertValue();
        }
        return $insertId;
    }

    /**
     * Method  update
     * @desc  更新数据
     *
     * @author  huangql <hql@btctrade.com>
     * @param array $set
     * @param array $where
     * @throws \Exception
     *
     * @return  int
     */
    protected function update(array $set, array $where = [])
    {
        $id = 0;
        $set = $this->filterData($set);
        if (isset($set[$this->pkColumn])) {
            $id = (int)$set[$this->pkColumn];
            unset($set[$this->pkColumn]);
        }
        $update = $this->getUpdate();
        $update->set($set);
        if ($id > 0) {
            $update->where($this->pkColumn . ' = ' . $id);
        } else {
            $update->where($where);
        }
        //echo $sqlStr =  $this->getTableGw()->getSql()->buildSqlString($update);
        $affectedRows = $this->getTableGw()->updateWith($update);
        return $affectedRows;
    }

    /**
     * Method  delete
     * @desc   删除数据
     *
     * @author  huangql <hql@btctrade.com>
     * @param array $conditions
     * @throws \Exception
     *
     * @return  int
     */
    protected function delete(array $conditions)
    {
        $id = 0;
        $conditions = $this->filterData($conditions);
        if (isset($conditions[$this->pkColumn])) {
            $id = (int)$conditions[$this->pkColumn];
            unset($conditions[$this->pkColumn]);
        }
        $delete = $this->getDelete();
        if ($id > 0) {
            $delete->where($this->pkColumn . ' = ' . $id);
        } else {
            $delete->where($conditions);
        }
        //echo $sqlStr =  $this->getTableGw()->getSql()->buildSqlString($delete);
        $affectedRows = $this->getTableGw()->deleteWith($delete);
        return $affectedRows;
    }


    /**
     *Method  enableMasterAdapterSelect
     * @desc   开启主库查询
     *
     * @author huangql <huangql@btctrade.com>
     * @throws \Exception
     *
     * @return  boolean
     */
    public function enableMasterAdapterSelect()
    {
        if (self::$isEnableMasterSelect) {
            return true;
        }
        self::$tmpSlaveAdapter = $this->getSlaveAdapter();
        $this->adapterSlave  = $this->getMasterAdapter();
        self::$isEnableMasterSelect = true;
        return true;
    }


    /**
     *Method  disableMasterAdapterSelect
     * @desc   关闭主库查询
     *
     * @author huangql <huangql@btctrade.com>
     * @throws \Exception
     * @return  boolean
     */
    public function disableMasterAdapterSelect()
    {
        $this->adapterSlave  = self::$tmpSlaveAdapter;
        $this->setTableGw();
        self::$isEnableMasterSelect = false;
        return true;
    }

    /**
     * Method  select
     * @desc  返回select对象
     *
     * @author  huangql <hql@btctrade.com>
     * @param array $conditions
     * @param array $column
     * @throws \Exception
     *
     * @return  Select
     */
    protected function select(array $conditions = [], array $column = ['*'])
    {
        $select = $this->getSelect();
        if ($conditions) {
            $conditions = $this->filterData($conditions);
            $select->where($conditions);
        }
        $select->columns($column, false);
        return $select;
    }

    /**
     * Method  fetchRow
     * @desc  只查询相应条件中的一条记录
     *
     * @author  huangql <hql@btctrade.com>
     * @param Select|null $select
     * @throws \Exception
     *
     * @return  mixed|null
     */
    public function fetchRow($select = null)
    {
        if (!($select instanceof Select)) {
            $select = $this->getSelect();
        }
        $select->limit(1);
        $resultSet = $this->fetchAll($select);
        if ($resultSet) {
            return $this->populateEntity(current($resultSet));
        }
        return null;
    }

    /**
     * Method  fetchOne
     * @desc  只查询数据库中的一行记录中的第一列字段的值
     *
     * @author  huangql <hql@btctrade.com>
     * @param Select|null $select
     * @throws \Exception
     *
     * @return  mixed
     */
    public function fetchOne($select = null)
    {
        if (!($select instanceof Select)) {
            $select = $this->getSelect();
        }
        $select->limit(1);
        $resultSet = $this->fetchAll($select);
        $row = current($resultSet);
        $row = $row ? $row : [];
        return current($row);
    }

    /**
     * Method  fetchPairs
     * @desc  取回一个相关数组,第一个字段的值为key,第二个字段的值为value
     *
     * @author  huangql <hql@btctrade.com>
     * @param Select $select
     * @param $keyColumn
     * @param $valueColumn
     * @throws \Exception
     *
     * @return  array
     */
    public function fetchPairs($select = null, $keyColumn = null, $valueColumn = null)
    {
        $data = [];
        if (!($select instanceof Select)) {
            $select = $this->getSelect();
        }
        if ($keyColumn && $valueColumn) {
            $select->columns([$keyColumn, $valueColumn]);
        }
        $result = $this->fetchAll($select);
        foreach ($result as $item) {
            $keys = array_keys($item);
            $data[$item[$keys[0]]] = $item[$keys[1]];
        }
        return $data;
    }


    /**
     * Method  fetchCol
     * @desc  取回所有结果行的第一个字段值
     *
     * @author  huangql <hql@btctrade.com>
     * @param Select $select
     * @throws \Exception
     *
     * @return  array|mixed
     */
    public function fetchCol($select = null)
    {
        if (!($select instanceof Select)) {
            $select = $this->getSelect();
        }
        $data = [];
        $result = $this->fetchAll($select);
        foreach ($result as $item) {
            $data[] = current($item);
        }
        return $data;
    }

    /**
     * Method  fetchAssoc
     * @desc  取回结果集中所有字段的值,作为关联数组返回, 第一个字段的值作为key
     *
     * @author  huangql <hql@btctrade.com>
     * @param null $select
     * @throws \Exception
     *
     * @return  array
     */
    public function fetchAssoc($select = null)
    {
        if (!($select instanceof Select)) {
            $select = $this->getSelect();
        }
        //echo $sqlStr =  $this->getTableGw()->getSql()->buildSqlString($select);
        $result = $this->fetchAll($select);
        if (empty($result)) {
            return [];
        }
        $data = [];
        foreach ($result as $item) {
            $keys = array_keys($item);
            $data[$item[$keys[0]]] = $item;
        }
        return $data;
    }

    /**
     * Method  fetchAll
     * @desc  获取相关查询条件的数据库记录
     *
     * @author  huangql <hql@btctrade.com>
     * @param Select|null $select
     * @throws \Exception
     *
     * @return  array
     */
    public function fetchAll($select = null)
    {
        if (!($select instanceof Select)) {
            $select = $this->getSelect();
        }
        //echo $sqlStr =  $this->getTableGw()->getSql()->buildSqlString($select);
        $resultSet = $this->getTableGw()->selectWith($select);
        $count = $resultSet->count();
        if ($count <= 0) {
            return [];
        }
        return $resultSet->toArray();
    }

    /**
     * Method  fetchForUpdate
     * @desc  使用for update方式进行查询
     *
     * @author lihao <lihao@btctrade.com>
     * @param Select $select
     * @throws \Exception
     *
     * @return  array
     */
    public function fetchForUpdate($select = null)
    {
        if (!($select instanceof Select)) {
            $select = $this->getSelect();
        }
        $selectString = $this->getTableGw()->getSql()->buildSqlString($select) . ' FOR UPDATE';
        $resultSet = $this->getMasterAdapter()->query($selectString, Adapter::QUERY_MODE_EXECUTE);
        $count = $resultSet->count();
        if ($count <= 0) {
            return [];
        }
        return $resultSet->toArray();
    }

    /**
     * Method  fetchCount
     * @desc  获取相关的查询条件的数据库总记录数
     *
     * @author  huangql <hql@btctrade.com>
     * @param null $select
     * @throws \Exception
     *
     * @return  int
     */
    public function fetchCount($select = null)
    {
        if (!($select instanceof Select)) {
            $select = $this->getSelect();
        }
        $select->columns(['num' => new \Zend\Db\Sql\Expression('COUNT(*)')]);
        $num = (int)$this->fetchOne($select);
        return $num;
    }

    /**
     * Method fetchByPaginator
     * @desc  获取分页结果
     *
     * @author  huangql <hql@btctrade.com>
     * @param array $conditions
     * @param int $pageNum
     * @param int $pageSize
     * @param array $columns
     * @param string $order
     * @throws \Exception
     *
     * @return  array
     */
    public function fetchByPaginator(
        $conditions = [],
        $pageNum = 1,
        $pageSize = 10,
        array $columns = ['*'],
        $order = ''
    ) {
        if ($conditions instanceof Select) {
            $select = $conditions;
        } elseif (is_array($conditions)) {
            $conditions = $this->filterData($conditions);
            $select = $this->getSelect();
            $select->where($conditions);
        }
        //获取总量
        $totalCount = $this->fetchCount($select);
        $pagination = [
            'totalCount' => 0,
            'totalPages' => 0,
            'currentPage' => $pageNum,
            'data' => []
        ];
        if (empty($totalCount)) {
            return $pagination;
        }
        $offset = ((int)$pageNum - 1) * (int)$pageSize;
        if ($offset < 0) {
            $offset = 0;
        }
        $totalPages = (int)ceil($totalCount / $pageSize);
        $select->limit($pageSize)->offset($offset);
        if ($columns) {
            $select->columns($columns, false);
        }
        if (!empty($order)) {
            $select->order($order);
        }
        $data = $this->fetchAll($select);
        if ($data) {
            $pagination['totalCount'] = $totalCount;
            $pagination['totalPages'] = $totalPages;
            $pagination['data'] = $data;
        }
        return $pagination;
    }


    /**
     * Method  getTransactionStatus
     * @desc  ......
     *
     * @author  huangql <hql@btctrade.com>
     *
     * @return  bool
     */
    public function getTransactionStatus()
    {
        return self::$isBeginTransaction;
    }

    /**
     * Method  beginTransaction
     * @desc  ......
     *
     * @author  huangql <hql@btctrade.com>
     * @throws \Exception
     *
     * @return  bool|\Zend\Db\Adapter\Driver\ConnectionInterface
     */
    public function beginTransaction()
    {
        if (true === self::$isBeginTransaction) {
            return true;
        }
        self::$isBeginTransaction = true;
        $this->enableMasterAdapterSelect();
        return $this->getMasterAdapter()->getDriver()->getConnection()->beginTransaction();
    }

    /**
     * Method  commit
     * @desc  ......
     *
     * @author  huangql <hql@btctrade.com>
     * @throws \Exception
     *
     * @return  bool|\Zend\Db\Adapter\Driver\ConnectionInterface
     */
    public function commit()
    {
        if (true !== self::$isBeginTransaction) {
            return false;
        }
        self::$isBeginTransaction = false;
        $this->disableMasterAdapterSelect();
        return $this->getMasterAdapter()->getDriver()->getConnection()->commit();
    }

    /**
     * Method  rollBack
     * @desc  ......
     *
     * @author  huangql <hql@btctrade.com>
     * @throws \Exception
     *
     * @return  bool|\Zend\Db\Adapter\Driver\ConnectionInterface
     */
    public function rollBack()
    {
        if (true !== self::$isBeginTransaction) {
            return false;
        }
        self::$isBeginTransaction = false;
        $this->disableMasterAdapterSelect();
        return $this->getMasterAdapter()->getDriver()->getConnection()->rollback();
    }

    /**
     * Method  filterData
     * @desc  过滤数据
     *
     * @author  huangql <hql@btctrade.com>
     * @param array $rawData
     *
     * @return  array
     */
    protected function filterData(array $rawData)
    {
        $data = [];
        foreach ($rawData as $column => $value) {
            if (is_null($value)) {
                continue;
            }
            $data[$column] = $value;
        }
        return $data;
    }

    /**
     * Method  populateEntity
     * @desc  将数据库查询的数据转换成实体对象属性
     *
     * @author  huangql <hql@btctrade.com>
     * @param array $row
     * @throws \ReflectionException
     *
     * @return  mixed
     */
    public function populateEntity(array $row)
    {
        if (self::$isEnabledYafNameSpace == 0) {
            $entityClass = "Entity_" . str_replace('Model', 'EntityModel', $this->calledClassName);
        } else {
            $entityClass = "Entity\\" . str_replace('Model', 'EntityModel', $this->calledClassName);
        }
        $reflectionClass = new \ReflectionClass($entityClass);
        $object = new $entityClass();
        foreach ($reflectionClass->getProperties(\ReflectionProperty::IS_PROTECTED) as $property) {
            $propertyName = $property->getName();
            $dbColumnName = Tool::camel2Underline($propertyName);
            $reflectionProperty = $reflectionClass->getProperty($propertyName);
            $reflectionProperty->setAccessible(true);
            if (isset($row[$dbColumnName])) {
                $reflectionProperty->setValue($object, $row[$dbColumnName]);
            }
        }
        return $object;
    }
}

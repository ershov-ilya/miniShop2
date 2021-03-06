<?php

class msProductLinkGetListProcessor extends modObjectGetListProcessor {
	public $classKey = 'msProductLink';
	public $defaultSortField = 'link';
	public $defaultSortDirection  = 'ASC';

	/**
	 * {@inheritDoc}
	 * @return boolean
	 */
	public function initialize() {
		$this->setDefaultProperties(array(
			'start' => 0,
			'limit' => 20,
			'sort' => $this->defaultSortField,
			'dir' => $this->defaultSortDirection,
			'combo' => false,
			'query' => '',
		));
		return true;
	}

	/**
	 * {@inheritDoc}
	 * @return mixed
	 */
	public function process() {
		$beforeQuery = $this->beforeQuery();
		if ($beforeQuery !== true) {
			return $this->failure($beforeQuery);
		}
		$data = $this->getData();
		$list = $this->iterate($data);
		return $this->outputArray($list,$data['total']);
	}


	/**
	 * Get the data of the query
	 * @return array
	 */
	public function getData() {
		$data = array();
		$limit = intval($this->getProperty('limit'));
		$start = intval($this->getProperty('start'));

		/* query for chunks */
		$c = $this->modx->newQuery($this->classKey);
		$c = $this->prepareQueryBeforeCount($c);

		$data['total'] = $this->getCount($this->classKey,$c);

		$c = $this->prepareQueryAfterCount($c);

		$sortClassKey = $this->getSortClassKey();
		$sortKey = $this->modx->getSelectColumns($sortClassKey,$this->getProperty('sortAlias',$sortClassKey),'',array($this->getProperty('sort')));
		if (empty($sortKey)) $sortKey = $this->getProperty('sort');
		$c->sortby($sortKey,$this->getProperty('dir'));
		if ($limit > 0) {
			$c->limit($limit,$start);
		}

		if ($c->prepare() && $c->stmt->execute()) {
			$data['results'] = $c->stmt->fetchAll(PDO::FETCH_ASSOC);
		}

		return $data;
	}


	public function getCount($className, $criteria= null) {
		$count= 0;
		$q = $this->modx->newQuery($className, $criteria);
		if (isset($q->query['columns'])) {$q->query['columns'] = array();}
		$q->select(array("COUNT(*) as count"));
		if ($q->prepare() && $q->stmt->execute()) {
			$count = $q->stmt->fetch(PDO::FETCH_COLUMN);
		}
		return $count;
	}

	/**
	 * Iterate across the data
	 *
	 * @param array $data
	 * @return array
	 */
	public function iterate(array $data) {
		$list = array();
		$list = $this->beforeIteration($list);
		$this->currentIndex = 0;
		/** @var xPDOObject|modAccessibleObject $object */
		foreach ($data['results'] as $array) {
			$objectArray = $this->prepareResult($array);
			if (!empty($objectArray) && is_array($objectArray)) {
				$list[] = $objectArray;
				$this->currentIndex++;
			}
		}
		$list = $this->afterIteration($list);
		return $list;
	}

	/**
	 * {@inheritDoc}
	 * @return xPDOQuery
	 */
	public function prepareQueryBeforeCount(xPDOQuery $c) {
		if ($master = $this->getProperty('master')) {
			$c->orCondition(array('master' => $master, 'slave' => $master));
		}
		$c->innerJoin('msLink', 'msLink', 'msProductLink.link=msLink.id');
		$c->leftJoin('msProduct', 'Master', 'Master.id=msProductLink.master');
		$c->leftJoin('msProduct', 'Slave', 'Slave.id=msProductLink.slave');
		$c->select($this->modx->getSelectColumns('msProductLink', 'msProductLink'));
		$c->select($this->modx->getSelectColumns('msLink', 'msLink', '', array('id'), true));
		$c->select('`Master`.`pagetitle` as `master_pagetitle`, `Slave`.`pagetitle` as `slave_pagetitle`');

		return $c;
	}

	/**
	 * {@inheritDoc}
	 * @return array
	 */
	public function prepareResult(array $array) {
		return $array;
	}
}

return 'msProductLinkGetListProcessor';
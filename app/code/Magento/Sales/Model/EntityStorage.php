<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Sales\Model;

use Magento\Framework\Model\AbstractModel as FrameworkAbstractModel;

/**
 * Class EntityStorage store only one type of entity per instance
 */
class EntityStorage
{
    /**
     * @var array
     */
    protected $registry = [];

    /**
     * Using for mapping hashes of identifying fields to entity ids
     *
     * @var array
     */
    protected $storageMapper = [];

    /**
     * Using for array concatenation
     */
    const GLUE = '';

    /**
     * Adds entity using identifying fields mapping, entity should have an id
     *
     * @param FrameworkAbstractModel $object
     * @param array $identifyingFields
     * @param $storageName
     * @throws \Magento\Framework\Exception\InputException
     */
    public function addByIdentifyingFields(FrameworkAbstractModel $object, array $identifyingFields, $storageName)
    {
        if (empty($identifyingFields)) {
            throw new \Magento\Framework\Exception\InputException(__('Identifying Fields required'));
        }
        if (!$object->getId()) {
            throw new \Magento\Framework\Exception\InputException(__('Id required'));
        }
        $this->storageMapper[$storageName][$this->getHash($identifyingFields)] = $object->getId();
        $this->registry[$object->getId()] = $object;
    }

    /**
     * Retrieve entity from registry if entity in it
     *
     * @param $id
     * @return bool|FrameworkAbstractModel
     */
    public function get($id)
    {
        if ($this->has($id)) {
            return $this->registry[$id];
        }
        return false;
    }

    /**
     * Gets entity by identifying fields
     *
     * @param array $identifyingFields
     * @param $storageName
     * @return bool|FrameworkAbstractModel
     */
    public function getByIdentifyingFields(array $identifyingFields, $storageName)
    {
        $hash = $this->getHash($identifyingFields);
        if (isset($this->storageMapper[$storageName][$hash])) {
            return $this->get($this->storageMapper[$storageName][$hash]);
        }
        return false;
    }

    /**
     * Remove entity from storage
     *
     * @param $id
     */
    public function remove($id)
    {
        if ($this->has($id)) {
            unset($this->registry[$id]);
        }
    }

    /**
     * Checks if entity is in storage
     *
     * @param $id
     * @return bool
     */
    public function has($id)
    {
        return isset($this->registry[$id]);
    }

    /**
     * Gets hash using concatenation of identifying fields
     *
     * @param array $fields
     * @return string
     */
    protected function getHash(array $fields)
    {
        $stringForKey = implode(self::GLUE, $fields);
        return sha1($stringForKey);
    }
}

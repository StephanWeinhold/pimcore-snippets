<?php
class ObjectNavigation
{

    /**
     * @param $parentFolderId
     *
     * @return array|bool
     * @throws \Zend_Db_Exception
     */
    public function getObjectNavigation($parentFolderId)
    {
        if (!empty($parentFolderId) && is_int($parentFolderId)) {
            $folderObject = Object::getById($parentFolderId);

            if (is_object($folderObject) && $folderObject->getType() === 'folder') {
                $dbData = array(
                    'host'      => 'YOUR_HOST',
                    'username'  => 'YOUR_USERNAME',
                    'password'  => 'YOUR_PASSWORD',
                    'dbname'    => 'pimcore'
                );
                $db = Zend_Db::factory('Pdo_Mysql', $dbData);
                $folderName = $db->quote('%' . $folderObject->getO_key() . '%');
                $query = "SELECT o_id, CONCAT(o_path, o_key) AS path FROM object_1 WHERE o_path LIKE " . $folderName;
                $result = $db->fetchAll($query);

                if (is_array($result) && !empty($result)) {
                    $objectsNavigationArray = self::objectPathToAssociativeArray($result);
                    if ($objectsNavigationArray !== false) {
                        return $objectsNavigationArray;
                    }
                }
            }
        }
        return false;
    }

    /**
     * @param $objectsArray
     *
     * @return array|bool
     */
    private static function objectPathToAssociativeArray($objectsArray)
    {
        if (is_array($objectsArray)) {
            $objectsNavigationArray = array();

            foreach ($objectsArray as $object) {
                $path = array_filter(explode('/', $object['path']));
                self::addElementToFolder($objectsNavigationArray, $path, $object['o_id']);
            }

            return $objectsNavigationArray;
        }
        return false;
    }

    /**
     * @param $toFill
     * @param $path
     * @param $filename
     */
    private static function addElementToFolder(&$toFill, $path, $filename) {
        $folder = array_shift($path);
        if (empty($path)) {
            $toFill[$folder][] = $filename;
        } else {
            self::addElementToFolder($toFill[$folder], $path, $filename);
        }
    }
}
?>

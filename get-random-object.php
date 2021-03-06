<?php
/**
 * @param $folderId
 *
 * @return $randomObject | false
 */
public static function getRandomObject($folderId)
{
    if (!empty($folderId) && is_int($folderId)) {
        $folderObject = Object::getById($folderId);
        
        if (is_object($folderObject) && $folderObject->getType() == 'folder') {
            $folderName = $folderObject->getO_key();

            $objectList = new Object\Listing();
            $objectList->setCondition("o_path LIKE " . $objectList->quote("%" . $folderName . "%"));

            if (is_object($objectList)) {
                $allObjectIds = array();
                
                foreach ($objectList as $object) {
                    $allObjectIds[] = $object->getId();
                }

                $randomObject = Object::getById($allObjectIds[array_rand($allObjectIds)]);

                if (is_object($randomObject)) {
                    return $randomObject;
                }
            }
        }
    }
    return false;
}
?>

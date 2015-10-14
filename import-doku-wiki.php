<?php
use Website\Controller\Action;

class ImportWikiController extends Action
{

    public function importDokuwikiAction()
    {
        /**
         * https://www.dokuwiki.org/syndication
         */
        $username = 'YOUR USERNAME';
        $password = 'YOUR PASSWORD';
        $url = 'http://your-dokuwiki/feed.php?type=rss2&num=99999999999&none=0&linkto=current&content=html&mode=list&u=' . $username . '&p=' . $password;

        if ($this->getParam('namespace') && $this->getParam('namespace') != '') {
            $url .= '&ns=' . $this->getParam('namespace');
        }

        $this->getDataFromDokuWiki($url);

        $this->_helper->json(array('success' => true, 'msg' => 'Success'));
    }

    /**
     * @param $url
     *
     * @throws Exception
     *
     * Grabs data from a DokuWiki
     */
    protected function getDataFromDokuWiki($url)
    {
        if ($url && $url != '') {
            $xml = file_get_contents($url);
            $channel = new SimpleXMLElement($xml);
            $items = $channel->channel->item;

            foreach ($items as $item) {
                $this->saveEntry($item);
            }
        }
        else {
            throw new Exception('No URL given.', 404);
        }
    }

    /**
     * @param $entry
     *
     * @throws Exception
     *
     * Generates a pimcore-object out of an entry
     */
    protected function saveEntry($entry)
    {
        if (is_object($entry)) {
            $objectKey = substr($entry->title, strrpos($entry->title, ':') + 1);
            $folderPath = 'YOUR/PATH/' . str_replace(':', '/', substr($entry->title, 0, strrpos($entry->title, ':')));

            $description = $this->parseDescription($entry->description[0]);

            if ($entry->pubDate) {
                $date = strtotime((string)$entry->pubDate);
            }
            else {
                $date = time();
            }

            $folderObject = $this->checkFolderPath($folderPath);

            $object = Object::getByPath('/' . $folderPath . '/' . $objectKey);

            if (!$object) {
                $object = Object_YOURCLASS::create();
            }

            $object->setTitle((string)$description['title']);
            $object->setText((string)$description['text']);
            $object->setDate($date);
            $object->setKey($objectKey);
            $object->setParentId($folderObject->getId());
            $object->setPublished(true);

            try {
                $object->save();
            }
            catch (Exception $e) {
                throw $e;
            }
        }
        else {
            throw new Exception('No or wrong XML-object given.', 500);
        }
    }

    /**
     * @param $folderPath
     *
     * @return mixed
     * @throws Exception
     *
     * Runs each level of the folder-path and checks if it is already existing in pimcore
     */
    protected function checkFolderPath($folderPath)
    {
        $folderObject = Object_Folder::getByPath($folderPath);

        if (!is_object($folderObject)) {
            $folderPathArray = explode('/', $folderPath);
            $growingPath = '';

            foreach ($folderPathArray as $folderName) {
                $folderObject = Object_Folder::getByPath($growingPath . '/' . $folderName);

                if (!is_object($folderObject) && !method_exists($folderObject, 'getId')) {
                    try {
                        $parentFolder = Object_Folder::getByPath($growingPath);

                        if (is_object($parentFolder)) {
                            $parentId = $parentFolder->getId();
                            $parentFolder = Object_Folder::create(array('type' => 'folder', 'key' => $folderName, 'parentId' => $parentId));
                        }
                    }
                    catch (Exception $e) {
                        throw $e;
                    }
                }

                $growingPath .= '/' . $folderName;
            }
        }

        if (is_object($folderObject)) {
            return $folderObject;
        }
        else {
            return $parentFolder;
        }
    }

    /**
     * @param $description
     *
     * @return array
     *
     * Cuts the title out of the description and returns both
     */
    protected function parseDescription($description)
    {
        $return = array();

        $description = substr($description, strpos($description, '>') + 1);

        $return['title'] = substr($description, 0, strpos($description, '</h1>'));
        $return['text'] = substr($description, strpos($description, '</h1>') + 5);

        return $return;
    }

}

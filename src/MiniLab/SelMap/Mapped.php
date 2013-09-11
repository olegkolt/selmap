<?php

namespace MiniLab\SelMap;

use MiniLab\SelMap\Reader\XmlReader;
use MiniLab\SelMap\Path\Path;

/**
 * Description of SelMapEntity
 *
 * @author oleg
 */
trait Mapped
{
    protected $db;
    private $fileExt = ".sm.xml";
    private $maps = array();
    private $filePath;
    /**
     * @return \MiniLab\SelMap\DataBase
     */
    abstract protected function getDataBase();
    /**
     * 
     * @param string $classFilePath Must be __FILE__
     * @throws \Exception
     */
    protected function init($classFilePath)
    {
        $this->db = $this->getDataBase();
        
        $info = pathinfo($classFilePath);
        $dir = $info["dirname"] . "/";
        $name = $info["filename"];
        $this->filePath = $dir . $name . $this->fileExt;
        if (!file_exists($this->filePath)) {
            throw new \Exception("Can not find config file");
        }
        
        $xMap = new \DOMDocument();
        $xMap->load($this->filePath);
        
        $reader = new XmlReader($this->db);
        
        foreach ($xMap->documentElement->getElementsByTagName("Map") as $xMap) {
            if ($xMap instanceof \DOMElement) {
                $name = $xMap->getAttribute("Name");
                $this->maps[$name] = array();
                $this->maps[$name]["map"] = $reader->readMap($xMap);
                $paths = array();
                foreach ($xMap->getElementsByTagName("Path") as $xPath) {
                    $pName = $xPath->getAttribute("Name");
                    $paths[$pName] = new Path($xPath->nodeValue);
                }
                $this->maps[$name]["paths"] = $paths;
            }
        }
    }
    /**
     * 
     * @param string $mapName
     * @return MiniLab\SelMap\Query\QueryMap
     */
    protected function getMap($mapName)
    {
        if (is_null($this->db)) {
            throw new \Exception("Resources has not been loaded");
        }
        if (!isset($this->maps[$mapName])) {
            throw new \Exception("Map not find: " . $this->filePath . " " . $mapName);
        }
        return $this->maps[$mapName]["map"];
    }
    /**
     * 
     * @param string $mapName
     * @param string $pathName
     * @return MiniLab\SelMap\Path\Path
     */
    protected function getPath($mapName, $pathName)
    {
        if (is_null($this->db)) {
            throw new \Exception("Resources has not been loaded");
        }
        if (!isset($this->maps[$mapName])) {
            throw new \Exception("Map not find: " . $this->filePath . " " . $mapName);
        }
        if (!isset($this->maps[$mapName]["paths"][$pathName])) {
            throw new \Exception("Path not find: " . $this->filePath . " " . $mapName . " " . $pathName);
        }
        return $this->maps[$mapName]["paths"][$pathName];
    }
}

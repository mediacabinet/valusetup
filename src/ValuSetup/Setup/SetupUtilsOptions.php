<?php
namespace ValuSetup\Setup\SetupUtils;

use Zend\Stdlib\AbstractOptions;

class SetupUtilsOptions 
    extends AbstractOptions
{
    
    protected $definitionFile;
    
    protected $moduleDirs = array();
    
    protected $repositories = array();
    
    protected $extractPhars = false;
    
    public function setExtractPhars($enable){
        $this->extractPhars = (boolean) $enable;
    }
    
    public function getExtractPhars(){
        return $this->extractPhars;
    }
    
    public function setDefinitionFile($file){
        $this->definitionFile = $file;
    }
    
    public function getDefinitionFile(){
        return $this->definitionFile;
    }
    
	/**
	 * @return the $repositories
	 */
	public function getRepositories() {
		return $this->repositories;
	}

	/**
	 * @param array $repositories
	 */
	public function setRepositories($repositories) {
	    
	    if(!is_array($repositories) && !($repositories instanceof \Traversable)){
	    	throw new \InvalidArgumentException('Invalid argument $repositories; array or instance of Traversable expected');
	    }
	    
	    $this->repositories = array();
	    
	    foreach ($repositories as $name => $specs){
	        $this->repositories[$name] = $specs;
	    }
	}

	/**
	 * @return the $moduleDirs
	 */
	public function getModuleDirs() {
		return $this->moduleDirs;
	}

	/**
	 * @param array $moduleDirs
	 */
	public function setModuleDirs($dirs) {
	    
	    if(!is_array($dirs) && !($dirs instanceof \Traversable)){
	        throw new \InvalidArgumentException('Invalid argument $dirs; array or instance of Traversable expected');
	    }
	    
	    $this->moduleDirs = array();
	    
		foreach($dirs as $dir){
		    if(!is_dir($dir) || !is_readable($dir)){
		        throw new \Exception('Illegal module directory specified: '.$dir);
		    }
		    
		    $this->moduleDirs[] = $dir;
		}
	}
}
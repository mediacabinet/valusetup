<?php
namespace ValuSetup\Setup;

use Zend\Stdlib\AbstractOptions;

class SetupUtilsOptions 
    extends AbstractOptions
{
    
    /**
     * Definition file name
     * 
     * @var string
     */
    protected $definitionFile;
    
    /**
     * Module directories
     * 
     * @var array
     */
    protected $moduleDirs = array();
    
    /**
     * Module repositories
     * 
     * @var array
     */
    protected $repositories = array();
    
    /**
     * Should PHAR files be extracted?
     * 
     * @var boolean
     */
    protected $extractPhars = false;
    
    /**
     * Enable/disable PHAR extraction
     * 
     * @param boolean $extract
     */
    public function setExtractPhars($extract = true){
        $this->extractPhars = (boolean) $extract;
    }
    
    /**
     * Should PHAR files be extracted?
     * 
     * @return boolean
     */
    public function getExtractPhars(){
        return $this->extractPhars;
    }
    
    /**
     * Set definition file name
     * 
     * @param string $file
     */
    public function setDefinitionFile($file){
        $this->definitionFile = $file;
    }
    
    /**
     * Get definition file name
     * 
     * @return string
     */
    public function getDefinitionFile(){
        return $this->definitionFile;
    }
    
	/**
	 * Get repositories
	 * 
	 * @return array
	 */
	public function getRepositories() {
		return $this->repositories;
	}

	/**
	 * Set repositories
	 * 
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
	 * Get module directories
	 * 
	 * @return array
	 */
	public function getModuleDirs() {
		return $this->moduleDirs;
	}

	/**
	 * Set module directories
	 * 
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
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
<?php
namespace ValuSetup\Setup;

use ValuSo\Broker\ServiceBroker;
use ValuSetup\Setup\Exception;
use DirectoryIterator;
use FilesystemIterator;
use Zend\Stdlib\PriorityQueue;

/**
 * Setup utilities
 * 
 * @author Juha Suni
 *
 */
class SetupUtils{
    
    /**
     * Service broker instance
     *
     * @var \ValuSo\Broker\ServiceBroker
     */
    protected $serviceBroker = null;
    
    /**
     * Options
     * 
     * @var SetupUtilsOptions
     */
    protected $options;
    
    /**
     * Module definitions
     * 
     * @var array
     */
    protected $definitions = array();
    
    /**
     * Module dependencies
     * 
     * @var array
     */
    protected $deps = array();
    
    public function __construct(ServiceBroker $serviceBroker, $config = null)
    {
        $this->setServiceBroker($serviceBroker);
        
        if($config !== null){
            $this->setConfig($config);
        }
    }
    
    /**
     * Install module dependencies
     *
     * @return void
     */
    public function install($module, $version, $options = null)
    {
        $module = strtolower($module);
        $options = is_null($options) ? array() : $options;
        
        $modules = $this->findModules();
        $current = array();
    
        foreach($modules as $name){
            $modVersion = $this->getModuleVersion($name);
            
            if ($modVersion) {
                $current[$name] = new SoftwareVersion(
                    $this->getModuleVersion($name));
            }
        }
        
        // Resolve deps
        $deps = $this->resolveDependencies(
            $module
        );
        
        if (!$deps->offsetExists($module)) {
            $deps->offsetSet($module, $version);
        }
        
        $queue = new PriorityQueue();
        
        // Setup all dependencies
        foreach($deps as $depModule => $depVersion){
    
            $depVersion = new SoftwareVersion($depVersion);
    
            if(!$this->hasSetupService($depModule)){
                continue;
            }
    
            $setup = $this->initSetupService($depModule);
            $queue->insert(['service' => $setup, 'module' => $depModule], $setup->getPriority());
        }
        
        foreach ($queue->getIterator() as $datum) {
            $service    = $datum['service'];
            $depModule  = $datum['module'];
            $operation  = 'setup';
            $args       = array();
        
            if ($depModule == $module) {
                $args['options'] = $options;
            }
             
            // complete existing installation
            $setup->exec(
                $operation,
                $args
            );
        }
        
        return true;
    }
    
    /**
     * Update module (execute setup for each dependent module)
     * 
     * @param string $module
     * @param array|null $options
     * @return boolean
     */
    public function update($module, $options = null)
    {
        $module = strtolower($module);
        $options = is_null($options) ? array() : $options;
        $modules = $this->findModules();
       
        // Resolve deps
        $deps = $this->resolveDependencies(
            $module
        );
        
        if (!$deps->offsetExists($module)) {
            $deps->offsetSet($module, 'X');
        }
        
        // Setup all dependencies
        foreach($deps as $depModule => $depVersion){

            if(!$this->hasSetupService($depModule)){
                continue;
            }
        
            $setup = $this->initSetupService($depModule);
            $operation = 'setup';
            $args = array();
        
            if ($depModule == $module) {
                $args['options'] = $options;
            }
             
            // complete existing installation
            $setup->exec(
                'setup',
                $args
            );
        }
        
        return true;
    }
    
    /**
     * Upgrade module
     *
     * @param string $module
     * @param string $version
     * @param array $options
     * @throws \Exception
     */
    public function upgrade($module, $version, array $options = null){
    
        /**
         * Fetch previous version for module
         */
        $oldVersion = $this->getModuleVersion($module);
        $oldVersion = new SoftwareVersion($oldVersion);
    
        if($oldVersion->isLt($version)){
            throw new \Exception('Unable to upgrade to '.$version.' (version '.$oldVersion.' is already installed)');
        }
    
        return $this->install($module, $version, $options);
    }
    
    /**
     * Uninstall module
     * 
     * @param string $module	Module name
     * @return boolean 			True when module was removed, false if
     * 							nothing was removed
     */
    public function uninstall($module){
        if($this->moduleExists($module)){
            return $this->removeModuleFiles($module);
        }
    }
    
    /**
     * Resolve module dependencies, recursively
     * 
     * This method returns an array with dependent module names
     * as keys and versions as values. There may also be a 
     * dependency back to module itself with greater version
     * number.
     * 
     * Example of dependency tree:
     * - A 1.0
     *   - C 1.0
     *     - A 1.1
     *   - D 2.0
     *     - E 1.0
     *     - B 1.0
     *   - B 1.1
     *   
     * Resolved dependencies:
     * A : 1.1
     * C : 1.0
     * E : 1.0
     * B : 1.1
     * D : 2.0
     * 
     * @param string $module
     * @throws \Exception
     * @return ArrayObject
     */
    public function resolveDependencies($module){
        
        $version = $this->getModuleVersion($module);
        
        if(null === $version){
            throw new \Exception(sprintf(
                "Unable to resolve dependencies for module '%s'. Module definitions are missing or incomplete.", 
                $module
            ));
        }
        
        $resolved = new \ArrayObject();
        $this->resolveDepsRecursive($module, $version, $resolved);
        
        // Remove dependency to module itself if resolved version 
        // is less than or equal to current version.
        if($resolved->offsetExists($module) && SoftwareVersion::compare($resolved[$module], $version) <= 0){
            unset($resolved[$module]);
        }

        return $resolved;
    }
    
    /**
     * Resolve dependencies recursively. The level and order in module dependency
     * hierarchy defines the module's priority.
     * 
     * @param string $module
     * @param string $version
     * @param \ArrayObject $resolved
     */
    protected function resolveDepsRecursive($module, $version, \ArrayObject $resolved){
    
        $module = strtolower($module);
        
        // replace if exists with lower version number
        if($resolved->offsetExists($module)){
            $new = new SoftwareVersion($version);
        
            // remember the greatest versions
            if($new->isGt($resolved[$module])){
                unset($resolved[$module]);
                $resolved[$module] = $version;
            }
        } else{
            $resolved[$module] = $version;
        }
        
        // ask for deps
        $deps = $this->getModuleDeps($module);
        
        if (array_key_exists($module, $deps)) {
            throw new \Exception(
                sprintf('Invalid dependency for module %s: dependency cannot point to self', $module));
        }
    
        foreach ($deps as $name => $depVersion){
    
            // make sure the same module doesn't get resolved again
            if(!$resolved->offsetExists($name)){
                $this->resolveDepsRecursive($name, $depVersion, $resolved);
            }
        }
        
        if ($resolved[$module] == $version) {
            unset($resolved[$module]);
            $resolved[$module] = $version;
        }
    }
    
    /**
     * Check whether a module directory exists in one of the
     * module locations
     *
     * @param string $module
     * @throws \Exception
     * @return boolean
     */
    public function moduleExists($module)
    {
        return $this->locateModule($module) !== false;
    }
    
    /**
     * Find all modules installed in module directories
     *
     * @return array
     */
    public function findModules()
    {
        $dirs    = $this->getOption('module_dirs');
        $modules = array();
    
        foreach($dirs as $dir){
            $iterator = new DirectoryIterator($dir);
    
            foreach ($iterator as $file) {
                if (($file->isDir() && substr($file->getBasename(), 0, 1) !== '.') 
                    || ($file->isFile() && $file->getExtension() == 'phar')) {
                    
                    $modules[$file->getRealPath()] = strtolower($file->getBasename());
                }
            }
        }
    
        return $modules;
    }
    
    /**
     * Locate module file (phar) or directory
     *
     * @param string $module
     * @return string|boolean
     */
    public function locateModule($module)
    {
        $module = strtolower($module);
        $dirs = $this->getOption('module_dirs');
    
        foreach($dirs as $dir){
             
            $file = $dir . DIRECTORY_SEPARATOR . $module;
             
            if(file_exists($file)){
                return $file;
            }
        }
         
        return false;
    }
    
    /**
     * Detect the name of the module based on class
     *
     * @param object $object Setup instance
     * @return string|null Module name
     */
    public function whichModule($object){
        $reflection = new \ReflectionClass($object);
        $ns = explode('\\', $reflection->getNamespaceName());
        
        if (class_exists($ns[0] . '\Module')) {
            $reflection = new \ReflectionClass($ns[0] . '\Module');
            return strtolower(basename(dirname($reflection->getFileName())));
        } else {
            $path = $reflection->getFileName();
            $dirs = $this->getOption('module_dirs');
             
            foreach($dirs as $dir){
                $dir = realpath($dir);
            
                if(strpos($path, $dir) === 0){
                    $dir = substr($path, strlen($dir));
                    $dir = ltrim($dir, DIRECTORY_SEPARATOR);
            
                    $a = explode(DIRECTORY_SEPARATOR, $dir);
                    return strtolower($a[0]);
                }
            }
        }

        return null;
    }
    
    /**
     * Get current version for module
     *
     * @param string pathule
     * @return string|null
     */
    public function getModuleVersion($module)
    {
        $config = $this->getModuleDefinition($module);
    
        if (isset($config['version'])) {
            return $config['version'];
        } else {
            return null;
        }
    }
    
    /**
     * Get module definition as an array
     *
     * @param string $module
     * @return array
     */
    public function getModuleDefinition($module)
    {
        $module = strtolower($module);
        
        if (!array_key_exists($module, $this->definitions)) {
            $path = $this->locateModule($module);
            $config = array();
    
            if($path){
    
                $definition = $path . DIRECTORY_SEPARATOR . $this->getOption('definition_file');
    
                /**
                 * Read version from definition file (e.g. definition.ini)
                 * from either module directory or PHAR archive
                */
                if(is_dir($path) && file_exists($definition)){
                    $config = \Zend\Config\Factory::fromFile($definition);
                } else if(is_file($path) && file_exists('phar://'.$definition)){
                    $config = \Zend\Config\Factory::fromFile('phar://'.$definition);
                }
            }
    
            $this->definitions[$module] = $config;
        }
    
        return $this->definitions[$module];
    }
    
    /**
     * Retrieve list of module dependencies
     *
     * @param string $module
     * @return array
     */
    public function getModuleDeps($module)
    {
        $module = strtolower($module);
        
        if (!isset($this->deps[$module])) {
            $map     = array();
            $deps    = array();
            $modules = $this->findModules();
    
            foreach ($modules as $name) {
                $config     = $this->getModuleDefinition($name);
                $map[$name] = isset($config['name']) ? $config['name'] : null;
            }
    
            $config = $this->getModuleDefinition($module);
    
            if (isset($config['require-dev'])) {
                foreach ($config['require-dev'] as $composerName => $composerVersion) {
                    $moduleName = array_search($composerName, $map);
    
                    if ($moduleName !== false) {
                        $deps[$moduleName] = ltrim($composerVersion, '<>=');
                    }
                }
            }

            $this->deps[$module] = $deps;
        }
    
        return $this->deps[$module];
    }
    
    /**
     * Remove module files
     * 
     * Removes both PHAR archives and directories with
     * the module name within one of the module directories.
     * 
     * @param string $module
     * @throws Exception\ModuleFolderNotWritableException
     */
    protected function removeModuleFiles($module){
    	$path = $this->locateModule($module);

    	/**
    	 * Test if a directory exists and remove
    	 * recursively
    	 */
    	if($path && is_dir($path)){
    	    
    	    if(!is_writable($path)){
    	        throw new Exception\ModuleFolderNotWritableException(
    	        	'Unable to remove module from path '.$path
    	        );
    	    }
    	    
    	    $iterator = new \RecursiveIteratorIterator(
    	    	new \RecursiveDirectoryIterator($path),
    	    	\RecursiveIteratorIterator::CHILD_FIRST
    	    );
    	    
    	    foreach ($iterator as $path){
    	    	if ($path->isDir()) {
    	    		rmdir($path->__toString());
    	    	}
    	    	else{
    	    		unlink($path->__toString());
    	    	}
    	    }
    	    
    	    rmdir($path);
    	    
    	    return true;
    	}
    	
    	/**
    	 * Test if a phar file exists and remove
    	 */
    	$file = $this->locateModule($module);
    	if(file_exists($file)){
    	    
    	    if(!is_writable($file)){
    	    	throw new Exception\ModuleFolderNotWritableException(
    	    		'Unable to remove module from path '.$path
    	    	);
    	    }
    	        	    
    	    unlink($file);
    	    return true;
    	}
    	
    	return false;
    }
    
    /**
     * Retrieve setup service Worker for module
     * 
     * @param string $module
     * @return \ValuSo\Broker\Worker
     */
    public function initSetupService($module){
        $moduleSetup = $module . '.setup';
        return $this->getServiceBroker()->service($moduleSetup);
    }
    
    /**
     * Does a setup service exist for module?
     * 
     * @param string $module
     * @return boolean
     */
    public function hasSetupService($module){
        $moduleSetup = $module . '.setup';
        return $this->getServiceBroker()->exists($moduleSetup);
    }
    
    /**
     * Retrieve service broker
     *
     * @return \ValuSo\Broker\ServiceBroker
     */
    public function getServiceBroker()
    {
    	return $this->serviceBroker;
    }
    
    /**
     * Set service broker
     *
     * @param \ValuSo\Broker\ServiceBroker $broker
     */
    public function setServiceBroker(ServiceBroker $serviceBroker)
    {
    	$this->serviceBroker = $serviceBroker;
    }
    
    /**
     * Set service options
     *
     * @param  array|Traversable|string $config
     * @return Service
     */
    public function setConfig($config)
    {
        if(is_string($config) && file_exists($config)){
            $options = \Zend\Config\Factory::fromFile($config);
            $this->setOptions($options);
        }
        elseif(!is_array($config) && !($config instanceof \Traversable)){
            throw new \InvalidArgumentException('Invalid parameter $config or config file not found');
        }
        
    	$this->options = new SetupUtilsOptions($config);
    	return $this;
    }
    
    /**
     * Retrieve service options
     *
     * @return array
     */
    public function getOptions()
    {
    	if(!$this->options){
    		$this->options = new SetupUtilsOptions();
    	}
    
    	return $this->options;
    }
    
    /**
     * Is an option present?
     *
     * @param  string $key
     * @return bool
     */
    public function hasOption($key)
    {
    	return $this->getOptions()->__isset($key);
    }
    
    /**
     * Set option
     *
     * @param string $key
     * @param mixed $value
     * @return Service
     */
    public function setOption($key, $value)
    {
    	$this->getOptions()->__set($key, $value);
    	return $this;
    }
    
    /**
     * Retrieve a single option
     *
     * @param  string $key
     * @return mixed
     */
    public function getOption($key)
    {
    	return $this->getOptions()->__get($key);
    }
}

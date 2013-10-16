<?php
/**
 * EAssetManager class file.
 *
 * Extended Asset Manager
 * Compiles .less file(s) on-the-fly and publishes output .css file
 *
 * @author Inpassor <inpassor@gmail.com>
 * @link https://github.com/Inpassor/yii-EAssetManager
 *
 * @version 0.2 (2013.10.15)
 */

/*

INSTALLATION

1. Copy EAssetManager.php to /protected/extensions directory
2. Download the latest version of lessphp from http://leafo.net/lessphp and put lessc.inc.php file under /protected/extentions/EAssetManager/ folder
3. Add or replace the assetManager component in /protected/config/main.php like that:

	'components'=>array(

		...

		'assetManager'=>array(
			'class'=>'EAssetManager',
			'lessCompile'=>true,
			'lessCompiledPath'=>'application.assets.css',
			'lessFormatter'=>'compressed',
			'lessForceCompile'=>false,
		),

		...

	),

See code of EAssetManager.php to read description of public properties.


USAGE

CHtml::asset(Yii::app()->basePath.'/vendors/bootstrap/less/bootstrap.less');

That's all :)

*/


class EAssetManager extends CAssetManager
{

	// path to store compiled css files
	// defaults to 'application.assets.css'
	public $lessCompiledPath=null;

	// compiled output formatter
	// accepted values: 'lessjs' , 'compressed' , 'classic'
	// defaults to 'lessjs'
	// read http://leafo.net/lessphp/docs/#output_formatting for details
	public $lessFormatter='lessjs';

	// passing in true will cause the input to always be recompiled
	public $lessForceCompile=false;

	// if set to false, .less to .css compilation will be done ONLY if output .css file not found
	// otherwise existing .css file will be used
	public $lessCompile=true;


	public function init()
	{
		if ($this->lessCompile)
		{
			require_once(dirname(__FILE__).'/EAssetManager/lessc.inc.php');
		}
		if (!$this->lessCompiledPath)
		{
			$this->lessCompiledPath='application.assets.css';
		}
		$this->lessCompiledPath=$this->_getPath($this->lessCompiledPath);
		parent::init();
	}


	public function publish($path,$hashByName=false,$level=-1,$forceCopy=null)
	{
		if (($src=realpath($path))!==false)
		{
			switch (pathinfo($src,PATHINFO_EXTENSION))
			{
				case 'less':
				{
					$path=$this->lessCompiledPath.'/'.basename($src,'.less').'.css';
					$lessCompile=false;
					if (!$this->lessForceCompile&&$this->lessCompile)
					{
						$lessFiles=$this->_cacheGet('EAssetManager-less-updated-'.$src);
						if ($lessFiles&&is_array($lessFiles))
						{
							foreach ($lessFiles as $_file=>$_time)
							{
								if (filemtime($_file)!=$_time)
								{
									$lessCompile=true;
									break;
								}
							}
						}
						else
						{
							$lessCompile=true;
						}
						unset($lessFiles);
					}
					if (!file_exists($path)||$lessCompile||$this->lessForceCompile)
					{
						$lessc=new lessc();
						$lessc->setFormatter($this->lessFormatter);
						$lessCache=$lessc->cachedCompile($src);
						file_put_contents($path,$lessCache['compiled']);
						$this->_cacheSet('EAssetManager-less-updated-'.$src,$lessCache['files']);
					}
				}
			}
		}
		return parent::publish($path,$hashByName,$level,$forceCopy);
	}


	private function _getPath($path)
	{
		$alias=YiiBase::getPathOfAlias($path);
		if ($alias)
		{
			return $alias;
		}
		elseif (realpath($path)!==false)
		{
			return realpath($path);
		}
		else
		{
			return realpath(Yii::app()->basePath.'/../assets');
		}
	}


	private function _cacheSet($name,$value)
	{
		if (Yii::app()->cache)
		{
			return Yii::app()->cache->set($name,$value);
		}
		if (!file_exists(dirname(__FILE__).'/EAssetManager/cache'))
		{
			mkdir(dirname(__FILE__).'/EAssetManager/cache');
		}
		return file_put_contents(dirname(__FILE__).'/EAssetManager/cache/'.md5($name),serialize($value),LOCK_EX);
	}


	private function _cacheGet($name)
	{
		if (Yii::app()->cache)
		{
			return Yii::app()->cache->get($name);
		}
		if (file_exists(dirname(__FILE__).'/EAssetManager/cache/'.md5($name)))
		{
			return unserialize(file_get_contents(dirname(__FILE__).'/EAssetManager/cache/'.md5($name)));
		}
		return false;
	}

}

?>

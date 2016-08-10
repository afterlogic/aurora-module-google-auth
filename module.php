<?php

class GoogleAuthWebclientModule extends AApiModule
{
	protected $sService = 'google';
	
	protected $aSettingsMap = array(
		'EnableModule' => array(false, 'bool'),
		'Id' => array('', 'string'),
		'Secret' => array('', 'string')
	);
	
	protected $aRequireModules = array(
		'OAuthIntegratorWebclient'
	);
	
	public function init() 
	{
		$this->incClass('connector');
		$this->subscribeEvent('OAuthIntegratorAction', array($this, 'onOAuthIntegratorAction'));
		$this->subscribeEvent('GetServices', array($this, 'onGetServices'));
		$this->subscribeEvent('GetServicesSettings', array($this, 'onGetServicesSettings'));
		$this->subscribeEvent('UpdateServicesSettings', array($this, 'onUpdateServicesSettings'));
	}
	
	/**
	 * Adds dropbox service name to array passed by reference.
	 * 
	 * @param array $aServices Array with services names passed by reference.
	 */
	public function onGetServices(&$aServices)
	{
		if ($this->getConfig('EnableModule', false))
		{
			$aServices[] = $this->sService;
		}
	}
	
	/**
	 * Adds service settings to array passed by reference.
	 * 
	 * @param array $aServices Array with services settings passed by reference.
	 */
	public function onGetServicesSettings(&$aServices)
	{
		$aSettings = $this->GetAppData();
		if (!empty($aSettings))
		{
			$aServices[] = $aSettings;
		}
	}
	
	/**
	 * Returns module settings.
	 * 
	 * @return array
	 */
	public function GetAppData()
	{
		$oUser = \CApi::getAuthenticatedUser();
		if ($oUser && $oUser->Role === 0) // Super Admin
		{
			return array(
				'Name' => $this->sService,
				'DisplayName' => $this->GetName(),
				'EnableModule' => $this->getConfig('EnableModule', false),
				'Id' => $this->getConfig('Id', ''),
				'Secret' => $this->getConfig('Secret', '')
			);
		}
		
		if ($oUser && $oUser->Role === 1) // Power User
		{
			$oAccount = null;
			$oOAuthIntegratorWebclientDecorator = \CApi::GetModuleDecorator('OAuthIntegratorWebclient');
			if ($oOAuthIntegratorWebclientDecorator)
			{
				$oAccount = $oOAuthIntegratorWebclientDecorator->GetAccount($this->sService);
			}
			return array(
				'Connected' => $oAccount ? true : false
			);
		}
		
		return array();
	}
	
	/**
	 * Updates service settings.
	 * 
	 * @param array $aServices Array with new values for service settings.
	 * 
	 * @throws \System\Exceptions\ClientException
	 */
	public function onUpdateServicesSettings($aServices)
	{
		$aSettings = $aServices[$this->sService];
		
		if (is_array($aSettings))
		{
			$this->UpdateSettings($aSettings['EnableModule'], $aSettings['Id'], $aSettings['Secret']);
		}
	}
	
	/**
	 * Updates service settings.
	 * 
	 * @param boolean $EnableModule
	 * @param string $Id
	 * @param string $Secret
	 * 
	 * @throws \System\Exceptions\ClientException
	 */
	public function UpdateSettings($EnableModule, $Id, $Secret)
	{
		try
		{
			$this->setConfig('EnableModule', $EnableModule);
			$this->setConfig('Id', $Id);
			$this->setConfig('Secret', $Secret);
			$this->saveModuleConfig();
		}
		catch (Exception $ex)
		{
			throw new \System\Exceptions\ClientException(\System\Notifications::CanNotSaveSettings);
		}
		
		return true;
	}
	
	public function onOAuthIntegratorAction($sService, &$mResult)
	{
		if ($sService === $this->sService)
		{
			$mResult = false;
			$oConnector = new COAuthIntegratorConnectorGoogle($this);
			if ($oConnector)
			{
				$mResult = $oConnector->Init();
			}
		}
	}
	
	public function DeleteAccount()
	{
		$bResult = false;
		$oOAuthIntegratorWebclientDecorator = \CApi::GetModuleDecorator('OAuthIntegratorWebclient');
		if ($oOAuthIntegratorWebclientDecorator)
		{
			$bResult = $oOAuthIntegratorWebclientDecorator->DeleteAccount($this->sService);
		}		
		
		return $bResult;
	}	
}

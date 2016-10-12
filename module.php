<?php
/**
 * @copyright Copyright (c) 2016, Afterlogic Corp.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 * 
 * @package Modules
 */

class GoogleAuthWebclientModule extends AApiModule
{
	protected $sService = 'google';
	
	protected $aSettingsMap = array(
		'EnableModule' => array(false, 'bool'),
		'Id' => array('', 'string'),
		'Key' => array('', 'string'),
		'Secret' => array('', 'string')
	);
	
	protected $aRequireModules = array(
		'OAuthIntegratorWebclient'
	);
	
	/***** private functions *****/
	/**
	 * Initializes FacebookAuthWebclient Module.
	 * 
	 * @ignore
	 */
	public function init()
	{
		$this->incClass('connector');
		$this->subscribeEvent('OAuthIntegratorAction', array($this, 'onOAuthIntegratorAction'));
		$this->subscribeEvent('GetServices', array($this, 'onGetServices'));
		$this->subscribeEvent('GetServicesSettings', array($this, 'onGetServicesSettings'));
		$this->subscribeEvent('UpdateServicesSettings', array($this, 'onUpdateServicesSettings'));
	}
	
	/**
	 * Passes data to connect to service.
	 * 
	 * @ignore
	 * @param string $sService Service type to verify if data should be passed.
	 * @param boolean|array $mResult variable passed by reference to take the result.
	 */
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
	
	/**
	 * Adds service name to array passed by reference.
	 * 
	 * @ignore
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
	 * @ignore
	 * @param array $aServices Array with services settings passed by reference.
	 */
	public function onGetServicesSettings(&$aServices)
	{
		$aSettings = $this->GetSettings();
		if (!empty($aSettings))
		{
			$aServices[] = $aSettings;
		}
	}
	
	/**
	 * Updates service settings.
	 * 
	 * @ignore
	 * @param array $aServices Array with new values for service settings.
	 * 
	 * @throws \System\Exceptions\AuroraApiException
	 */
	public function onUpdateServicesSettings($aServices)
	{
		$aSettings = $aServices[$this->sService];
		
		if (is_array($aSettings))
		{
			$this->UpdateSettings($aSettings['EnableModule'], $aSettings['Id'], $aSettings['Secret']);
		}
	}
	/***** private functions *****/
	
	/***** public functions might be called with web API *****/
	/**
	 * Obtaines list of module settings for authenticated user.
	 * 
	 * @return array
	 */
	public function GetSettings()
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::Anonymous);
		
		$oUser = \CApi::getAuthenticatedUser();
		if (!empty($oUser) && $oUser->Role === \EUserRole::SuperAdmin)
		{
			return array(
				'Name' => $this->sService,
				'DisplayName' => $this->GetName(),
				'EnableModule' => $this->getConfig('EnableModule', false),
				'Id' => $this->getConfig('Id', ''),
				'Secret' => $this->getConfig('Secret', ''),
				'Key' => $this->getConfig('Key', '')
			);
		}
		
		if (!empty($oUser) && $oUser->Role === \EUserRole::NormalUser)
		{
			$oAccount = null;
			$oOAuthIntegratorWebclientDecorator = \CApi::GetModuleDecorator('OAuthIntegratorWebclient');
			if ($oOAuthIntegratorWebclientDecorator)
			{
				$oAccount = $oOAuthIntegratorWebclientDecorator->GetAccount($this->sService);
			}
			return array(
				'EnableModule' => $this->getConfig('EnableModule', false),
				'Connected' => $oAccount ? true : false
			);
		}
		
		return array();
	}
	
	/**
	 * Updates service settings.
	 * 
	 * @param boolean $EnableModule **true** if module should be enabled.
	 * @param string $Id Service app identificator.
	 * @param string $Secret Service app secret.
	 * @param string $Key Service app key.
	 * 
	 * @throws \System\Exceptions\AuroraApiException
	 */
	public function UpdateSettings($EnableModule, $Id, $Secret, $Key)
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::TenantAdmin);
		
		try
		{
			$this->setConfig('EnableModule', $EnableModule);
			$this->setConfig('Id', $Id);
			$this->setConfig('Secret', $Secret);
			$this->setConfig('Key', $Key);
			$this->saveModuleConfig();
		}
		catch (Exception $ex)
		{
			throw new \System\Exceptions\AuroraApiException(\System\Notifications::CanNotSaveSettings);
		}
		
		return true;
	}
	
	/**
	 * Deletes DropBox account.
	 * 
	 * @return boolean
	 */
	public function DeleteAccount()
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$bResult = false;
		$oOAuthIntegratorWebclientDecorator = \CApi::GetModuleDecorator('OAuthIntegratorWebclient');
		if ($oOAuthIntegratorWebclientDecorator)
		{
			$bResult = $oOAuthIntegratorWebclientDecorator->DeleteAccount($this->sService);
		}
		
		return $bResult;
	}
	/***** public functions might be called with web API *****/
}

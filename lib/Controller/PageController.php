<?php
namespace OCA\Carnet\Controller;

use OC_App;
use OCP\IRequest;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;

class PageController extends Controller {
	private $userId;
	private $config;
	public function __construct($AppName, IRequest $request, $UserId, $Config){
		parent::__construct($AppName, $request);
		$this->userId = $UserId;
		$this->config = $Config;
	}

	/**
	 * CAUTION: the @Stuff turns off security checks; for this page no admin is
	 *          required and no CSRF check. If you don't know what CSRF is, read
	 *          it up in the docs or you might create a security hole. This is
	 *          basically the only required method to add this exemption, don't
	 *          add it to any other method if you don't exactly know what it does
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function browser() {
		$parameters = [
			'nc_version' => \OCP\Util::getVersion()[0],
			'carnet_display_fullscreen' => $this->config->getAppValue('carnet', 'carnetDisplayFullscreen', 'no'),
			'app_version' => OC_App::getAppInfo($this->appName)['version'],
		];
		$response = new TemplateResponse($this->appName,"new_browser",$parameters);
		$response->renderAs("blank");
		$policy = new ContentSecurityPolicy();
		$policy->addAllowedFrameDomain('\'self\'');
		$policy->addAllowedFrameDomain('data:');
		
		$response->setContentSecurityPolicy($policy); // allow iframe
		return $response;
	}

	/**
	 * CAUTION: the @Stuff turns off security checks; for this page no admin is
	 *          required and no CSRF check. If you don't know what CSRF is, read
	 *          it up in the docs or you might create a security hole. This is
	 *          basically the only required method to add this exemption, don't
	 *          add it to any other method if you don't exactly know what it does
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index() {
		if($this->config->getAppValue('carnet', 'carnetDisplayFullscreen', 'no') === "yes")
			return $this->browser();
		$parameters = [
			'nc_version' => \OCP\Util::getVersion()[0],
			'carnet_display_fullscreen' => $this->config->getAppValue('carnet', 'carnetDisplayFullscreen', 'no'),
			'app_version' => OC_App::getAppInfo($this->appName)['version'],
		];
		$response = new TemplateResponse($this->appName,"index",$parameters);
		$policy = new ContentSecurityPolicy();
		$policy->addAllowedFrameDomain('\'self\'');
		$policy->addAllowedFrameDomain('data:');
		
		$response->setContentSecurityPolicy($policy); // allow iframe
		return $response;
	}

		/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function writer() {
		$parameters = [
			'app_version' => OC_App::getAppInfo($this->appName)['version'],
		];
		if($this->config->getUserValue($this->userId, $this->appName, "use_md_editor",0)){
			$response = new TemplateResponse($this->appName,"new_editor",$parameters);
		}
		else
			$response = new TemplateResponse($this->appName,"writer",$parameters);
		$policy = new ContentSecurityPolicy();
		$policy->addAllowedMediaDomain('blob:');
		$policy->addAllowedFrameDomain('\'self\'');
		$policy->addAllowedFrameDomain('data:');
		//needed by record encoder
		$policy->addAllowedScriptDomain('*');
		if (method_exists($policy, "addAllowedWorkerSrcDomain")){
			$policy->addAllowedWorkerSrcDomain('\'self\'');

		}
		$response->setContentSecurityPolicy($policy);
		$response->renderAs("blank");
		return $response;
	}
	/**
	* @NoAdminRequired
	* @NoCSRFRequired
	*/
   public function settings() {
		$parameters = [
			'carnet_display_fullscreen' => $this->config->getAppValue('carnet', 'carnetDisplayFullscreen', 'no'),
			'app_version' => OC_App::getAppInfo($this->appName)['version'],
		];
		$response =  new TemplateResponse($this->appName,"settings", $parameters);
		$response->renderAs("blank");
		$policy = new ContentSecurityPolicy();
        $policy->addAllowedFrameDomain('\'self\'');
		$response->setContentSecurityPolicy($policy); // allow iframe
		return $response;
   }

   	/**
	* @NoAdminRequired
	* @NoCSRFRequired
	*/
	public function importer() {
		$parameters = [
			'app_version' => OC_App::getAppInfo($this->appName)['version'],
		];
		$response =  new TemplateResponse($this->appName,"importer", $parameters);
		$response->renderAs("blank");
		
		return $response;
   }

   /**
	* @NoAdminRequired
	* @NoCSRFRequired
	*/
	public function exporter() {
		$parameters = [
			'app_version' => OC_App::getAppInfo($this->appName)['version'],
		];
		$response =  new TemplateResponse($this->appName,"exporter", $parameters);
		$response->renderAs("blank");
		
		return $response;
   }

}

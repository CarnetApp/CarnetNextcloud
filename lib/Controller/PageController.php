<?php
namespace OCA\Carnet\Controller;

use OCP\IRequest;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;

class PageController extends Controller {
	private $userId;
	private $RootFolder;
	public function __construct($AppName, IRequest $request, $UserId, $RootFolder){
		parent::__construct($AppName, $request);
		$this->userId = $UserId;
		$this->RootFolder = $RootFolder;
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
		$response = new TemplateResponse($this->appName,"index");
		$policy = new ContentSecurityPolicy();
        $policy->addAllowedFrameDomain('\'self\'');
        $response->setContentSecurityPolicy($policy); // allow iframe
		return $response;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function writer() {
		return new TemplateResponse($this->appName,"writer"); // templates/writer.php
	}

}
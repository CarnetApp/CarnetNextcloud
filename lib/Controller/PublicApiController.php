<?php

namespace OCA\Carnet\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\StreamResponse;

class PublicApiController extends Controller {
 
/**
	 * @PublicPage
	 * @NoCSRFRequired
   * 
*/
  public function getOpusEncoder(){

    $response = new StreamResponse(__DIR__.'/../../templates/CarnetElectron/reader/libs/recorder/encoderWorker.min.wasm');
    $response->addHeader("Content-Type", "application/wasm");
    return $response;
  }

 
  public function getOpusDecoder(){
    $response = new StreamResponse(__DIR__.'/../../templates/CarnetElectron/reader/libs/recorder/decoderWorker.min.wasm');
    $response->addHeader("Content-Type", "application/wasm");
    return $response;
  }


    /**
    * Validate the token of this share. If the token is invalid this controller
    * will return a 404.
    */
    public function isValidToken(): bool {
            return true;
    }

    /**
     * Allows you to specify if this share is password protected
     */
    protected function isPasswordProtected(): bool {
            return false;
    }
}
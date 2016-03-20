<?php
/**
 * Information ArchiTECH, LLC
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to contact@informationarchitech.com so we can send you a copy immediately.
 *
 *
 * @copyright  Copyright (c) 2013 Information ArchiTECH, LLC (http://www.informationarchitech.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     Information ArchiTECH <contact@informationarchitech.com>
 */

class Ia_TaxCloud_Model_Api_Address {

 	private $Address1;

 	private $Address2;

 	private $City;

 	private $State;

 	private $Zip5;

 	private $Zip4;

 	function __construct() {
	}

 	function setAddress1($address1) {
 		$this->Address1 = $address1;
 	}

 	function getAddress1() {
 		return $this->Address1;
 	}

 	function setAddress2($address2) {
 		$this->Address2 = $address2;
 	}

 	function getAddress2() {
 		return $this->Address2;
 	}

 	function setCity($city) {
 		$this->City = $city;
 	}

 	function getCity() {
 		return $this->City;
 	}

 	function setState($state) {
 		$this->State = $state;
 	}

 	function getState() {
 		return $this->State;
 	}

 	function setZip5($zip5) {
 		$this->Zip5 = $zip5;
 	}

 	function getZip5() {
 		return $this->Zip5;
 	}

 	function setZip4($zip4) {
 		$this->Zip4 = $zip4;
 	}

 	function getZip4() {
 		return $this->Zip4;
 	}

}

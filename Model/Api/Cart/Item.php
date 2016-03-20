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

class Ia_TaxCloud_Model_Api_Cart_Item {

 	private $ItemID;

 	private $Index;

 	private $TIC;

 	private $Price;

 	private $Qty;

	function __construct() {
 	}

 	function setItemID($itemID) {
 		$this->ItemID = $itemID;
 	}

 	function getItemID() {
 		return $this->ItemID;
 	}

 	function setIndex($index) {
 		$this->Index = $index;
 	}

 	function getIndex() {
 		return $this->Index;
 	}

 	function setTIC($TIC) {
 		$this->TIC = $TIC;
 	}

 	function getTIC() {
 		return $this->TIC;
 	}

 	function setPrice($price) {
 		$this->Price = $price;
 	}

 	function getPrice() {
 		return $this->Price;
 	}

 	function setQty($qty) {
 		$this->Qty = $qty;
 	}

 	function getQty() {
 		return $this->Qty;
 	}
    
}
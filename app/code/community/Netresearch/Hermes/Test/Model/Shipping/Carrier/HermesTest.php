<?php
/**
 * Netresearch Hermes
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to
 * newer versions in the future.
 *
 * @category    Netresearch
 * @package     Netresearch_Hermes
 * @copyright   Copyright (c) 2012 Netresearch GmbH & Co. KG (http://www.netresearch.de/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Hermes carrier unittest
 *
 * @category    Netresearch
 * @package     Netresearch_Hermes
 * @author      Thomas Birke <thomas.birke@netresearch.de>
 */
class Netresearch_Hermes_Test_Model_Shipping_Carrier_HermesTest extends EcomDev_PHPUnit_Test_Case_Config
{
    public function testIsActive()
    {
        $carrier = Mage::getModel('hermes/shipping_carrier_hermes');
        $this->assertTrue($carrier->isActive());
    }

    public function testIsTrackingAvailable()
    {
        $carrier = Mage::getModel('hermes/shipping_carrier_hermes');
        $this->assertTrue($carrier->isTrackingAvailable());
    }

    public function testCollectRates()
    {
        $request = Mage::getModel('shipping/rate_request');
        $result  = Mage::getModel('shipping/rate_result');
        $carrier = Mage::getModel('hermes/shipping_carrier_hermes');
        $this->assertEquals($result, $carrier->collectRates($request));
    }

    public function testGetTrackingInfo()
    {
        $trackingResult = $this->getModelMock('shipping/tracking_result', array('getAllTrackings'));
        $trackingResult->expects($this->any())
            ->method('getAllTrackings')
            ->will($this->returnValue(array('bar')));

        $carrier = $this->getModelMock('hermes/shipping_carrier_hermes', array('getTracking'));
        $carrier->expects($this->any())
            ->method('getTracking')
            ->will($this->returnValue($trackingResult));
        $this->assertEquals('bar', $carrier->getTrackingInfo('foo'));

        $trackingResult = 'xyz';
        $carrier = $this->getModelMock('hermes/shipping_carrier_hermes', array('getTracking'));
        $carrier->expects($this->any())
            ->method('getTracking')
            ->will($this->returnValue($trackingResult));
        $this->assertEquals('xyz', $carrier->getTrackingInfo('abc'));

        $trackingResult = '';
        $carrier = $this->getModelMock('hermes/shipping_carrier_hermes', array('getTracking'));
        $carrier->expects($this->any())
            ->method('getTracking')
            ->will($this->returnValue($trackingResult));
        $this->assertFalse($carrier->getTrackingInfo('abc'));
    }

    public function testGetHermesTracking()
    {
        $trackings = array('123', 'abc');

        $status = $this->getModelMock('shipping/tracking_result_status', array(
            'setCarrierTitle',
            'setCarrier',
            'setPopup'
        ));
        $status->expects($this->exactly(2))
            ->method('setCarrierTitle')
            ->with($this->equalTo('Hermes'))
            ->will($this->returnSelf());
        $status->expects($this->exactly(2))
            ->method('setCarrier')
            ->with($this->equalTo('hermes'))
            ->will($this->returnSelf());
        $status->expects($this->exactly(2))
            ->method('setPopup')
            ->with($this->equalTo(true))
            ->will($this->returnSelf());
        $this->replaceByMock('model', 'shipping/tracking_result_status', $status);

        $carrier = Mage::getModel('hermes/shipping_carrier_hermes');
        $result = $carrier->getTracking($trackings);

        $this->assertInstanceOf('Mage_Shipping_Model_Tracking_Result', $result);
        $tracks = $result->getAllTrackings();

        $this->assertEquals(2, count($tracks));
        $this->assertEquals('abc', $tracks[1]->getTracking());
        $this->assertEquals(
            Mage::getModel('hermes/config')->getTrackingUrl('abc'),
            $tracks[1]->getUrl()
        );
    }

    public function testGetSingleHermesTracking()
    {
        $trackings = 'wrgl';

        $carrier = Mage::getModel('hermes/shipping_carrier_hermes');
        $result = $carrier->getTracking($trackings);

        $this->assertInstanceOf('Mage_Shipping_Model_Tracking_Result', $result);
        $tracks = $result->getAllTrackings();

        $this->assertEquals(1, count($tracks));
        $this->assertEquals('wrgl', $tracks[0]->getTracking());
        $this->assertEquals(
            Mage::getModel('hermes/config')->getTrackingUrl('wrgl'),
            $tracks[0]->getUrl()
        );
    }
}

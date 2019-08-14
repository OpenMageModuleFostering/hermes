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
 * Hermes ConfigController unittest
 *
 * @category    Netresearch
 * @package     Netresearch_Hermes
 * @author      Thomas Birke <thomas.birke@netresearch.de>
 */
class Netresearch_Hermes_Test_Controller_ConfigControllerTest
    extends EcomDev_PHPUnit_Test_Case_Controller
{
    protected function setUp()
    {
        parent::setUp();
        $this->mockAdminUserSession();
    }

    /**
     * Logged in to Magento with fake user to test an adminhtml controllers
     */
    /**
     * Test whether fake user successfully logged in
     */
    public function testLoggedIn()
    {
        $this->assertTrue(Mage::getSingleton('admin/session')->isLoggedIn());
    }

    public function testUpdateListOfProducts()
    {
        Mage::getSingleton('adminhtml/session')->getMessages(true);
        $client = $this->getModelMock('hermes/client', array('updateListOfProducts'));
        $this->replaceByMock('model', 'hermes/client', $client);

        // client does nothing
        $this->dispatch('adminhtml/config/updateListOfProducts');
        $this->assertRequestRoute('adminhtml/config/updateListOfProducts');
        $error = Mage::getSingleton('adminhtml/session')->getMessages(true)->toString();
        $this->assertEquals('error: The list of products could not be updated.', $error);

        // client pretends to have updated product list
        $client->expects($this->any())
            ->method('updateListOfProducts')
            ->will($this->returnValue(true));

        $this->dispatch('adminhtml/config/updateListOfProducts');
        $success = Mage::getSingleton('adminhtml/session')->getMessages(true)->toString();
        $this->assertEquals('success: The list of products was successfully updated.', $success);

        $message = 'Login failed';
        $client->expects($this->any())
            ->method('updateListOfProducts')
            ->will($this->throwException(new Netresearch_Hermes_Model_Client_Exception($message)));

        $this->dispatch('adminhtml/config/updateListOfProducts');
        $error = Mage::getSingleton('adminhtml/session')->getMessages(true)->toString();
        $this->assertEquals("error: $message", $error);
    }
}

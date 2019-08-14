<?php
class Netresearch_Hermes_Test_Helper_Validate_OrderTest extends EcomDev_PHPUnit_Test_Case
{
    protected $helper;
    protected $config;
    protected $store;

    protected function setUp()
    {
        parent::setUp();
        $this->helper  =  Mage::helper('hermes/validate_order');
        $this->config = Mage::getModel('hermes/config');
        $this->store  = Mage::app()->getStore(0)->load(0);
    }

    /**
     * @test
     * @loadFixture ../../../../var/fixtures/orderList1
     */
    public function testGetOrder()
    {
        $order = Mage::getModel('sales/order')->load(31);
        $this->helper->setOrder($order);
        $this->assertEquals(100000055, $this->helper->getOrder()->getIncrementId());
    }

    /**
     * @test
     * @loadFixture ../../../../var/fixtures/orderList1
     */
    public function testIsShippedAsCod()
    {
        $order = Mage::getModel('sales/order')->load(31);
        $this->helper->setOrder($order);
        $this->assertFalse($this->helper->isShippedAsCod());

        $this->store->setConfig('hermes/shipment_options/cod_payment_methods', 'checkmo');
        $this->assertTrue($this->helper->isShippedAsCod());

        $this->store->setConfig('hermes/shipment_options/cod_payment_methods', '');
    }

    /**
     * @test
     * @loadFixture ../../../../var/fixtures/orderList1
     */
    public function testIsValidHermesShipment()
    {
        $order = Mage::getModel('sales/order')->load(31);
        $this->helper->setOrder($order);
        $this->assertTrue($this->helper->isValidHermesShipment());
        $this->assertEquals(0, count($this->helper->getValidationErrors()), "An validation error was encountered");
        $this->helper->resetValidationErrors();

        //set checkmo to cod and amount < 2500
        $this->store->setConfig('hermes/shipment_options/cod_payment_methods', 'checkmo');
        $this->assertTrue($this->helper->isValidHermesShipment());
        $this->assertEquals(0, count($this->helper->getValidationErrors()), "An validation error was encountered");
        $this->helper->resetValidationErrors();

        //set checkmo to cod and an amount > 2500
        $this->store->setConfig('hermes/shipment_options/cod_payment_methods', 'checkmo');
        $order->setGrandTotal(2501);
        $this->helper->setOrder($order);
        $this->assertFalse($this->helper->isValidHermesShipment());
        $this->assertEquals(1, count($this->helper->getValidationErrors()), "More/less then one validation error was encountered");
        $this->helper->resetValidationErrors();

        //check usa customer
        $this->store->setConfig('hermes/shipment_options/cod_payment_methods', '');
        $order = Mage::getModel('sales/order')->load(32);
        $this->helper->setOrder($order);
        $this->assertFalse($this->helper->isValidHermesShipment());
        $this->assertEquals(1, count($this->helper->getValidationErrors()), "More/less then one validation error was encountered");
        $this->helper->resetValidationErrors();

        //check usa customer with cod
        $this->store->setConfig('hermes/shipment_options/cod_payment_methods', 'checkmo');
        $this->assertFalse($this->helper->isValidHermesShipment());
        $this->assertEquals(2, count($this->helper->getValidationErrors()), "More/less then two validation error was encountered");
        $this->helper->resetValidationErrors();

        //check usa customer with cod and amount < 2500
        $this->store->setConfig('hermes/shipment_options/cod_payment_methods', 'checkmo');
        $order->setGrandTotal(2501);
        $this->helper->setOrder($order);
        $this->assertFalse($this->helper->isValidHermesShipment());
        $this->assertEquals(3, count($this->helper->getValidationErrors()), "More/less then three validation error was encountered");
        $this->helper->resetValidationErrors();

        $this->store->setConfig('hermes/shipment_options/cod_payment_methods', '');
    }

    /**
     * @test
     */
    public function isCreatedAfterHermesInstallation()
    {
        $order = Mage::getModel('sales/order');
        $order->setCreatedAt('2012-04-01');
        $this->helper->setOrder($order);

        $config = $this->getModelMock('hermes/config', array('getInstallationDate'));
        $config->expects($this->any())
            ->method('getInstallationDate')
            ->will($this->returnValue(strtotime('2. April 2012')));
        $this->replaceByMock('model', 'hermes/config', $config);

        $this->assertFalse($this->helper->isCreatedAfterHermesInstallation());

        $order->setCreatedAt('2012-04-03');
        $this->helper->setOrder($order);



        $this->assertTrue($this->helper->isCreatedAfterHermesInstallation());
    }
}


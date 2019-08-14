<?php

class Netresearch_Hermes_Test_Controller_PermissionsDeniedControllerTest
    extends EcomDev_PHPUnit_Test_Case_Controller
{
    protected function setUp()
    {
        parent::setUp();
        $this->mockAdminUserSession();
    }

    protected function _allowGlobalAccess()
    {
        $session = $this->getModelMock('admin/session', array('isAllowed'));
        $session->expects($this->any())
            ->method('isAllowed')
            ->will($this->returnCallback(array($this, 'fakePermission')));
        $this->replaceByMock('singleton', 'admin/session', $session);
    }

    public static function fakePermission($path)
    {
        return 'sales/shipment' === $path;
    }

    public function testCheckSectionAllowed()
    {
        $this->dispatch('adminhtml/parcel/transmitHermesParcels');
        $this->assertRedirect();
        Mage::getSingleton('adminhtml/session')->getMessages(true);
    }


}

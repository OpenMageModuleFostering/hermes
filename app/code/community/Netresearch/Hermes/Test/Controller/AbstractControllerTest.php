<?php

abstract class Netresearch_Hermes_Test_Controller_AbstractControllerTest
    extends EcomDev_PHPUnit_Test_Case_Controller
{
    const FAKE_USER_ID = 999999999;
    
    public function tearDown()
    {
        $adminSession = Mage::getSingleton('admin/session');
        $adminSession->unsetAll();
        if ($adminSession->getCookie()) {
            $adminSession->getCookie()->delete($adminSession->getSessionName());
        }
        parent::tearDown();
    }

    /**
     * _workaroundAdminMenuIssue 
     * 
     * @see https://github.com/IvanChepurnyi/EcomDev_PHPUnit/issues/26
     * @return void
     */
    protected function _workaroundAdminMenuIssue()
    {
        $menuBlock = $this->getBlockMock('adminhtml/page_menu', array('_toHtml'));
        $menuBlock->expects($this->any())
            ->method('_toHtml')
            ->will($this->returnCallback(array($this, 'getAdminhtmlPageMenuTemplate')));
        $this->replaceByMock('block', 'adminhtml/page_menu', $menuBlock);
    }

    public static function getAdminhtmlPageMenuTemplate()
    {
        if (function_exists('drawMenuLevel')) {
            return '';
        }
        return Mage::getBlockSingleton('adminhtml/page_menu')->renderView();
    }
    
    protected function _fakeLogin()
    {
        $this->_workaroundAdminMenuIssue();
        $this->_registerUserMock();
        $this->_allowGlobalAccess();
        Mage::getSingleton('adminhtml/url')->turnOffSecretKey();
        $session = Mage::getSingleton('admin/session');
    
        // segmentation fault when trying to delete old session id:
        // Mage_Core_Model_Session_Abstract_Varien::regenerateSessionId()
        // workaround: use <session_save><![CDATA[db]]></session_save>
        Mage::getConfig()->setNode(Mage_Core_Model_Session::XML_NODE_SESSION_SAVE, 'db');
        
        $user = $session->login('fakeuser', 'fakeuser_pass');
    }
    
    protected function _allowGlobalAccess()
    {
        $session = $this->getModelMock('admin/session', array('isAllowed'));
        $session->expects($this->any())
            ->method('isAllowed')
            ->will($this->returnValue(true));
        $this->replaceByMock('singleton', 'admin/session', $session);
    }
    
    /**
     * Creates a mock object for admin/user Magento Model
     *
     * @return My_Module_Test_Controller_Adminhtml_Controller
     */
    protected function _registerUserMock()
    {
        $user = $this->getModelMock('admin/user');
        $user->expects($this->any())->method('getId')->will($this->returnValue(self::FAKE_USER_ID));
        $this->replaceByMock('model', 'admin/user', $user);
        return $this;
    }
}

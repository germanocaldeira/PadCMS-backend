<?php
/**
 * LICENSE
 *
 * This software is governed by the CeCILL-C  license under French law and
 * abiding by the rules of distribution of free software.  You can  use,
 * modify and/ or redistribute the software under the terms of the CeCILL-C
 * license as circulated by CEA, CNRS and INRIA at the following URL
 * "http://www.cecill.info".
 *
 * As a counterpart to the access to the source code and  rights to copy,
 * modify and redistribute granted by the license, users are provided only
 * with a limited warranty  and the software's author,  the holder of the
 * economic rights,  and the successive licensors  have only  limited
 * liability.
 *
 * In this respect, the user's attention is drawn to the risks associated
 * with loading,  using,  modifying and/or developing or reproducing the
 * software by the user in light of its specific status of free software,
 * that may mean  that it is complicated to manipulate,  and  that  also
 * therefore means  that it is reserved for developers  and  experienced
 * professionals having in-depth computer knowledge. Users are therefore
 * encouraged to load and test the software's suitability as regards their
 * requirements in conditions enabling the security of their systems and/or
 * data to be ensured and,  more generally, to use and operate it in the
 * same conditions as regards security.
 *
 * The fact that you are presently reading this means that you have had
 * knowledge of the CeCILL-C license and that you accept its terms.
 *
 * @author Copyright (c) PadCMS (http://www.padcms.net)
 * @version $DOXY_VERSION
 */

class ElementResourceCopyTest extends AM_Test_PHPUnit_DatabaseTestCase
{
    /** @var PHPUnit_Framework_MockObject_MockObject $standardMock **/
    var $standardMock = null;

    protected function _getDataSetYmlFile()
    {
        return dirname(__FILE__)
                . DIRECTORY_SEPARATOR . '_fixtures'
                . DIRECTORY_SEPARATOR . 'copy.yml';
    }

    public function testShouldCopyResource()
    {
        //GIVEN
        $thumbnailerMock = $this->getMock('AM_Handler_Thumbnail', array('addSourceFile', 'loadAllPresets', 'createThumbnails', 'getSources'));
        AM_Handler_Locator::getInstance()->setHandler('thumbnail', $thumbnailerMock);
        $this->standardMock = $this->getMock('AM_Tools_Standard', array('is_dir', 'mkdir', 'copy'));

        $element = AM_Model_Db_Table_Abstract::factory('element')->findOneBy('id', 1);
        $resource = new AM_Model_Db_Element_Data_MockResource($element);
        $resource->addAdditionalResourceKey('additional_key');
        $element->id = 2;

        $oldDir = AM_Tools::getContentPath(AM_Model_Db_Element_Data_Resource::TYPE, 1);
        $newDir = AM_Tools::getContentPath(AM_Model_Db_Element_Data_Resource::TYPE, 2);

        //THEN
        $this->standardMock->expects($this->at(0))
             ->method('is_dir')
             ->with($this->equalTo($oldDir))
             ->will($this->returnValue(true));

        $this->standardMock->expects($this->at(1))
             ->method('is_dir')
             ->with($this->equalTo($newDir))
             ->will($this->returnValue(false));

        $this->standardMock->expects($this->once())
             ->method('mkdir')
             ->with($this->equalTo($newDir),  $this->equalTo(0777), $this->equalTo(true))
             ->will($this->returnValue(true));

        $this->standardMock->expects($this->at(3))
             ->method('copy')
             ->with($this->equalTo($oldDir . DIRECTORY_SEPARATOR . "resource.png"),
                    $this->equalTo($newDir . DIRECTORY_SEPARATOR . "resource.png"))
             ->will($this->returnValue(true));

        $this->standardMock->expects($this->at(4))
             ->method('copy')
             ->with($this->equalTo($oldDir . DIRECTORY_SEPARATOR . "additional_key.png"),
                    $this->equalTo($newDir . DIRECTORY_SEPARATOR . "additional_key.png"))
             ->will($this->returnValue(true));

        $thumbnailerMock->expects($this->any())
                ->method('addSourceFile')
                ->will($this->returnValue($thumbnailerMock));

        $thumbnailerMock->expects($this->any())
                ->method('loadAllPresets')
                ->will($this->returnValue($thumbnailerMock));

        $thumbnailerMock->expects($this->any())
                ->method('getSources')
                ->will($this->returnValue(array()));

        //WHEN
        $resource->copy();

        //THEN
        $queryTable    = $this->getConnection()->createQueryTable("element_data", "SELECT id_element, key_name, value FROM element_data ORDER BY id");
        $expectedTable = $this->createFlatXMLDataSet(dirname(__FILE__) . "/_dataset/copy.xml")
                              ->getTable("element_data");

        $this->assertTablesEqual($expectedTable, $queryTable);
    }
}

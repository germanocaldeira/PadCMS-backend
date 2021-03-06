<?php
/**
 * @file
 * AM_Component_List_Application class definition.
 *
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

/**
 * Applications list component
 * @ingroup AM_Component
 * @todo refactoring
 */
class AM_Component_List_Application extends AM_Component_Grid
{
    public function __construct(AM_Controller_Action $oActionController, $iClientId)
    {
        $iClientId = intval($iClientId);

        $oQuery = $oActionController->oDb->select()
                ->from('application')

                ->joinLeft('issue', 'issue.application = application.id AND issue.deleted = "no"', null)

                ->joinLeft(array('issue1' => 'issue'),
                        'application.id = issue1.application AND issue1.deleted = "no" '
                            . $oActionController->oDb->quoteInto('AND issue1.state = ?', AM_Model_Db_State::STATE_PUBLISHED), null)

                ->joinLeft('revision',
                        'revision.issue = issue1.id AND revision.deleted = "no" '
                            . $oActionController->oDb->quoteInto('AND revision.state = ?', AM_Model_Db_State::STATE_PUBLISHED), null)

                ->where('application.deleted = ?', 'no')
                ->where('application.client = ?', $iClientId)

                ->group('application.id')

                ->columns(array(
                    'published_revision' => 'revision.id',
                    'issue_count' => new Zend_Db_Expr('COUNT(DISTINCT(issue.id))')
                ));

        parent::__construct($oActionController, 'grid', $oActionController->oDb, $oQuery, 'application.title', array(), 4, 'subselect');
    }
}
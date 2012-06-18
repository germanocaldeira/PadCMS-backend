<?php
/**
 * @file
 * AM_Model_Db_Term class definition.
 *
 * LICENSE
 *
 * $DOXY_LICENSE
 *
 * @author $DOXY_AUTHOR
 * @version $DOXY_VERSION
 */

/**
 * Term model class
 * @ingroup AM_Model
 */
class AM_Model_Db_Term extends AM_Model_Db_Base_NestedSet
{
    /** @var AM_Model_Db_Vocabulary */
    protected $_oVocabulary = null; /**< @type AM_Model_Db_Vocabulary */
    /** @var AM_Model_Db_Term_Data_Resource */
    protected $_oResources  = null; /**< @type AM_Model_Db_Term_Data_Resource */
    /** @var AM_Model_Db_Rowset_Page */
    protected $_oPages      = null; /**< @type AM_Model_Db_Rowset_Page */

    /**
     * Check if term is tag
     * @return bool
     */
    protected function _isTag()
    {
        $oVocabulary = $this->getVocabulary();
        if (0 == $oVocabulary->has_hierarchy && 1 == $oVocabulary->multiple) {
            return true;
        }

        return false;
    }

    /**
     * Check if term is TOC
     * @return bool
     */
    protected function _isToc()
    {
        $oVocabulary = $this->getVocabulary();
        if (1 == $oVocabulary->has_hierarchy && 0 == $oVocabulary->multiple) {
            return true;
        }

        return false;
    }

    /**
     * Copy term data from one revision to other
     * @param AM_Model_Db_Revision $oRevisionTo
     * @return AM_Model_Db_Term
     */
    public function moveToRevision(AM_Model_Db_Revision $oRevisionTo)
    {
        $oTagVocabulary = $oRevisionTo->getVocabularyTag();
        $oTocVocabulary = $oRevisionTo->getVocabularyToc();

        $bNeedToUpdate = false;

        if ($this->_isTag()) {
            //Check vacabulary of term & new revision
            if ($oTagVocabulary->id != $this->vocabulary) {
                $this->vocabulary   = $oTagVocabulary->id;
                $bNeedToUpdate       = true;
            }
        } elseif($this->_isToc()) {
            //Check vacabulary of term & new revision
            if ($oTocVocabulary->id != $this->vocabulary) {
                $this->vocabulary   = $oTocVocabulary->id;
                $bNeedToUpdate       = true;
            }
        }

        if (!empty($this->revision)) {
            if($oRevisionTo->id != $this->revision) {
                $this->revision   = $oRevisionTo->id;
                $bNeedToUpdate     = true;
            }
        }

        if ($bNeedToUpdate) {
            $this->save();
        }

        return $this;
    }

    /**
     * Copy term data from one revision to other
     * @param AM_Model_Db_Revision $oRevisionTo
     * @return AM_Model_Db_Term
     */
    public function copyToRevision(AM_Model_Db_Revision $oRevisionTo)
    {
        $oTagVocabulary = $oRevisionTo->getVocabularyTag();
        $oTocVocabulary = $oRevisionTo->getVocabularyToc();

        $oResources = $this->getResources();

        $bNeedToInsert = false;

        if ($this->_isTag()) {
            //Check vacabulary of term & new revision
            if ($oTagVocabulary->id != $this->vocabulary) {
                $this->vocabulary   = $oTagVocabulary->id;
                $bNeedToInsert       = true;
            }
        } elseif($this->_isToc()) {
            //Check vacabulary of term & new revision
            if ($oTocVocabulary->id != $this->vocabulary) {
                $this->vocabulary   = $oTocVocabulary->id;
                $bNeedToInsert       = true;
            }
        }

        if (!empty($this->revision)) {
            $this->revision   = $oRevisionTo->id;
            $bNeedToInsert     = true;
        }

        if ($bNeedToInsert) {
            $iIdOld = $this->id;

            $aData               = array();
            $aData['updated']    = null;
            $aData['vocabulary'] = $this->vocabulary;
            $aData['revision']   = $this->revision;

            $this->copy($aData);

            $oResources->copy();
        }
        return $this;
    }

    /**
     * Set resources
     * @param AM_Model_Db_Term_Data_Resource $oResources
     * @return AM_Model_Db_Term
     */
    public function setResources(AM_Model_Db_Term_Data_Resource $oResources)
    {
        $this->_oResources = $oResources;

        return $this;
    }

    /**
     * Get resources of term
     * @return AM_Model_Db_Term_Data_Resource
     */
    public function getResources()
    {
        if (is_null($this->_oResources)){
            $this->fetchResources();
        }

        return $this->_oResources;
    }

    /**
     * Fetch resources of term
     * @return AM_Model_Db_Term
     */
    public function fetchResources()
    {
        $this->_oResources = new AM_Model_Db_Term_Data_Resource($this);

        return $this;
    }

    /**
     * Update parent term id
     * @return AM_Model_Db_Term
     */
    public function updateReletations()
    {
        $oParent = $this->getParent();
        if (!empty($oParent)) {
            if ($this->parent_term != $oParent->id) {
                $this->parent_term = $oParent->id;
                $this->save();
            }
        }

        return $this;
    }

    /**
     * Save page and term relations
     * @param AM_Model_Db_Page $oPage
     * @return AM_Model_Db_Term
     */
    public function saveToPage(AM_Model_Db_Page $oPage)
    {
        $oTermPage = AM_Model_Db_Table_Abstract::factory('term_page')->findOneBy(array('page' => $oPage->id, 'term' => $this->id));

        if (is_null($oTermPage)) {
            $oTermPage       = new AM_Model_Db_TermPage();
            $oTermPage->page = $oPage->id;
            $oTermPage->term = $this->id;
            $oTermPage->save();
        }

        return $this;
    }

    /**
     * Set vocabulary
     * @return AM_Model_Db_Term
     */
    public function setVocabulary(AM_Model_Db_Vocabulary $oVocabulary)
    {
        $this->_oVocabulary = $oVocabulary;

        return $this;
    }

    /**
     * Get vocabulary
     * @return AM_Model_Db_Vocabulary
     */
    public function getVocabulary()
    {
        if (is_null($this->_oVocabulary)) {
            $this->fetchVocabulary();
        }

        return $this->_oVocabulary;
    }

    /**
     * Fetch vocabulary
     * @return AM_Model_Db_Term
     */
    public function fetchVocabulary()
    {
        $this->_oVocabulary = AM_Model_Db_Table_Abstract::factory('vocabulary')->findOneBy('id', $this->vocabulary);

        if (is_null($this->_oVocabulary)) {
            throw new AM_Model_Db_Exception(sprintf('Term "%s" has no vocabulary', $this->id));
        }

        return $this;
    }

    /**
     * Get terms pages
     * @return AM_Model_Db_Rowset_Page
     */
    public function getPages()
    {
        if (is_null($this->_oPages)) {
            $this->fetchPages();
        }

        return $this->_oPages;
    }

    /**
     * Fetch terms pages
     * @return AM_Model_Db_Term
     */
    public function fetchPages()
    {
        $this->_oPages = AM_Model_Db_Table_Abstract::factory('term_page')
                ->findPagesByTermId($this->id);

        return $this;
    }

    /**
     * Filter title value
     *
     * @param string $sValue
     * @return string
     */
    public function filterValueTitle($sValue)
    {
        $sValue = AM_Tools::filter_xss($sValue);

        return $sValue;
    }

    /**
     * Filter description value
     *
     * @param string $sValue
     * @return string
     */
    public function filterValueDescription($sValue)
    {
        $sValue = AM_Tools::filter_xss($sValue);

        return $sValue;
    }

    /**
     * Filter color value
     *
     * @param string $sValue
     * @return string
     */
    public function filterValueColor($sValue)
    {
        if (!preg_match('/^#?+([0-9a-f]{3}(?:[0-9a-f]{3})?)$/iD', $sValue, $aMatches) || count($aMatches) < 2) {
            $sValue = null;
        }

        return $sValue;
    }
}
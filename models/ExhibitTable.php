<?php 
/**
* Exhibit Table
*/
class ExhibitTable extends Omeka_Db_Table
{
    public function findBySlug($slug)
    {
        $db = $this->getDb();
        $select = new Omeka_Db_Select;
        $select->from(array('e'=>$db->Exhibit), array('e.*'));
        $select->where("e.slug = ?");
        $select->limit(1);
        
        new ExhibitPermissions($select);
        
        return $this->fetchObject($select, array($slug));       
    }
    
    /**
     * Override Omeka_Table::count() to retrieve a permissions-limited
     *
     * @return void
     **/
    public function count()
    {
        $db = $this->getDb();
        $select = new Omeka_Db_Select;
        
        $select->from("$db->Exhibit e", "COUNT(DISTINCT(e.id))");
        
        new ExhibitPermissions($select);
        
        return $db->fetchOne($select);
    }
    
    public function find($id)
    {
        $db = $this->getDb();
        
        $select = new Omeka_Db_Select;
        
        $select->from(array('e'=>$db->Exhibit), array('e.*'));
        $select->where("e.id = ?");
        
        new ExhibitPermissions($select);
        
        return $this->fetchObject($select, array($id));
    }
    
    public function exhibitHasItem($exhibit_id, $item_id)
    {
        $db = $this->getDb();
        
        $sql = "SELECT COUNT(i.id) FROM $db->Item i 
                INNER JOIN $db->ExhibitPageEntry ip ON ip.item_id = i.id 
                INNER JOIN $db->ExhibitPage sp ON sp.id = ip.page_id
                INNER JOIN $db->ExhibitSection s ON s.id = sp.section_id
                INNER JOIN $db->Exhibit e ON e.id = s.exhibit_id
                WHERE e.id = ? AND i.id = ?";
                
        $count = (int) $db->fetchOne($sql, array((int) $exhibit_id, (int) $item_id));

        return ($count > 0);
    }
    
    public function findBy($params=array())
    {
        $db = $this->getDb();
        
        $select = new Omeka_Db_Select;
        
        $select->from(array('e'=>$db->Exhibit), array('e.*'));

        if(isset($params['tags'])) {
            $tags = explode(',', $params['tags']);
            $select->joinInner(array('tg'=>$db->Taggings), 'tg.relation_id = e.id', array());
            $select->joinInner(array('t'=>$db->Tag), "t.id = tg.tag_id", array());
            foreach ($tags as $k => $tag) {
                $select->where('t.name = ?', trim($tag));
            }
            
            //Ah, inheritance
            $select->where("tg.type = 'Exhibit'");
        }
        
        if (isset($params['limit']))
        {
            $select->limit($params['limit']);
        }
        
        if (isset($params['recent']) && $params['recent'] == true)
        {
            $select->order('e.id DESC');
        }
        
        new ExhibitPermissions($select);
        
        $exhibits = $this->fetchObjects($select);
        
        return $exhibits;
    }
    
    /**
     * @duplication CollectionTable::findRandomFeatured(), ItemTable::findRandomFeatured()
     *
     * @return Exhibit|false
     **/
    public function findRandomFeatured()
    {
        $db = $this->getDb();
        
        $select = new Omeka_Db_Select;
        
        $select->from(array('e'=>$db->Exhibit))->where("e.featured = 1")->order("RAND()")->limit(1);
        
        return $this->fetchObject($select);
    }
    
    protected function _getColumnPairs()
    {        
        return array('e.id', 'e.title');
    }
    
    /**
     * Adds an advanced search subquery to the lucene search query 
     *
     * @param Zend_Search_Lucene_Search_Query_Boolean $advancedSearchQuery
     * @param string|array $requestParams An associative array of request parameters
     */
    public function addAdvancedSearchQueryForLucene($advancedSearchQuery, $requestParams) 
    {
        if ($search = Omeka_Search::getInstance()) {
            
            // Build an advanced search query for the item
            $advancedSearchQueryForExhibit = new Zend_Search_Lucene_Search_Query_Boolean();
            foreach($requestParams as $requestParamName => $requestParamValue) {
                switch($requestParamName) {

                    case 'public':
                        if (is_true($requestParamValue)) {
                            $subquery = $search->getLuceneTermQueryForFieldName(Omeka_Search::FIELD_NAME_IS_PUBLIC, Omeka_Search::FIELD_VALUE_TRUE, true);
                            $advancedSearchQueryForItem->addSubquery($subquery, true);
                        }
                    break;

                    case 'tag':
                    case 'tags':
                        $this->filterByTagsLucene($advancedSearchQueryForExhibit, $requestParamValue);
                        break;

                }
            }

            // add the exhibit advanced search query to the searchQuery as a disjunctive subquery 
            // (i.e. there will be OR statements between each of models' the advanced search queries)
            $advancedSearchQuery->addSubquery($advancedSearchQueryForExhibit);
        }        
    }
    
    /**
     * Filters the exhibit by comma-delimited tags
     * 
     * @param Zend_Search_Lucene_Search_Query_Boolean $searchQuery
     * @param string|array $tags A comma-delimited string or an array of tag 
     *         names.
     */
    public function filterByTagsLucene($searchQuery, $tags)
    {
        if ($search = Omeka_Search::getInstance()) {
            if (!is_array($tags)) {
                $tags = explode(',', $tags);
            }
            // make all of the tags required (i.e. conjoin the tags with AND)
            foreach ($tags as $tag) {
                $subquery = $search->getLuceneTermQueryForFieldName(Omeka_Search::FIELD_NAME_TAG, trim($tag));
                $searchQuery->addSubquery($subquery, true);
            }
        }
    }
}
 
?>

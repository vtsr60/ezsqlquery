<?php

/*! \file ezfsolrdocumentfieldsqlquery.php
*/

/*!
  \class ezfSolrDocumentFieldSQLQuery ezfsolrdocumentfieldobjectrelation.php

*/

class ezfSolrDocumentFieldSQLQuery extends ezfSolrDocumentFieldBase
{


    /**
     * @see ezfSolrDocumentFieldBase::getData()
     */
    public function getData()
    {
        $contentClassAttribute = $this->ContentObjectAttribute->attribute( 'contentclass_attribute' );
        $fieldName = self::getFieldName( $contentClassAttribute );
        $content = $this->ContentObjectAttribute->attribute( 'content' );
        $arraydata = array();
        foreach($content['main']['result'] as $row){
            $currentrow = array();
            foreach($row as $key => $value){
                $currentrow[] = "$key:$value";
            }
            $arraydata[] = implode(',', $currentrow);
        }
        $textdata = implode('|', $arraydata);
        return array( $fieldName => array($textdata) );
    }
}

?>

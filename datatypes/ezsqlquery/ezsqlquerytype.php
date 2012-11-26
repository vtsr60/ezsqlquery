<?php


class eZSQLQueryType extends eZDataType {

	const DATA_TYPE_STRING     = 'ezsqlquery';
	const DEFAULT_STRING_FIELD = 'data_text1';
    const SQL_KEYS_VARIABLE = "_ezsqlquery_sql_keys_";
    const SELECT_QUERY_VARIABLE = "_ezsqlquery_select_query_";
	const INSERT_QUERY_VARIABLE = "_ezsqlquery_insert_query_";
	const UPDATE_QUERY_VARIABLE = "_ezsqlquery_update_query_";
	const DELETE_QUERY_VARIABLE = "_ezsqlquery_delete_query_";
	const KEYS_QUERY_VARIABLE = "_ezsqlquery_keys_query_";
    const VIEWS_NAME_VARIABLE = "_ezsqlquery_views_name_";
    const VIEWS_QUERYS_VARIABLE = "_ezsqlquery_views_query_";


    public function eZSQLQueryType() {
		$this->eZDataType(
			self::DATA_TYPE_STRING,
			ezpI18n::tr( 'extension/ezsqlquery', 'SQL Query', 'Datatype name' ),
			array( 'serialize_supported' => true, 'translation_allowed' => false )
		);
	}


    function validateObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute )
    {
        $classContent = $contentObjectAttribute->attribute('contentclass_attribute')->content();
        if ( $http->hasPostVariable( $base . '_ezsqlquery_new_row_' . $contentObjectAttribute->attribute( 'id' ) ) )
        {
            $rows = $http->postVariable( $base . '_ezsqlquery_new_row_' . $contentObjectAttribute->attribute( 'id' ) );
            $newitem = array();
            $firstheading = false;
            foreach($rows as $items){
                foreach($items as $key => $value){
                    if(!$firstheading)
                        $firstheading = $key;
                    elseif($firstheading == $key){
                        $newrows[] = $newitem;
                        $newitem = array();
                    }
                    if(trim($value) != '')
                        $newitem[$key] = mysql_real_escape_string($value);
                }

            }
            if(count($newitem)){
                $newrows[] = $newitem;
            }
            foreach($newrows as $item){
                if(count($item) && count(array_diff($classContent['SQLKeys'], array_keys($item)))){
                    $contentObjectAttribute->setValidationError( ezpI18n::tr( 'kernel/classes/datatypes',
                        'Input required for Key fields :'.implode(',', array_diff($classContent['SQLKeys'], array_keys($item))).'.' ) );
                    return eZInputValidator::STATE_INVALID;
                }
            }
        }
        return eZInputValidator::STATE_ACCEPTED;
    }

    /*!
     Fetches the http post var string input and stores it in the data instance.
    */
    function fetchObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute )
    {
        $data = array();
        $classContent = $contentObjectAttribute->attribute('contentclass_attribute')->content();
        $content = $contentObjectAttribute->content();
        $updaterows = array();
        if ( $http->hasPostVariable( $base . '_ezsqlquery_row_' . $contentObjectAttribute->attribute( 'id' ) ) )
        {
            $rows = $http->postVariable( $base . '_ezsqlquery_row_' . $contentObjectAttribute->attribute( 'id' ) );
            foreach($rows as $index => $row){
                $rowmodified = false;
                $newvalues = $content['main']['result'][$index];
                foreach($row as $key => $value ){
                    $rowmodified = $rowmodified || ($value != $content['main']['result'][$index][$key]);
                    $newvalues[$key] = mysql_real_escape_string($value);
                }
                if($rowmodified){
                    $updaterows[] = $newvalues;
                }

            }
        }
        $newrows = array();
        if ( $http->hasPostVariable( $base . '_ezsqlquery_new_row_' . $contentObjectAttribute->attribute( 'id' ) ) )
        {
            $rows = $http->postVariable( $base . '_ezsqlquery_new_row_' . $contentObjectAttribute->attribute( 'id' ) );
            $newitem = array();
            $firstheading = false;
            foreach($rows as $items){
                foreach($items as $key => $value){
                    if(!$firstheading)
                        $firstheading = $key;
                    elseif($firstheading == $key){
                        if(count($newitem) && !count(array_diff($classContent['SQLKeys'], array_keys($newitem)))){
                            $newrows[] = $newitem;
                            $newitem = array();
                        }
                    }
                    if(trim($value) != '')
                        $newitem[$key] = mysql_real_escape_string($value);
                }
            }
            if(count($newitem) && !count(array_diff($classContent['SQLKeys'], array_keys($newitem)))){

                $newrows[] = $newitem;
            }
        }

        $deleterows = array();
        if ( $http->hasPostVariable( $base . '_ezsqlquery_delete_row_' . $contentObjectAttribute->attribute( 'id' ) ) )
        {
            $deleteindexs = $http->postVariable( $base . '_ezsqlquery_delete_row_' . $contentObjectAttribute->attribute( 'id' ) );
            foreach($deleteindexs as $index)
                if(isset($content['main']['result'][$index]))
                    $deleterows[] = $content['main']['result'][$index];
        }

        $serializedata = serialize(array('update' => $updaterows,
                                'new' => $newrows,
                                'delete' => $deleterows));
        $contentObjectAttribute->setAttribute( 'data_text', $serializedata );
        return true;
    }

    function onPublish( $contentObjectAttribute, $contentObject, $publishedNodes )
    {
        $serializedata = $contentObjectAttribute->attribute( 'data_text' );
        $data = unserialize($serializedata);
        $classContent = $contentObjectAttribute->attribute('contentclass_attribute')->content();
        $updatequery = trim($classContent['UpdateQuery']);
        $insertquery = trim($classContent['InsertQuery']);
        $deletequery = trim($classContent['DeleteQuery']);

        $Querys = array();
        if(count($data['new']) && $classContent['can_insert']){
            foreach($data['new'] as $newvalues){
                $Querys[] = self::parseTPLString($insertquery, $contentObjectAttribute, $newvalues);
            }
        }
        if(count($data['update']) && $classContent['can_update']){
            foreach($data['update'] as $updatevalues){
                $Querys[] = self::parseTPLString($updatequery, $contentObjectAttribute, $updatevalues);
            }
        }
        if(count($data['delete']) && $classContent['can_delete']){
            foreach($data['delete'] as $deletevalues){
                $Querys[] = self::parseTPLString($deletequery, $contentObjectAttribute, $deletevalues);
            }
        }
        if(count($Querys)){
            $db = eZDB::instance();
            $db->begin();
            foreach($Querys as $Query)
                $db->query($Query);
            $db->commit();
        }
        return true;
    }

    /*!
     Returns the content.
    */
    function objectAttributeContent( $contentObjectAttribute )
    {
        $classAttribute = $contentObjectAttribute->attribute('contentclass_attribute');
        $classContent = $classAttribute->content();
        $content = array('main' => array('sql' => '',
                                            'result' => null,
                                            'count' => 0,
                                            'heading' => array()));
        if($classContent && isset($classContent['SelectQuery'])){
            $content['main'] = self::processQuery($classContent['SelectQuery'], $contentObjectAttribute);
        }
        if($classContent && isset($classContent['Views']) && count($classContent['Views'])){
            foreach($classContent['Views'] as $name => $query){
                $content[$name] = self::processQuery($query, $contentObjectAttribute);
            }
        }
        return $content;
    }

    //CONTENT CLASS ATTRIBUTE FUNCTIONS
    function validateClassAttributeHTTPInput( $http, $base, $classAttribute )
    {
        $selectQueryName = $base . self::SELECT_QUERY_VARIABLE . $classAttribute->attribute( 'id' );
        if( !($http->hasPostVariable( $selectQueryName ) && trim($http->postVariable( $selectQueryName )) != '')){
			$classAttribute->setAttribute('information', ezpI18n::tr( 'kernel/classes/datatypes',
                                                                         'Valid Select query is required.'));
			return eZInputValidator::STATE_INVALID;
        }
		
        return eZInputValidator::STATE_ACCEPTED;
    }

    function fetchClassAttributeHTTPInput( $http, $base, $classAttribute )
    {
		$content = $classAttribute->content();
        $sqlKeysName = $base . self::SQL_KEYS_VARIABLE . $classAttribute->attribute( 'id' );
        if( $http->hasPostVariable( $sqlKeysName ) && trim($http->postVariable( $sqlKeysName )) != ''){
			$content['SQLKeys'] = explode(',', trim($http->postVariable( $sqlKeysName )));
        }

        $selectQueryName = $base . self::SELECT_QUERY_VARIABLE . $classAttribute->attribute( 'id' );
        if( $http->hasPostVariable( $selectQueryName ) && trim($http->postVariable( $selectQueryName )) != ''){
			$content['SelectQuery'] = trim($http->postVariable( $selectQueryName ));
        }
		
        $insertQueryName = $base . self::INSERT_QUERY_VARIABLE . $classAttribute->attribute( 'id' );
        if( $http->hasPostVariable( $insertQueryName ) && trim($http->postVariable( $insertQueryName )) != ''){
			$content['InsertQuery'] = trim($http->postVariable( $insertQueryName ));
        }
        $updateQueryName = $base . self::UPDATE_QUERY_VARIABLE . $classAttribute->attribute( 'id' );
        if( $http->hasPostVariable( $updateQueryName ) && trim($http->postVariable( $updateQueryName )) != ''){
			$content['UpdateQuery'] = trim($http->postVariable( $updateQueryName ));
        }

        $deleteQueryName = $base . self::DELETE_QUERY_VARIABLE . $classAttribute->attribute( 'id' );
        if( $http->hasPostVariable( $deleteQueryName ) && trim($http->postVariable( $deleteQueryName )) != ''){
            $content['DeleteQuery'] = trim($http->postVariable( $deleteQueryName ));
        }


        $viewsQuerysName = $base . self::VIEWS_QUERYS_VARIABLE . $classAttribute->attribute( 'id' );
        if( $http->hasPostVariable( $viewsQuerysName ) && count($http->postVariable( $viewsQuerysName ))){
            $viewquerys = $http->postVariable( $viewsQuerysName );
            foreach($viewquerys as $viewname => $viewquery){
                if(trim($viewquery) != ''){
                    $content['Views'][$viewname] = $viewquery;
                }
            }
        }
        $classAttribute->setContent( $content );
        $classAttribute->store();

        return true;
    }

    function classAttributeContent( $classAttribute )
    {

        $xmlText = $classAttribute->attribute( 'data_text5' );
        if ( trim( $xmlText ) == '' )
        {
            return $this->defaultClassAttributeContent();
        }
        $doc = $this->parseXML( $xmlText );
        return $this->createClassContentStructure( $doc );
    }

    static function parseXML( $xmlText )
    {
        $dom = new DOMDocument( '1.0', 'utf-8' );
        $dom->loadXML( $xmlText );
        return $dom;
    }

    function defaultClassAttributeContent()
    {
        return array( 'SQLKeys' => array(),
            'SelectQuery' => '',
            'InsertQuery' => '',
            'UpdateQuery' => '',
            'DeleteQuery' => '',
            'can_insert' => false,
            'can_update' => false,
            'can_delete' => false,
            'Views' => array()
        );
    }

    static function createClassDOMDocument( $content )
    {
        $doc = new DOMDocument( '1.0', 'utf-8' );
        $root = $doc->createElement( 'sqlquery' );
        $selectquery = $doc->createElement( 'select-query' );
        $selectquery->setAttribute( 'value', $content['SelectQuery'] );
        $root->appendChild( $selectquery );
        $insertquery = $doc->createElement( 'insert-query' );
        $insertquery->setAttribute( 'value', $content['InsertQuery'] );
        $root->appendChild( $insertquery );
        $updatequery = $doc->createElement( 'update-query' );
        $updatequery->setAttribute( 'value', $content['UpdateQuery'] );
        $root->appendChild( $updatequery );
        $deletequery = $doc->createElement( 'delete-query' );
        $deletequery->setAttribute( 'value', $content['DeleteQuery'] );
        $root->appendChild( $deletequery );
        $keys = $doc->createElement( 'keys' );
        $keys->setAttribute( 'value', implode(',',$content['SQLKeys']) );
        $root->appendChild( $keys );

        $views = $doc->createElement( 'views' );
        foreach ( $content['Views'] as $key => $view )
        {
            if(trim($view) != ''){
                unset( $viewElement );
                $viewElement = $doc->createElement( 'view' );
                $viewElement->setAttribute( 'identifier', $key );
                $viewElement->setAttribute( 'value', $view );
                $views->appendChild( $viewElement );
            }
        }
        $root->appendChild( $views );

        $doc->appendChild( $root );
        return $doc;
    }

    function createClassContentStructure( $doc )
    {
        $content = $this->defaultClassAttributeContent();
        $root = $doc->documentElement;
        $content['SQLKeys'] = explode(',', $root->getElementsByTagName( 'keys' )->item( 0 )->getAttribute( 'value' ));
        $content['SelectQuery'] = $root->getElementsByTagName( 'select-query' )->item( 0 )->getAttribute( 'value' );
        $content['InsertQuery'] = $root->getElementsByTagName( 'insert-query' )->item( 0 )->getAttribute( 'value' );
        $content['UpdateQuery'] = $root->getElementsByTagName( 'update-query' )->item( 0 )->getAttribute( 'value' );
        $content['DeleteQuery'] = $root->getElementsByTagName( 'delete-query' )->item( 0 )->getAttribute( 'value' );
        $content['can_insert'] = (trim($content['InsertQuery']) != '');
        $content['can_update'] = (trim($content['UpdateQuery']) != '');
        $content['can_delete'] = (trim($content['DeleteQuery']) != '');
        $views = $root->getElementsByTagName( 'views' )->item( 0 );
        if ( $views )
        {
            $viewlist = $views->getElementsByTagName( 'view' );
            foreach( $viewlist as $view )
            {
                if(trim($view->getAttribute( 'value' )) != '')
                    $content['Views'][$view->getAttribute( 'identifier' )] = $view->getAttribute( 'value' );
            }
        }

        return $content;
    }

    function initializeClassAttribute( $classAttribute )
    {
        $xmlText = $classAttribute->attribute( 'data_text5' );
        if ( trim( $xmlText ) == '' )
        {
            $content = $this->defaultClassAttributeContent();
            return $this->storeClassAttributeContent( $classAttribute, $content );
        }
    }

    function preStoreClassAttribute( $classAttribute, $version )
    {
        $content = $classAttribute->content();
        return $this->storeClassAttributeContent( $classAttribute, $content );
    }

    function storeClassAttributeContent( $classAttribute, $content )
    {
        if ( is_array( $content ) )
        {
            $doc = $this->createClassDOMDocument( $content );
            $this->storeClassDOMDocument( $doc, $classAttribute );
            return true;
        }
        return false;
    }


    function customClassAttributeHTTPAction( $http, $action, $classAttribute )
    {
        $base = 'ContentClass';
        switch ( $action )
        {
            case 'add_new_view':
                {
                $content = $classAttribute->content();
                $newviewname = "ezsqlquery_newViewName_" . $classAttribute->attribute( 'id' );
                $newviewquery = "ezsqlquery_newViewQuery_" . $classAttribute->attribute( 'id' );

                if( $http->hasPostVariable( $newviewquery ) && trim($http->postVariable( $newviewquery )) != ''){
                    $viewkey = 'view'.count($content['Views']);
                    if( $http->hasPostVariable( $newviewname ) && trim($http->postVariable( $newviewname )) != '' && trim($http->postVariable( $newviewname )) != 'main')
                        $viewkey = preg_replace('/[^a-zA-Z0-9]/s', '',  trim($http->postVariable( $newviewname )));
                    $content['Views'][$viewkey] = trim($http->postVariable( $newviewquery ));
                }
                $classAttribute->setContent( $content );
                } break;
            case 'remove_new_view':
                {
                $content = $classAttribute->content();
                $deleteviewsname = "ezsqlquery_deleteView" . $classAttribute->attribute( 'id' );
                if( $http->hasPostVariable( $deleteviewsname ) && count($http->postVariable( $deleteviewsname ))){
                    $deleteviews = $http->postVariable( $deleteviewsname );
                    foreach($deleteviews as $viewname){
                        if(isset($content['Views'][$viewname])){
                            unset($content['Views'][$viewname]);
                            unset($_POST[$base . self::VIEWS_QUERYS_VARIABLE . $classAttribute->attribute( 'id' )][$viewname]);
                        }
                    }
                }
                $classAttribute->setContent( $content );
                } break;
            default:
                {
                eZDebug::writeError( "Unknown objectrelationlist action '$action'", __METHOD__ );
                } break;
        }
    }

    /*!
     TO IMPLEMENTE
    */
    function metaData( $contentObjectAttribute )
    {
        return $contentObjectAttribute->attribute( 'data_text' );
    }

    function serializeContentClassAttribute( $classAttribute, $attributeNode, $attributeParametersNode )
    {
        $dom = $attributeParametersNode->ownerDocument;
        $content = $classAttribute->content();

        $keyNode = $dom->createElement( 'keys' );
        $keyNode->appendChild( $dom->createTextNode( implode(',',$content['SQLKeys']) ) );
        $attributeParametersNode->appendChild( $keyNode );

        $selectNode = $dom->createElement( 'select-query' );
        $selectNode->appendChild( $dom->createTextNode( $content['SelectQuery']) );
        $attributeParametersNode->appendChild( $selectNode );

        $insertNode = $dom->createElement( 'insert-query' );
        $insertNode->appendChild( $dom->createTextNode( $content['InsertQuery']) );
        $attributeParametersNode->appendChild( $insertNode );

        $updateNode = $dom->createElement( 'update-query' );
        $updateNode->appendChild( $dom->createTextNode( $content['UpdateQuery']) );
        $attributeParametersNode->appendChild( $updateNode );

        $deleteNode = $dom->createElement( 'delete-query' );
        $deleteNode->appendChild( $dom->createTextNode( $content['DeleteQuery']) );
        $attributeParametersNode->appendChild( $deleteNode );

        $viewsNode = $dom->createElement( 'views' );
        $attributeParametersNode->appendChild( $viewsNode );
        foreach ( $content['Views'] as $key => $value )
        {
            if(trim($value) != ''){
                unset($viewNode);
                $viewNode = $dom->createElement( 'view' );
                $viewNode->setAttribute( 'identifier', $key );
                $viewNode->setAttribute( 'value', $value );
                $viewsNode->appendChild( $viewNode );
            }
        }

    }

    function unserializeContentClassAttribute( $classAttribute, $attributeNode, $attributeParametersNode )
    {
        $content = $classAttribute->content();

        $content['SQLKeys'] = explode(',', $attributeParametersNode->getElementsByTagName( 'keys' )->item( 0 )->textContent);
        $content['SelectQuery'] = $attributeParametersNode->getElementsByTagName( 'select-query' )->item( 0 )->textContent;
        $content['InsertQuery'] = $attributeParametersNode->getElementsByTagName( 'insert-query' )->item( 0 )->textContent;
        $content['UpdateQuery'] = $attributeParametersNode->getElementsByTagName( 'update-query' )->item( 0 )->textContent;
        $content['DeleteQuery'] = $attributeParametersNode->getElementsByTagName( 'delete-query' )->item( 0 )->textContent;
        $viewsNode = $attributeParametersNode->getElementsByTagName( 'views' )->item( 0 );
        $viewlist = $viewsNode->getElementsByTagName( 'view' );
        $content['Views'] = array();
        foreach ( $viewlist as $viewNode )
        {
            if(trim($viewNode->getAttribute( 'value' )) != '')
                $content['Views'][$viewNode->getAttribute( 'identifier' )] = $viewNode->getAttribute( 'value' );
        }

        $classAttribute->setContent( $content );
        $this->storeClassAttributeContent( $classAttribute, $content );
    }

    /*!
      Method used by content diff system to retrieve changes in attributes.
      This method implements the default behaviour, which is to show old and
      new version values of the object.
    */
    function diff( $old, $new, $options = false )
    {
        $fromversion = $old->attribute('version');
        $toversion = $new->attribute('version');
        $identifer = $new->attribute('contentclass_attribute_identifier');
        if($fromversion > $toversion){
            $tmp = $fromversion;
            $fromversion = $toversion;
            $toversion = $tmp;
        }
        $comparedversion = array();
        $extradetials = array();
        foreach($new->attribute('object')->attribute('versions') as $version){
            if($version->attribute('version') >= $fromversion && $version->attribute('version') <= $toversion){
                $datamap = $version->attribute('data_map');
                if(isset($datamap[$identifer])){
                    $comparedversion[$version->attribute('version')] = unserialize($datamap[$identifer]->attribute('data_text'));
                    $extradetials[$version->attribute('version')] = array('creator' => $version->attribute('creator'),
                                                                            'created' => $version->attribute('created'));
                }
            }
        }
        return array(   'classcontent' => $new->attribute('contentclass_attribute')->content(),
                        'old' => $old,
                        'new' => $new,
                        'oldversion' => $old->attribute('version'),
                        'newversion' => $new->attribute('version'),
                        'versionshistory' => $comparedversion,
                        'extrainfo' => $extradetials);
    }


    //STATIC FUNCTIONS
    static function storeClassDOMDocument( $doc, $classAttribute )
    {
        $docText = self::domString( $doc );
        $classAttribute->setAttribute( 'data_text5', $docText );
    }

    /*!
     \static
     \return the XML structure in \a $domDocument as text.
             It will take of care of the necessary charset conversions
             for content storage.
    */
    static function domString( $domDocument )
    {
        $ini = eZINI::instance();
        $xmlCharset = $ini->variable( 'RegionalSettings', 'ContentXMLCharset' );
        if ( $xmlCharset == 'enabled' )
        {
            $charset = eZTextCodec::internalCharset();
        }
        else if ( $xmlCharset == 'disabled' )
            $charset = true;
        else
            $charset = $xmlCharset;
        if ( $charset !== true )
        {
            $charset = eZCharsetInfo::realCharsetCode( $charset );
        }
        $domString = $domDocument->saveXML();
        return $domString;
    }

    static function parseTPLString($templateText, $contentObjectAttribute, $extraparama = array()){
        $text = "";
        $tpl = eZTemplate::instance();
        $resourceData = $tpl->resourceData( null, '', '', '' );
        $resourceData['root-node'] = array( eZTemplate::NODE_ROOT, false );
        $rootNamespace = '';
        $resourceData["text"] = $templateText;
        $contentObject = $contentObjectAttribute->attribute('object');
        $tpl->setVariable( "object", $contentObject);
        $tpl->setVariable( "node", $contentObject->attribute('main_node'));
        $tpl->setVariable( "data_map", $contentObject->attribute('data_map'));
        foreach($extraparama as $key => $value)
            $tpl->setVariable( $key, $value );
        $tpl->parse( $templateText, $resourceData['root-node'], $rootNamespace, $resourceData );
        $root =& $resourceData['root-node'];
        $tpl->process( $root, $text, "", "" );
        return $text;

    }

    static function processQuery($sqltemplatecode, $contentObjectAttribute){
        $result = array('sql' => '',
                        'result' => null,
                        'count' => 0,
                        'heading' => array());
        if(trim($sqltemplatecode) == '')
            return $result;
        $result['sql'] = self::parseTPLString($sqltemplatecode, $contentObjectAttribute);
        $db = eZDB::instance();
        $result['result'] = $db->arrayQuery( $result['sql'] );
        $result['count'] = count($result['result']);
        if($result['count'] > 0){
            foreach($result['result'][0] as $key => $value)
                $result['heading'][] = $key;
        }
        return $result;
    }
}

eZDataType::register( eZSQLQueryType::DATA_TYPE_STRING, 'eZSQLQueryType' );

?>

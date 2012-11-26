{* $attribute.content|attribute('show', 5) *}

{def $main_content = $attribute.content['main']
$class_content = $attribute.class_content
$keys = $class_content.SQLKeys}

<div class="block">
    {if and($main_content.result, $main_content.count|gt(0))}
        <table id="sqlquery-jquery-table-{$attribute.id}" class="list" cellspacing="0" summary="{'Query \'%sql\' returned %count result'|i18n( 'design/standard/class/datatype',, hash('%sql', $main_content.sql, '%count', $main_content.count))}">
            <tr>
                {if $class_content.can_delete}
                    <th class="tight"><img width="16" height="16" title="Invert selection." onclick="ezjs_toggleCheckboxes( document.editform, '{$attribute_base}_ezsqlquery_delete_row_{$attribute.id}[]' ); return false;" alt="Invert selection." src="/design/admin2/images/toggle-button-16x16.gif" /></th>
                {/if}
                {foreach $main_content.heading as $heading}
                    <th>{$heading|wash()}</th>
                {/foreach}
            </tr>
            {foreach $main_content.result as $index => $row}
                <tr>
                    {if $class_content.can_delete}
                        <td><input type="checkbox" title="Remove this row." value="{$index}" name="{$attribute_base}_ezsqlquery_delete_row_{$attribute.id}[]"></td>
                    {/if}
                    {foreach $row as $heading => $value}
                        <td>
                            {if and($class_content.SQLKeys|contains($heading), $class_content.can_update)}
                                {$value|wash()}
                            {else}
                                <input class="box ezcc-{$attribute.object.content_class.identifier} ezcca-{$attribute.object.content_class.identifier}_{$attribute.contentclass_attribute_identifier}" type="text" name="{$attribute_base}_ezsqlquery_row_{$attribute.id}[{$index}][{$heading}]" value="{$value|wash( xhtml )}" />
                            {/if}
                        </td>
                    {/foreach}
                </tr>
            {/foreach}
            {if $class_content.can_insert}
                <tr id="new-row-template-{$attribute.id}">
                    <td><input type="button" title="Remove this row from the table." value="x" name="removeThisRowButton" class="button hide sqlquery-jquery-removetr" /></td>
                    {foreach $main_content.heading as $index => $heading}
                        <td><input class="{if $class_content.SQLKeys|contains($heading)}sqlquery-required {/if}box ezcc-{$attribute.object.content_class.identifier} ezcca-{$attribute.object.content_class.identifier}_{$attribute.contentclass_attribute_identifier}" type="text" name="{$attribute_base}_ezsqlquery_new_row_{$attribute.id}[][{$heading}]" value="" /></td>
                    {/foreach}
                </tr>
            {/if}
        </table>
        {if $class_content.can_insert}
            <div id="sqlquery-jquery-buttons-{$attribute.id}">
                <input type="button" title="Add new row to the above table." value="Add new row" name="addNewRowButton" class="button hide" id="sqlquery-add-button-{$attribute.id}" />
                <p id="no-jquery-message-{$attribute.id}" class="warning-message">Requires Jquery to add more than one row in one publish and to valiadate.</p>
            </div>
        {/if}
    {else}
        <span class="warning-message">{'Query %sql returned no result'|i18n( 'design/standard/class/datatype',, hash('sql', $item.sql))}</span>
    {/if}

</div>
{if $class_content.can_insert}
{literal}
<script type="text/javascript">
    //<![CDATA[
    jQuery(document).ready(function() {
        newrowtemplate{/literal}{$attribute.id}{literal} = "<tr>"+jQuery("#new-row-template-{/literal}{$attribute.id}{literal}").html()+"</tr>";
        jQuery("#no-jquery-message-{/literal}{$attribute.id}{literal}, #new-row-template-{/literal}{$attribute.id}{literal}").remove();
        jQuery("#sqlquery-add-button-{/literal}{$attribute.id}{literal}").show();
        jQuery("#sqlquery-add-button-{/literal}{$attribute.id}{literal}").click(function() {
            jQuery("#sqlquery-jquery-table-{/literal}{$attribute.id}{literal}").append(newrowtemplate{/literal}{$attribute.id}{literal});
            $(".sqlquery-jquery-removetr").show();
        });
        {/literal}{run-once}{literal}
        $(".sqlquery-jquery-removetr").live("click", function(){
            $(this).parent().parent().remove();
            return false;
        });
        jQuery('form').submit(function() {
            jQuery(".sqlquery-jquery-error").remove();
            isnewrowvalide = true;
            jQuery('.sqlquery-required').each(function(index) {
                if(jQuery(this).val() == ''){
                    isnewrowvalide = false;
                    jQuery(this).after("<span class='sqlquery-jquery-error'>Value is required for this field.</span>");
                }
            });
            if(!isnewrowvalide){
                alert('Please enter all the require field for the SQL query datatype.');
            }
            return isnewrowvalide;
        });
        {/literal}{/run-once}{literal}
    });
    //]]>
</script>
{/literal}
{/if}
{undef $main_content $class_content}

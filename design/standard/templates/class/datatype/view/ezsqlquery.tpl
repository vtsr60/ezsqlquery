{def $content = $class_attribute.content}

<div class="block">
    <label>{'SQL Keys'|i18n( 'design/standard/class/datatype' )}:</label>
    {if and(is_set($content.SQLKeys), count($content.SQLKeys))}
        <ul><li>{$content.SQLKeys|implode('</li><li>')}</li></ul>
    {else}
        <p>{'No Keys present, So insert, delete and update operation could not be preformed proerly.'|i18n( 'design/standard/class/datatype' )}</p>
    {/if}
</div>

<div class="block">
    <label>{'Select Query'|i18n( 'extension/ezsqlquery' )}:</label>
    <pre>{$content.SelectQuery}</pre>
</div>

<div class="block">
    <label>{'Insert Query'|i18n( 'extension/ezsqlquery' )}:</label>
    {if $content.can_insert}
        <pre>{$content.InsertQuery}</pre>
    {else}
        <p>Insert query not present, So insert operation can not be preformed on this attribute</p>
    {/if}
</div>

<div class="block">
    <label>{'Update Query'|i18n( 'extension/ezsqlquery' )}:</label>
    {if $content.can_update}
        <pre>{$content.UpdateQuery}</pre>
    {else}
        <p>Update query not present, So update operation can not be preformed on this attribute</p>
    {/if}

</div>

<div class="block">
    <label>{'Delete Query'|i18n( 'extension/ezsqlquery' )}:</label>
    {if $content.can_delete}
        <pre>{$content.DeleteQuery}</pre>
    {else}
        <p>Delete query not present, So delete operation can not be preformed on this attribute</p>
    {/if}

</div>

<div class="block">
    <label>{'Views'|i18n( 'extension/ezsqlquery' )}:</label>
    {if count($content.Views)}
        {foreach $content.Views as $key => $view}
            <div class="block">
                <label>{$key}:</label>
                <pre>{$view}</pre>
            </div>
        {/foreach}
    {else}
        <p>No views was add.</p>
    {/if}

</div>

{undef $content}
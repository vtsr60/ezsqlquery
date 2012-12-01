{* $attribute.content|attribute('show', 5) *}
{foreach $attribute.content as $key => $item}
    <div class="block">
        {if and($item.result, $item.count|gt(0))}
            <table class="list" cellspacing="0" summary="{'Query \'%sql\' returned %count result'|i18n( 'design/standard/class/datatype',, hash('%sql', $item.sql, '%count', $item.count))}">
                <caption>{$key|wash()}</caption>
                <tr>
                    {foreach $item.heading as $heading}
                        <th>{$heading|wash()}</th>
                    {/foreach}
                </tr>
                {foreach $item.result as $row}
                    <tr>
                        {foreach $row as $heading => $value}
                            <td>{$value|wash()}</td>
                        {/foreach}
                    </tr>
                {/foreach}
            </table>
        {else}
            <span class="warning-message">{'Query %sql returned no result'|i18n( 'design/standard/class/datatype',, hash('%sql', $item.sql))}</span>
        {/if}
    </div>
{/foreach}
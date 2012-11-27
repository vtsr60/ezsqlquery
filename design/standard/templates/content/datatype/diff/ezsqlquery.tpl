<div class="block">
{foreach $diff.versionshistory as $version => $change}
    <div class="block">
        <h4>Change done on version {$version} by '{$diff.extrainfo[$version].creator.name|wash()}' on {$diff.extrainfo[$version].created|datetime( 'custom', '%d/%m/%Y %h:%i%a' )}</h4>
        {def $haschanged = false()}
        {if and(is_set($change.update), $change.update|count())}
            <h5>Update row:</h5>
            {foreach $change.update as $row}
                <ul>
                    {foreach $row as $key => $value}
                        {if $diff.classcontent.SQLKeys|contains($key)}
                            <li><strong>{$key}:</strong> <i>{$value|wash}</i></li>
                        {else}
                            <li><ins><strong>{$key}:</strong> <i>{$value|wash}</i></ins></li>
                        {/if}
                    {/foreach}
                </ul>
            {/foreach}
            {set $haschanged = true()}
        {/if}

        {if and(is_set($change.new), $change.new|count())}
            <h5>Added rows :</h5>
            {foreach $change.new as $row}
                <ul>
                {foreach $row as $key => $value}
                    <li><ins><strong>{$key}:</strong> <i>{$value|wash}</i></ins></li>
                {/foreach}
                </ul>
            {/foreach}
            {set $haschanged = true()}
        {/if}
        {if and(is_set($change.delete), $change.delete|count())}
            <h5>Deleted rows :</h5>
            {foreach $change.delete as $row}
                <ul>
                    {foreach $row as $key => $value}
                        <li><del><strong>{$key}:</strong> <i>{$value|wash}</i></del></li>
                    {/foreach}
                </ul>
            {/foreach}
            {set $haschanged = true()}
        {/if}
        {if $haschanged|not()}
            <i>No change was done on this version.</i>
        {/if}
    </div>
{/foreach}
</div>

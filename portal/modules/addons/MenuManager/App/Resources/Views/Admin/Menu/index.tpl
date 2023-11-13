{extends file="$views/Admin/layout.tpl"}

{block name=js}
{/block}

{block name=css}
    <style>

    </style>
{/block}

{block name=content}
    <div>

        <table class="table dataTable no-footer" id="ignored-ip" style="width: 100%;" role="grid">
            <thead>
                <tr role="row">
                    <th>Name</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                {foreach $menuGroups as $group}

                    <tr>
                        <td>
                            <a href="{$addon_link}&action=details&group_id={$group->group_code}">
                                {$group->name}
                            </a>
                        </td>
                        <td>
                            <a href="{$addon_link}&action=details&group_id={$group->group_code}" class="btn btn-primary">
                                Show items
                            </a>
                        </td>
                    </tr>
                {/foreach}



            </tbody>
        </table>
    </div>
{/block}
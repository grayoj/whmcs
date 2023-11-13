
<li{if $smarty.server.QUERY_STRING|strpos:"page=menu" !== false OR $smarty.get.page === null} class="active"{/if}>
    <a class="btn btn-xs" href="{$addon_link}&page=menu">Menu groups</a>
</li>

<li id="check_update_btn" {if $smarty.server.QUERY_STRING|strpos:"page=checkUpdate" !== false} class="active"{/if}>
    <a id="checkUpdateMenuItem" class="btn btn-xs" href="{$addon_link}&page=checkUpdate&action=main">Check Update</a>
</li>

<div id="buton_template" class="hidden">
    <li>
        <a class="checkUpdateMenuItem btn btn-xs" href=""></a>
    </li>
</div>
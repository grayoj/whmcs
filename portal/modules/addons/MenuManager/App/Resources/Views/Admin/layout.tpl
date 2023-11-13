<div class="menu_tools" style="position: relative">

    <div class="addon-menu clearfix">
        <div class="logo">
            <img src="../modules/addons/MenuManager/App/Resources/Assets/Img/logo.png">
        </div>

        <div class="clearfix"></div>
        <div class="menu-buttons">
            <ul class="clearfix nav nav-pills menu-buttons">
                {include file="$views/Admin/menu.tpl"}
            </ul>
        </div> 
    </div>



    <div class="bk-all">
        <div class="row">
            <div class="col-lg-12">
                {foreach from=$flashMessages item=flashMessage}
                    <div class="alert alert-{$flashMessage->getType()} alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        {$flashMessage->getMessage()}
                    </div>
                {/foreach}
            </div>
        </div>

    <div id="content_block_wrapper">
        {block name=content}{/block}
    </div>

</div>
</div>

<script>
    var checkUpdateLink = '{$checkUpdateLink}&module-version={$version}';
    var modulesQuery = '{$activeModules}';
</script>

<script src="../modules/addons/MenuManager/App/Resources/Assets/JS/check_update.js"></script>
<script src="../modules/addons/MenuManager/App/Resources/Assets/JS/check_menu_item.js"></script>
<link rel="stylesheet" type="text/css" href="https://www.webfrigate.co.uk/whmcs_check/styles/menu_style.css">

{block name=css}{/block}
{block name=js}{/block}

<style>
    #contentarea > div > h1 {
        display: none;
    }
    #contentarea {
        padding: 0;
        border: 0;
        overflow: hidden;
    }

    #contentarea  .bk-all {
        padding: 12px;
    }

    .menu_tools .addon-menu {
        min-height: 55px;
        background: #98d3e2;
        background: -moz-linear-gradient(top, #98d3e2 0%, #d9edf2 60%, #ffffff 100%);
        background: -webkit-linear-gradient(top, #98d3e2 0%, #d9edf2 60%, #ffffff 100%);
        background: linear-gradient(to bottom, #98d3e2 0%, #d9edf2 60%, #ffffff 100%);
        filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#98d3e2', endColorstr='#ffffff',GradientType=0 ); }
    .menu_tools .addon-menu .logo {
        float: left; }
    .menu_tools .addon-menu .logo img {
        max-width: 175px;
        margin-top: 10px;
        margin-left: 30px; }
    .menu_tools .addon-menu .logo h2.addon-name {
        font-size: 1.8rem;
        font-family: Lato;
        font-weight: bold;
        padding: 9px 30px; }
    .menu_tools .addon-menu .menu {
        float: left; }
    .menu_tools .addon-menu .menu ul {
        margin: 0;
        padding: 0;
        list-style-type: none; }
    .menu_tools .addon-menu .menu ul li {
        float: left;
        display: block;
        margin: 32px 25px; }
    .menu_tools .addon-menu .menu ul li a {
        display: block;
        text-decoration: none !important; }
    .menu_tools .addon-menu .menu ul li a span.icon {
        color: gray;
        font-size: 1.7rem;
        margin-right: 7px;
        -webkit-transition: color 0.25s ease-in-out;
        -moz-transition: color 0.25s ease-in-out;
        -ms-transition: color 0.25s ease-in-out;
        -o-transition: color 0.25s ease-in-out;
        transition: color 0.25s ease-in-out; }
    .menu_tools .addon-menu .menu ul li a span.item-label {
        color: gray;
        font-weight: 300;
        font-family: Lato;
        font-size: 2.0rem;
        -webkit-transition: color 0.25s ease-in-out;
        -moz-transition: color 0.25s ease-in-out;
        -ms-transition: color 0.25s ease-in-out;
        -o-transition: color 0.25s ease-in-out;
        transition: color 0.25s ease-in-out; }
    .menu_tools .addon-menu .menu ul li a:hover span.icon {
        color: #0f8be6; }
    .menu_tools .addon-menu .menu ul li a:hover span.item-label {
        color: #0f8be6; }
    .menu_tools .addon-menu .menu ul li.active a span.icon {
        color: #0f8be6; }
    .menu_tools .addon-menu .menu ul li.active a span.item-label {
        color: #0f8be6; }
    .menu_tools .addon-content {
        padding: 15px;
        position: relative; }
    .menu_tools .addon-content .pagination {
        width: 100%;
        border: none;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #e8e8e8; }
    .menu_tools .addon-content .pagination .navs {
        float: left; }
    .menu_tools .addon-content .pagination .navs.prev-first {
        margin-right: 10px; }
    .menu_tools .addon-content .pagination .navs.next-last {
        margin-left: 10px; }
    .menu_tools .addon-content .pagination .numbers {
        float: left; }
    .menu_tools .addon-content .clear-search {
        margin-left: 7px; }
    .menu_tools .addon-content .search-form input {
        top: -1px;
        width: 225px;
        outline: none;
        font-weight: 400;
        padding: 2px 10px;
        font-size: 1.4rem;
        font-family: Lato;
        position: relative;
        border: 1px solid lightgray; }
    .menu_tools .addon-content table th.actions, .menu_tools .addon-content table td.actions {
        width: 370px; }
    .menu_tools .addon-messages {
        padding: 0 15px; }

    .w300 {
        width: 300px; }

    .menu-buttons{
        margin-right: 5px;
    }

    .dataTables_wrapper{
        margin-top: 50px;
    }

    #versionsContent .container{
        width: 100%;
        padding: 0;

    }

    .nav-pills>li.active>a:focus, .nav-pills>li.active>a:hover{
        background-color: #286090;
        border-color: #286090;
    }

    #versionsContent .version{
        padding: 10px 20px;
    }

    #versionsContent .nex-versions .major{
        background: rgba(255,0,0,0.3);

    }

    #versionsContent .nex-versions .normal{
        background: rgba(255,255,0,0.3);  
    }


    .menu_tools .menu-buttons{
        margin: 20px 0;
    }


    /*# sourceMappingURL=style.css.map */
</style>
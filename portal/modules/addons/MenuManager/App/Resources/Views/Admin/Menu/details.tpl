{extends file="$views/Admin/layout.tpl"}

{block name=js}

    <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script src="../modules/addons/MenuManager/App/Resources/Assets/fontawesome-iconpicker.min.js"></script>

    <link rel="stylesheet" type="text/css" href="../modules/addons/MenuManager/App/Resources/Assets/fontawesome-iconpicker.min.css" />

    <script>

        var saveLink = '{$addon_link}&action=save';
        {literal}
            var $y = jQuery.noConflict(true);
            $y(document).ready(function () {

                $y('[name="icon"]').iconpicker();

                var itemsData = $y('[data-items-body]').data('items-body');


                for (let item of itemsData) {
                    let htmlElement = readItem(item);
                    $y('#items-containter').append(htmlElement);
                }

                $y('#add-new-item').click(function () {
                    var cloned = $('#new-item .item').clone();
                    
                    $y(cloned.find('[name="icon"]')).iconpicker();

                    $y(cloned.find('.children')).sortable({
                        connectWith: ".connectedSortable"
                    });

                    $y('#items-containter').append(cloned);
                });

                $y("#items-containter").sortable({
                    connectWith: ".connectedSortable"
                });

                $y('#save-menu').click(function () {
                    var data = getFormValue($y('#items-containter > .item'));
                    var id = $y('[data-group_id').data('group_id');

                    $y.post(saveLink, {group_id: id, data: data}, function (response) {
                        console.log(response);
                    });
                });
                $y('.delete').click(function () {
                    $y(this).closest('.item').remove();
                });

                function readItem(data) {

                    var cloned = $('#new-item .item').clone();

                    cloned.find('[name="link"]').val(data.link);
                    cloned.find('[name="label"]').val(data.label);
                    cloned.find('[name="icon"]').val(data.icon);
                    $y(cloned.find('[name="icon"]')).iconpicker();

                    if (data.target === "_blank") {
                        cloned.find('[name="target"]').attr("checked", "checked");
                    }
                    cloned.find('[name="display_for"]').val(data.display_for);

                    var children = $y(cloned.find('.children'));

                    data.children = data.children ? data.children : [];

                    for (let dataChild of data.children) {
                        children.append(readItem(dataChild));
                    }

                    children.sortable({
                        connectWith: ".connectedSortable"
                    });

                    return cloned;

                }

                function getFormValue(items) {
                    var result = [];


                    for (var item of items) {
                        var current = $y(item);
                        var targetChecked = current.find('>[name="target"]:checked');

                        var link = current.find('>[name="link"]').val();
                        var label = current.find('>[name="label"]').val();
                        var icon = current.find('>[name="icon"]').val();
                        var target = targetChecked.length > 0 ? '_blank' : '';
                        var display_for = current.find('>[name="display_for"]').val();

                        var resultObject = {
                            link, label, icon, target, display_for,
                            children: getFormValue($y(current.find('>.children > .item')))
                        }

                        result.push(resultObject);
                    }

                    return result;
                }
            });
        {/literal}
    </script>

{/block}

{block name=css}
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <style>

        .menu_tools .item{
            background: aliceblue;
            border: 5px solid white;
            padding: 8px 8px 0 8px;
            cursor: grab;
        }


        .menu_tools .children{
            padding:10px;
        }

        #new-item .delete{
            display: none;
        }
    </style>
{/block}

{block name=content}
    <div class="details_container {if $is_premium} premium {else} free{/if}">
        <div data-items-body='{$items}'>

            <div class="row" data-group_id="{$group->group_code}"><h3>{$group->name}</h3></div>

            <div id='items-containter' class="connectedSortable">
            </div>
        </div>

        <div id='new-item'>
            <h4>New item:</h4>
            <div class='ui-state-default item'>
                Link: <input name="link" placeholder="{if $is_premium} Add https:// for external links {else}Use internal link {/if}"/>
                Label: <input name="label"/>
                Open in new tab: <input type="checkbox" name="target" value="_blank" {if !$is_premium} disabled {/if}/>
                Display for: <select name="display_for">
                    <option value="">All</option>
                    <option value="logged_client">Logged Client</option>
                    <option value="guest">Guest</option>
                    <option value="hide">Hide</option>
                </select>
                Icon: <input name="icon" placeholder="e.g. fas fa-envelope" {if !$is_premium} disabled {/if}/>
                <button class="btn btn-danger delete pull-right">Delete</button>
                <div class="children connectedSortable"></div>
            </div>

        </div>
        <div class="buttons">
            <button id="add-new-item" class="btn btn-primary">Add new item</button>
            <button id="save-menu" class="btn btn-primary">Save menu</button>
        </div>
    </div>
{/block}

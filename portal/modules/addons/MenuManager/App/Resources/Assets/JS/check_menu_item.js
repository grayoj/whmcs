$(document).ready(function () {

    var valueCheckMenuLink = 'https://www.webfrigate.co.uk/whmcs_check/index.php?v=1&' + modulesQuery;
    var module = getUrlVars()["module"];
    
    $.get(valueCheckMenuLink,
            {module},
            (response) => {

        try {

            var data = JSON.parse(response);

            var checkUpdateBtn = $('#check_update_btn');
            var buttonTemplate = $('#buton_template');


            for (var item of data.menu) {
                var html = buttonTemplate.html();
                var result = $(html)
                result.find('.checkUpdateMenuItem').attr('href', item.url + '?' + modulesQuery);
                result.find('.checkUpdateMenuItem').html(item.label);
                result.find('.checkUpdateMenuItem').addClass(item.buttonClass);

                result.find('.checkUpdateMenuItem').click(function (event) {
                    event.preventDefault();
                    event.stopPropagation();

                    var link = $(this).attr('href');

                    $.get(link,
                            {module},
                            (response) => {

                        $('#content_block_wrapper').html(response);
                    });

                })

                checkUpdateBtn.before(result);
            }

        } catch (e) {
            console.error(e);
            console.error(response);
        }
    }
    );
});


function getUrlVars()
{
    var vars = [], hash;
    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
    for (var i = 0; i < hashes.length; i++)
    {
        hash = hashes[i].split('=');
        vars.push(hash[0]);
        vars[hash[0]] = hash[1];
    }
    return vars;
}
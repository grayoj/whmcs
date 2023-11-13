$(document).ready(function () {

    $.get(checkUpdateLink,
            {},
            (response) => {

        try {

            var data = JSON.parse(response);

            if (data.length > 1) {
                $('#checkUpdateMenuItem').addClass('btn-danger');
            }

            var versionsContent = $('#versionsContent');

            if (versionsContent.length > 0) {

                var current = printVersion(data[0]);

                var futureVersions = '';
                var numberOfFuture = 0;
                for (var i = 1; i < data.length; i++) {
                    var content = printVersion(data[i]);
                    futureVersions += `<div>${content}</div>`;
                    numberOfFuture++;
                }


                var html = `
            <div class="container">
                <div class="col-md-6">
                        <h2>Current Version:</h2>
                        ${current}
                </div>

                    ${numberOfFuture > 0 ?
                        `<div class="col-md-6 nex-versions">
                        <h2>Next Versions:</h2>
                        ${futureVersions}</div>
                      </div>`
                        :
                        ''}


    `;
                versionsContent.html(html);
            }
        } catch (e) {
            var versionsContent = $('#versionsContent');

            if (versionsContent.length > 0) {
                versionsContent.html(response);
            }
        }
    }
    );
});

function printVersion(row) {

    var items = row.change_list.map((item) => `<li>${item}</li>`)

    return `<div class="version ${row.is_major ? 'major' : 'normal'}">
                                <div> Version: ${row.number} </div>
                                <div> Release date: ${row.release_date} </div>
                                <div> Is major: ${row.is_major ? 'True' : 'False'} </div>
                                <div> Changes: </div>
                                <ul>${items.join('')}</ul>
                            </div>`
}
